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

    use BSeller_SkyHub_Model_Integrator_Catalog_Product_Validation;
    
    /**
     * @param Varien_Event_Observer $observer
     */
    public function integrateProduct(Varien_Event_Observer $observer)
    {
        if (!$this->canRun()) {
            return;
        }

        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        if (!$this->canIntegrateProduct($product)) {
            return;
        }

        if ($this->hasActiveIntegrateOnSaveFlag() && $this->hasStockOrPriceUpdate($product)) {
            /** Create or Update Product */
            $this->catalogProductIntegrator()->createOrUpdate($product);
        } else {
            //check if this product already exists at queue table
            $queueRow = Mage::getModel('bseller_skyhub/queue')->load($product->getId(), 'entity_id');
            if($queueRow && $queueRow->getId()) {
                return;
            }

            //enqueue this product
            $queue = Mage::getModel('bseller_skyhub/queue')->queue(
                $product->getId(),
                BSeller_SkyHub_Model_Entity::TYPE_CATALOG_PRODUCT
            );
            $queue->save();
        }
    }

    protected function hasStockOrPriceUpdate($product)
    {
        if ($product->getOrigData('price') != $product->getData('price')) {
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
        if (!$this->canRun()) {
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
    public function addIntegrateButtonToProductEditPage(Varien_Event_Observer $observer)
    {
        if (!$this->canRun()) {
            return;
        }

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
        $button->setData([
            'label'   => Mage::helper('catalog')->__('Integrate With SkyHub'),
            'onclick' => "setLocation('{$this->getIntegrateUrl($block)}')",
            'class'   => 'success'
        ]);

        $backButton->setData('before_html', $button->toHtml());
    }
    
    
    /**
     * @param Mage_Adminhtml_Block_Catalog_Product_Edit $block
     *
     * @return string
     */
    protected function getIntegrateUrl(Mage_Adminhtml_Block_Catalog_Product_Edit $block)
    {
        $url = (string) $block->getUrl('*/*/integrate', ['product_id' => $block->getProductId()]);
        return $url;
    }
}
