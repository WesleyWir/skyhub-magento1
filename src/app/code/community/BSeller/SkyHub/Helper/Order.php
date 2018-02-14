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

class BSeller_SkyHub_Helper_Order extends BSeller_Core_Helper_Data
{
    
    private $_storeId = '1';
    
    private $_groupId = '1';
    
    private $_sendConfirmation = '0';
    
    private $orderData = array();
    
    /** @var Mage_Catalog_Model_Product */
    private $_product;
    
    private $_sourceCustomer;
    
    private $_sourceOrder;
    
    
    /**
     * @param Varien_Object                $sourceOrder
     * @param Mage_Customer_Model_Customer $sourceCustomer
     */
    public function setOrderInfo(Varien_Object $sourceOrder, Mage_Customer_Model_Customer $sourceCustomer)
    {
        $this->_sourceOrder = $sourceOrder;
        $this->_sourceCustomer = $sourceCustomer;

        $productId = 1;
        
        $this->_product = Mage::getModel('catalog/product');
        $this->_product->load($productId);
        
        $this->orderData = array(
            'session'      => array(
                'customer_id' => $this->_sourceCustomer->getId(),
                'store_id'    => $this->_storeId,
            ),
            'payment'      => array(
                'method' => 'checkmo',
            ),
            'add_products' => array(
                $this->_product->getId() => array('qty' => 1),
            ),
            'order'        => array(
                'currency'          => 'USD',
                'account'           => array(
                    'group_id' => $this->_groupId,
                    'email'    => $this->_sourceCustomer->getEmail()
                ),
                'billing_address'   => array(
                    'customer_address_id' => $this->_sourceCustomer->getCustomerAddressId(),
                    'prefix'              => '',
                    'firstname'           => $this->_sourceCustomer->getFirstname(),
                    'middlename'          => '',
                    'lastname'            => $this->_sourceCustomer->getLastname(),
                    'suffix'              => '',
                    'company'             => '',
                    'street'              => $sourceOrder->getData('billing_address/street'),
                    'city'                => $sourceOrder->getData('billing_address/city'),
                    'country_id'          => 'BR',
                    'region'              => $sourceOrder->getData('billing_address/region'),
                    'region_id'           => '',
                    'postcode'            => $sourceOrder->getData('billing_address/postcode'),
                    'telephone'           => $sourceOrder->getData('billing_address/phone'),
                    'fax'                 => '',
                ),
                'shipping_address'  => array(
                    'customer_address_id' => $this->_sourceCustomer->getCustomerAddressId(),
                    'prefix'              => '',
                    'firstname'           => $this->_sourceCustomer->getFirstname(),
                    'middlename'          => '',
                    'lastname'            => $this->_sourceCustomer->getLastname(),
                    'suffix'              => '',
                    'company'             => '',
                    'street'              => $sourceOrder->getData('shipping_address/street'),
                    'city'                => $sourceOrder->getData('shipping_address/city'),
                    'country_id'          => 'BR',
                    'region'              => $sourceOrder->getData('shipping_address/region'),
                    'region_id'           => '',
                    'postcode'            => $sourceOrder->getData('shipping_address/postcode'),
                    'telephone'           => $sourceOrder->getData('shipping_address/phone'),
                    'fax'                 => '',
                ),
                'shipping_method'   => 'flatrate_flatrate',
                'comment'           => array(
                    'customer_note' => 'This order has been programmatically created via import script.',
                ),
                'send_confirmation' => $this->_sendConfirmation
            ),
        );
        
        return $this;
    }
    
    
    /**
     * Retrieve order create model
     *
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    protected function _getOrderCreateModel()
    {
        /** @var Mage_Adminhtml_Model_Sales_Order_Create $create */
        $create = Mage::getSingleton('adminhtml/sales_order_create');
        return $create;
    }
    
    
    /**
     * Retrieve session object
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    protected function _getSession()
    {
        /** @var Mage_Adminhtml_Model_Session_Quote $session */
        $session = Mage::getSingleton('adminhtml/session_quote');
        return $session;
    }
    
    
    /**
     * Initialize order creation session data
     *
     * @param array $data
     *
     * @return $this
     */
    protected function _initSession($data)
    {
        /* Get/identify customer */
        if (!empty($data['customer_id'])) {
            $this->_getSession()->setCustomerId((int) $data['customer_id']);
        }
        
        /* Get/identify store */
        if (!empty($data['store_id'])) {
            $this->_getSession()->setStoreId((int) $data['store_id']);
        }
        
        return $this;
    }
    
    
    /**
     * Creates order
     */
    public function create()
    {
        $orderData = $this->orderData;
        
        if (!empty($orderData)) {
            
            $this->_initSession($orderData['session']);
            
            try {
                $this->_processQuote($orderData);
                if (!empty($orderData['payment'])) {
                    $this->_getOrderCreateModel()->setPaymentData($orderData['payment']);
                    $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($orderData['payment']);
                }
                
                $item = $this->_getOrderCreateModel()->getQuote()->getItemByProduct($this->_product);
                
                /*
                $item->addOption(new Varien_Object(
                                     array(
                                         'product' => $this->_product,
                                         'code'    => 'option_ids',
                                         'value'   => '5'
                                         /* Option id goes here. If more options, then comma separate * /
                                     )
                                 ));

                $item->addOption(new Varien_Object(
                                     array(
                                         'product' => $this->_product,
                                         'code'    => 'option_5',
                                         'value'   => 'Some value here'
                                     )
                                 ));
                */
                
                Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, "0");
                
                $_order = $this->_getOrderCreateModel()
                               ->importPostData($orderData['order'])
                               ->createOrder();
                
                $this->_getSession()->clear();
                Mage::unregister('rule_data');
                
                return $_order;
            } catch (Exception $e) {
                Mage::log("Order save error...");
            }
        }
        
        return null;
    }
    
    
    /**
     * @param array $data
     *
     * @return $this
     */
    protected function _processQuote($data = array())
    {
        /* Saving order data */
        if (!empty($data['order'])) {
            $this->_getOrderCreateModel()->importPostData($data['order']);
        }
        
        $this->_getOrderCreateModel()->getBillingAddress();
        $this->_getOrderCreateModel()->setShippingAsBilling(true);
        
        /* Just like adding products from Magento admin grid */
        if (!empty($data['add_products'])) {
            $this->_getOrderCreateModel()->addProducts($data['add_products']);
        }
        
        /* Collect shipping rates */
        $this->_getOrderCreateModel()->collectShippingRates();
        
        /* Add payment data */
        if (!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($data['payment']);
        }
        
        $this->_getOrderCreateModel()
             ->initRuleData()
             ->saveQuote();
        
        if (!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($data['payment']);
        }
        
        return $this;
    }
}