<?php
/**
 * BSeller Platform | B2W - Companhia Digital
 *
 * Do not edit this file if you want to update this module for future new versions.
 *
 * @category  BSeller
 * @package   BSeller_SkyHub
 *
 * @copyright Copyright (c) 2018 B2W Digital - BSeller Platform. (http://www.bseller.com.br)
 *
 * @author    Tiago Sampaio <tiago.sampaio@e-smart.com.br>
 */

class BSeller_SkyHub_Model_Observer_Catalog_Product extends BSeller_SkyHub_Model_Observer_Abstract
{

    use BSeller_SkyHub_Model_Integrator_Catalog_Product_Validation,
        BSeller_SkyHub_Trait_Entity,
        BSeller_SkyHub_Trait_Queue;
    
    /**
     * @param Varien_Event_Observer $observer
     */
    public function integrateProduct(Varien_Event_Observer $observer)
    {
        $this->processStoreIteration($this, 'prepareIntegrationProduct', $observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @param Mage_Core_Model_Store $store
     */
    public function prepareIntegrationProduct(Varien_Event_Observer $observer, Mage_Core_Model_Store $store)
    {
        if (!$this->canRun($store->getId())) {
            return;
        }
    
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');
        $this->processIntegrationProduct($product, $store);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param bool $forceQueue
     * @return void
     */
    protected function processIntegrationProduct(Mage_Catalog_Model_Product $product, Mage_Core_Model_Store $store)
    {
        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        foreach ($parentIds as $id) {
            $this->processIntegrationProduct(Mage::getModel('catalog/product')->load($id), $store);
        }

        if (!$product->getData('is_salable')) {
            if ($product->hasData('is_salable') && $product->getData('is_salable') == null) {
                $product->unsetData('is_salable');
            }
        }

        if (!$this->canIntegrateProduct($product, false, $store)) {
            return;
        }

        $forceIntegrate = true;
        if ($this->hasActiveIntegrateOnSaveFlag() && $this->hasStockOrPriceUpdate($product)) {
            try {
                /** Create or Update Product */
                $this->catalogProductIntegrator()->createOrUpdate($product);
                $forceIntegrate = false;

                // just to tell other "observers" to don't put the integration flag on these products;
                if ($recentIntegratedIds = Mage::registry('recent_integrated_product')) {
                    Mage::unregister('recent_integrated_product');
                    Mage::register('recent_integrated_product', array_merge($recentIntegratedIds, array($product->getId())));
                } else {
                    Mage::register('recent_integrated_product', array($product->getId()));
                }
                // end
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        if ($forceIntegrate) {
            $this->flagEntityIntegrate($product->getId());
        }
    }
    
    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return bool
     *
     * @throws Varien_Exception
     */
    protected function hasStockOrPriceUpdate(Mage_Catalog_Model_Product $product)
    {
        if ($product->getOrigData('price') && $product->getOrigData('price') != $product->getData('price')) {
            return true;
        }
        if ($product->getOrigData('special_price') != $product->getData('special_price')) {
            return true;
        }
        if ($product->getOrigData('promotional_price') != $product->getData('promotional_price')) {
            return true;
        }
        if ($product->getStockData('qty') != $product->getStockData('original_inventory_qty')) {
            return true;
        }
        return false;
    }
    

    /**
     * @param Varien_Event_Observer $observer
     */
    public function deleteProduct(Varien_Event_Observer $observer)
    {
        $this->processStoreIteration($this, 'processDeleteProduct', $observer);
    }
    

    /**
     * @param Varien_Event_Observer $observer
     */
    public function processDeleteProduct(Varien_Event_Observer $observer, Mage_Core_Model_Store $store)
    {
        if (!$this->canRun($store->getId())) {
            return;
        }

        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        if (!$this->canIntegrateProduct($product)) {
            return;
        }

        /** Create or Update Product */
        $this->catalogProductIntegrator()->delete($product->getSku());
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function disableProduct(Varien_Event_Observer $observer)
    {
        if (!$this->canRun()) {
            return;
        }

        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        if (!$this->canIntegrateProduct($product)) {
            return;
        }

        $responseHandler = $this->catalogProductIntegrator()->product($product->getSku());
        if ($responseHandler === false || ($responseHandler && $responseHandler->exception())) {
            return;
        }

        //disable the item and set 0 to stock items
        $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
        $stockItem = $product->getStockItem();
        if ($stockItem) {
            $stockItem->setQty(0);
            $stockItem->save();
        }

        /** Create or Update Product */
        $this->catalogProductIntegrator()->update($product);
    }
    
    
    /**
     * @param Varien_Event_Observer $observer
     */
    public function addIntegrateButtonToProductEditPage(Varien_Event_Observer $observer)
    {
        // $storeId = Mage::app()->getRequest()->getParam('store');
        
        /** @var Mage_Core_Model_Store $store */
        // $store = Mage::app()->getStore($storeId);
    
        // if (!$this->canRun($store->getId())) {
        //     return;
        // }
        
        /** @var Mage_Adminhtml_Block_Catalog_Product_Edit $block */
        $block = $observer->getData('block');

        if (!($block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit)) {
            return;
        }

        $product = $block->getProduct();

        if (!$product || !($product instanceof Mage_Catalog_Model_Product)) {
            return;
        }

        if (!$this->canIntegrateProduct($product)) {
            return;
        }

        /** @var Mage_Adminhtml_Block_Widget_Button $backButton */
        $backButton = $block->getChild('back_button');

        if (!($backButton instanceof Mage_Adminhtml_Block_Widget_Button)) {
            return;
        }

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = $block->getLayout()->createBlock('adminhtml/widget_button');
        $button->setData(array(
            'label'   => Mage::helper('catalog')->__('Integrate With SkyHub'),
            'onclick' => "setLocation('{$this->getIntegrateUrl($block)}')",
            'class'   => 'success'
        ));

        $backButton->setData('before_html', $button->toHtml());
    }
    
    
    /**
     * @param Mage_Adminhtml_Block_Catalog_Product_Edit $block
     *
     * @return string
     */
    protected function getIntegrateUrl(Mage_Adminhtml_Block_Catalog_Product_Edit $block)
    {
        $url = (string) $block->getUrl(
            '*/*/integrate',
            array('product_id' => $block->getProductId())
        );
        return $url;
    }

    public function integrateCatalogInventory(Varien_Event_Observer $observer)
    {
        $this->processStoreIteration($this, 'catalogInventoryCommit', $observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function catalogInventoryCommit(Varien_Event_Observer $observer, Mage_Core_Model_Store $store)
    {
        if (!$this->canRun($store->getId())) {
            return;
        }

        $recentIntegratedProductIds = Mage::registry('recent_integrated_product');
        $product = $this->_getProduct($observer->getItem());
        $productId = $product->getId();
        if (!$recentIntegratedProductIds || !in_array($productId, $recentIntegratedProductIds)) {
            if (!$this->canIntegrateProduct($product, false, $store)) {
                return false;
            }
            $this->flagEntityIntegrate($productId);
        }
    }

    /**
     * @param $item
     * @return Mage_Core_Model_Abstract|Varien_Object
     */
    protected function _getProduct($item)
    {
        $product = $item->getProduct();
        if ($product instanceof Varien_Object) {
            return $product;
        }

        return Mage::getModel('catalog/product')->load((int)$item->getProductId());
    }

    public function integrateProductForce(Varien_Event_Observer $observer)
    {
        $productId = $observer->getProductId();
        $this->flagEntityIntegrate($productId);
    }

    public function integrateProductsAfterMassiveAttributesUpdate(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getProductIds();

        foreach ($productIds as $productId) {
            $this->flagEntityIntegrate($productId);
        }
    }
}
