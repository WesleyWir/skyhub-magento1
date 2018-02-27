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
class BSeller_SkyHub_Adminhtml_Bseller_Skyhub_Sales_Order_ImportController
    extends BSeller_SkyHub_Controller_Admin_Action
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
    
    
    public function formAction()
    {
        $this->init('Import Manually');
        $this->renderLayout();
    }
    
    
    public function submitAction()
    {
        $codes = $this->getCleanedOrderCodes();
        
        if (empty($codes)) {
            $this->redirectBack();
            return;
        }
        
        /** @var string $code */
        foreach ($codes as $code) {
            /** @var bool|array $orderData */
            $orderData = $this->getOrderIntegrator()->order($code);
            
            if (!$orderData) {
                $this->_getSession()->addError($this->__('Order code %s does not exist in SkyHub.', $code));
                continue;
            }
            
            /** @var bool|Mage_Sales_Model_Order $order */
            $order = $this->getOrderProcessor()->createOrder($orderData);
            
            if (false === $order) {
                $this->_getSession()->addError($this->__('The order %s cannot be created in Magento.', $code));
                continue;
            }
            
            if (!$order->getData('is_created')) {
                $this->_getSession()->addWarning($this->__('The order %s already exists in Magento.', $code));
                continue;
            }
    
            $this->_getSession()->addSuccess($this->__('The order %s was successfully created.', $code));
        }
        
        $this->redirectOrdersGrid();
    }
    
    
    public function logAction()
    {
        $this->init('Import Log');
        $this->renderLayout();
    }
    
    
    /**
     * @return BSeller_SkyHub_Model_Processor_Sales_Order
     */
    protected function getOrderProcessor()
    {
        /** @var BSeller_SkyHub_Model_Processor_Sales_Order $processor */
        $processor = Mage::getModel('bseller_skyhub/processor_sales_order');
        return $processor;
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
        
        $cleanedCodes = [];
        
        foreach ($orderCodes as $orderCode) {
            if (empty($orderCode)) {
                continue;
            }
            
            $cleanedCodes[] = trim($orderCode);
        }
        
        return $cleanedCodes;
    }
    
    
    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function redirectOrdersGrid()
    {
        return $this->_redirect('adminhtml/sales_order/index', ['_current' => true]);
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
