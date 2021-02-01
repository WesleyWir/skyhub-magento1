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
 * Access https://ajuda.skyhub.com.br/hc/pt-br/requests/new for questions and other requests.
 */

class BSeller_SkyHub_Controller_Admin_Queue extends BSeller_SkyHub_Controller_Admin_Action
{
    
    /**
     * @param string|null $actionTitle
     *
     * @return $this
     */
    protected function init($actionTitle = null)
    {
        parent::init('Order');
        
        if (!empty($actionTitle)) {
            $this->_title($this->__($actionTitle));
        }
        
        return $this;
    }
    
    
    /**
     * @param int|array $queueIds
     *
     * @return $this
     */
    protected function deleteQueueIds($queueIds)
    {
        /** @var BSeller_SkyHub_Model_Resource_Queue $queue */
        $queue = Mage::getResourceModel('bseller_skyhub/queue');
        $queue->deleteByQueueIds((array) $queueIds);
        
        $this->_getSession()->addSuccess($this->__('The logs were successfully removed.'));
        
        return $this;
    }
    
    
    /**
     * @param int $queueId
     *
     * @return bool
     *
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    protected function importOrderByQueueId($queueId)
    {
        /** @var BSeller_SkyHub_Model_Queue $queue */
        $queue     = Mage::getModel('bseller_skyhub/queue')->load((int) $queueId);
        $reference = $queue->getReference();

        $this->prepareStore((int) $queue->getStoreId());

        if (!$queue->getId() || !$reference) {
            return false;
        }
        
        Mage::register('disable_order_log', true, true);
        $this->importOrder($reference);
        Mage::unregister('disable_order_log');
        
        return true;
    }
    
    
    /**
     * @param string $code
     * @param null   $storeId
     *
     * @return bool
     * @throws Exception
     */
    protected function importOrder($code, $storeId = null)
    {
        if (!$this->isModuleEnabled($storeId)) {
            $this->_getSession()->addError($this->__('The module is not enabled in this store.', $code));
            return false;
        }
        
        if (!$this->isConfigurationOk($storeId)) {
            $this->_getSession()->addError($this->__('The module is not configured correctly in this store.', $code));
            return false;
        }
        
        /** @var bool|array $orderData */
        $orderData = $this->getOrderIntegrator()->order($code);
        
        if (!$orderData) {
            $this->_getSession()->addError($this->__('Order code %s does not exist in SkyHub.', $code));
            return false;
        }
        
        /** @var bool|Mage_Sales_Model_Order $order */
        $order = $this->salesOrderProcessor()->createOrder($orderData);
        
        if (false === $order) {
            $this->_getSession()->addError($this->__('The order %s cannot be created in Magento.', $code));
            return false;
        }
        
        if (!$order->getData('is_created')) {
            $this->_getSession()->addNotice($this->__('The order %s already exists in Magento.', $code));
            return false;
        }
        
        $this->_getSession()->addSuccess($this->__('The order %s was successfully created.', $code));
        
        return true;
    }
    
    
    /**
     * @return BSeller_SkyHub_Model_Integrator_Sales_Order
     */
    protected function getOrderIntegrator()
    {
        /** @var BSeller_SkyHub_Model_Integrator_Sales_Order $integrator */
        $integrator = Mage::getSingleton('bseller_skyhub/integrator_sales_order');
        return $integrator;
    }
    
    
    /**
     * @return array
     */
    protected function getCleanedOrderCodes()
    {
        $orderCodes = $this->getRequest()->getPost('order_codes');
        $orderCodes = explode(PHP_EOL, $orderCodes);
        
        $cleanedCodes = array();
        
        foreach ($orderCodes as $orderCode) {
            if (empty($orderCode)) {
                continue;
            }
            
            $cleanedCodes[] = trim($orderCode);
        }
        
        return $cleanedCodes;
    }


    /**
     * @param null|int $storeId
     *
     * @return $this
     *
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function prepareStore($storeId = null)
    {
        if (!$storeId) {
            $storeId = $this->getRequest()->getPost('store_id', $this->getStoreIterator()->getDefaultStore());
        }

        /** @var Mage_Core_Model_Store $store */
        $store  = Mage::app()->getStore($storeId);

        $this->_getSession()->setData('simulated_store_id', $store->getId());
        $this->getStoreIterator()->simulateStore($store);

        return $this;
    }


    /**
     * @return int
     */
    protected function getSelectedStore()
    {
        return (int) $this->_getSession()->getData('simulated_store_id');
    }

    /**
     * @return bool
     */
    protected function validateStoreSelection()
    {
        if ($this->getSelectedStore() == Mage_Core_Model_App::ADMIN_STORE_ID) {
            return false;
        }

        return true;
    }

    
    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function redirectOrdersGrid()
    {
        return $this->_redirect(
            'adminhtml/sales_order/index',
            array('_current' => true)
        );
    }
    
    
    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function redirectBack()
    {
        $this->_getSession()
             ->setData('order_codes', $this->getCleanedOrderCodes());
        
        return $this->_redirectReferer();
    }
}
