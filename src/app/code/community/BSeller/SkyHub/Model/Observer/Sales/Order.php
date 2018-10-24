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
class BSeller_SkyHub_Model_Observer_Sales_Order extends BSeller_SkyHub_Model_Observer_Sales_Abstract
{
    use BSeller_SkyHub_Model_Integrator_Catalog_Product_Validation;
    use BSeller_SkyHub_Trait_Queue;
    use BSeller_SkyHub_Trait_Entity;

    /**
     * @param Varien_Event_Observer $observer
     */
    public function logOrderDetails(Varien_Event_Observer $observer)
    {
        if (true === Mage::registry('disable_order_log')) {
            return;
        }

        /**
         * @var Exception $exception
         * @var array $orderData
         */
        $exception = $observer->getData('exception');
        $orderData = (array)$observer->getData('order_data');

        if (!$exception || !$orderData) {
            return;
        }

        $orderCode = $this->arrayExtract($orderData, 'code');

        $data = array(
            'entity_id' => null,
            'reference' => (string)$orderCode,
            'entity_type' => BSeller_SkyHub_Model_Entity::TYPE_SALES_ORDER,
            'status' => BSeller_SkyHub_Model_Queue::STATUS_FAIL,
            'process_type' => BSeller_SkyHub_Model_Queue::PROCESS_TYPE_IMPORT,
            'messages' => $exception->getMessage(),
            'additional_data' => json_encode($orderData),
            'can_process' => false,
            'store_id' => (int)$this->getStoreId(),
        );

        /** @var BSeller_SkyHub_Model_Queue $queue */
        $queue = Mage::getModel('bseller_skyhub/queue');
        $queue->setData($data);
        $queue->save();
    }


    /**
     * @param Varien_Event_Observer $observer
     */
    public function cancelOrderAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getData('order');

        if (!$order || !$order->getId()) {
            return;
        }
        $this->getStoreIterator()->call($this->orderIntegrator(), 'cancel', array($order->getId()), $order->getStore());
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function reintegrateOrderProducts(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getData('order');

        if (!$order || !$order->getId()) {
            return;
        }

        $products = $order->getAllVisibleItems();

        foreach ($products as $item) {
            $this->processReintegrationOrderProducts($item->getProduct());
        }
    }

    protected function processReintegrationOrderProducts($product)
    {
        $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        foreach ($parentIds as $id) {
            $this->processReintegrationOrderProducts(Mage::getModel('catalog/product')->load($id));
        }

        if (!$this->canIntegrateProduct($product)) {
            return;
        }

        $hasActiveIntegrateProductsOnOrderPlaceFlag = $this->hasActiveIntegrateProductsOnOrderPlaceFlag();
        if ($hasActiveIntegrateProductsOnOrderPlaceFlag) {
            /**
             * integrate all order items on skyhub (mainly to update stock qty)
             */
            $response = $this->catalogProductIntegrator()->createOrUpdate($product);

            if ($response && $response->success()) {
                return;
            }
        }

        $queueResource = $this->getQueueResource();
        /**
         * put the product on the line
         */
        $queueResource->queue(
            [$product->getId()],
            BSeller_SkyHub_Model_Entity::TYPE_CATALOG_PRODUCT,
            BSeller_SkyHub_Model_Queue::PROCESS_TYPE_EXPORT
        );
    }
}
