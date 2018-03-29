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

class BSeller_SkyHub_Model_Processor_Sales_Order extends BSeller_SkyHub_Model_Processor_Abstract
{

    /**
     * @param array $data
     *
     * @return bool|Mage_Sales_Model_Order
     */
    public function createOrder(array $data)
    {
        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = $this->processOrderCreation($data);
        } catch (Exception $e) {
            Mage::dispatchEvent('bseller_skyhub_order_import_exception', [
                'exception'  => $e,
                'order_data' => $data,
            ]);

            Mage::logException($e);

            return false;
        }

        if ($order && $order->getId()) {
            $this->updateOrderStatus($data, $order);
        }

        return $order;
    }


    /**
     * @param array $data
     *
     * @return bool|Mage_Sales_Model_Order
     *
     * @throws Exception
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function processOrderCreation(array $data)
    {
        $code        = $this->arrayExtract($data, 'code');
        $channel     = $this->arrayExtract($data, 'channel');
        $orderId = $this->getOrderId($code);

        if ($orderId) {
            /**
             * @var Mage_Sales_Model_Order $order
             *
             * Order already exists.
             */
            $order = Mage::getModel('sales/order')->load($orderId);
            return $order;
        }

        $this->simulateStore($this->getStore());

        $billingAddress  = new Varien_Object($this->arrayExtract($data, 'billing_address'));
        $shippingAddress = new Varien_Object($this->arrayExtract($data, 'shipping_address'));

        $customerData = (array) $this->arrayExtract($data, 'customer', []);
        $customerData = array_merge_recursive($customerData, [
            'billing_address'  => $billingAddress,
            'shipping_address' => $shippingAddress
        ]);

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $this->getCustomer($customerData);

        $shippingCarrier = (string) $this->arrayExtract($data, 'shipping_carrier');
        $shippingMethod  = (string) $this->arrayExtract($data, 'shipping_method');
        $shippingCost    = (float)  $this->arrayExtract($data, 'shipping_cost', 0.0000);
        $discountAmount  = (float)  $this->arrayExtract($data, 'discount', 0.0000);
        $interestAmount  = (float)  $this->arrayExtract($data, 'interest', 0.0000);

        /** @var BSeller_SkyHub_Model_Support_Sales_Order_Create $creation */
        $creation = Mage::getModel('bseller_skyhub/support_sales_order_create', $this->getStore());

        $incrementId = $this->getOrderIncrementId($creation->getQuote(), $code);
        $info = new Varien_Object([
            'increment_id' => $incrementId,
            'send_confirmation' => 0
        ]);

        $creation->setOrderInfo($info)
            ->setCustomer($customer)
            ->setShippingMethod($shippingMethod, $shippingCarrier, (float) $shippingCost)
            ->setPaymentMethod('bseller_skyhub_standard')
            ->setDiscountAmount($discountAmount)
            ->setInterestAmount($interestAmount)
            ->addOrderAddress('billing', $billingAddress)
            ->addOrderAddress('shipping', $shippingAddress)
            ->setComment('This order was automatically created by SkyHub import process.')
        ;

        $products = $this->getProducts((array) $this->arrayExtract($data, 'items'));
        if (empty($products)) {
            Mage::throwException($this->__('The SkyHub products cannot be matched with Magento products.'));
        }

        /** @var array $productData */
        foreach ((array) $products as $productData) {
            $creation->addProduct($productData);
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $creation->create();

        if (!$order) {
            return false;
        }

        $order->setData('bseller_skyhub', true);
        $order->setData('bseller_skyhub_code', $code);
        $order->setData('bseller_skyhub_channel', $channel);

        /** Bizcommerce_SkyHub uses these fields. */
        $order->setData('skyhub_code', $code);
        $order->setData('skyhub_marketplace', $channel);

        $order->getResource()->save($order);

        $order->setData('is_created', true);

        return $order;
    }
    
    
    /**
     * @param array                  $skyhubOrderData
     * @param Mage_Sales_Model_Order $order
     *
     * @return $this
     */
    protected function updateOrderStatus(array $skyhubOrderData, Mage_Sales_Model_Order $order)
    {
        $skyhubStatusCode = $this->arrayExtract($skyhubOrderData, 'code');
        $skyhubStatusType = $this->arrayExtract($skyhubOrderData, 'status/type');
        
        $this->salesOrderStatusProcessor()
             ->processOrderStatus($skyhubStatusCode, $skyhubStatusType, $order);
        
        return $this;
    }
    
    
    /**
     * @param array $items
     *
     * @return array
     */
    protected function getProducts(array $items)
    {
        $products = [];
        
        foreach ($items as $item) {
            $parentSku    = $this->arrayExtract($item, 'product_id');
            $childSku     = $this->arrayExtract($item, 'id');
            $qty          = $this->arrayExtract($item, 'qty');
            
            $price        = (float) $this->arrayExtract($item, 'original_price');
            $specialPrice = (float) $this->arrayExtract($item, 'special_price');

            $finalPrice = $price;
            if (!empty($specialPrice)) {
                $finalPrice = $specialPrice;
            }
    
            if (!$productId = $this->getProductIdBySku($parentSku)) {
                continue;
            }
    
            $data = [
                'product_id'    => (int)    $productId,
                'product_sku'   => (string) $parentSku,
                'qty'           => (float)  ($qty ? $qty : 1),
                'price'         => (float)  $price,
                'special_price' => (float)  $specialPrice,
                'final_price'   => (float)  $finalPrice,
            ];
    
            if ($childId = $this->getProductIdBySku($childSku)) {
                $data['children'][] = [
                    'product_id'  => (int)    $childId,
                    'product_sku' => (string) $childSku,
                ];
            };

            $products[] = $data;
        }
        
        return $products;
    }
    
    
    /**
     * @param string $sku
     *
     * @return bool|false|BSeller_SkyHub_Model_Catalog_Product
     */
    protected function getProductBySku($sku)
    {
        $product   = Mage::getModel('bseller_skyhub/catalog_product');
        $productId = (int) $product->getResource()->getIdBySku($sku);
        
        if (!$productId) {
            return false;
        }
        
        $product->load($productId);
        return $product;
    }
    
    
    /**
     * @param string $sku
     *
     * @return false|int
     */
    protected function getProductIdBySku($sku)
    {
        $productId = Mage::getResourceSingleton('catalog/product')->getIdBySku($sku);
        return $productId;
    }
    
    
    /**
     * @param array                  $data
     *
     * @return Mage_Customer_Model_Customer
     *
     * @throws Exception
     */
    protected function getCustomer(array $data)
    {
        $email = $this->arrayExtract($data, 'email');
        
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->setStore($this->getStore());
        $customer->loadByEmail($email);
        
        if (!$customer->getId()) {
            $this->createCustomer($data, $customer);
        }
        
        return $customer;
    }
    
    
    /**
     * @param array                        $data
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return Mage_Customer_Model_Customer
     *
     * @throws Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function createCustomer(array $data, Mage_Customer_Model_Customer $customer)
    {
        $customer->setStore(Mage::app()->getStore());
        
        $dateOfBirth = $this->arrayExtract($data, 'date_of_birth');
        $email       = $this->arrayExtract($data, 'email');
        $gender      = $this->arrayExtract($data, 'gender');
        $name        = $this->arrayExtract($data, 'name');
        $vatNumber   = $this->arrayExtract($data, 'vat_number');
        $phones      = $this->arrayExtract($data, 'phones', []);
        
        /** @var Varien_Object $nameObject */
        $nameObject = $this->breakName($name);
        
        $customer->setFirstname($nameObject->getData('firstname'));
        $customer->setLastname($nameObject->getData('lastname'));
        $customer->setMiddlename($nameObject->getData('middlename'));
        $customer->setEmail($email);
        $customer->setDob($dateOfBirth);
        $customer->setTaxvat($vatNumber);
        
        /** @var string $phone */
        foreach ($phones as $phone) {
            $customer->setTelephone($phone);
            break;
        }
        
        switch ($gender) {
            case 'male':
                $customer->setGender(1);
                break;
            case 'female':
                $customer->setGender(2);
                break;
        }
        
        $customer->save();
        
        /** @var Varien_Object $billing */
        if ($billing = $this->arrayExtract($data, 'billing_address')) {
            $address = $this->createCustomerAddress($billing);
            $address->setCustomer($customer);
        }
        
        /** @var Varien_Object $billing */
        if ($shipping = $this->arrayExtract($data, 'shipping_address')) {
            $address = $this->createCustomerAddress($shipping);
            $address->setCustomer($customer);
        }
        
        return $customer;
    }
    
    
    /**
     * @param Varien_Object $addressObject
     *
     * @return Mage_Customer_Model_Address
     */
    protected function createCustomerAddress(Varien_Object $addressObject)
    {
        /** @var Mage_Customer_Model_Address $address */
        $address = Mage::getModel('customer/address');
        
        /**
         * @todo Create customer address algorithm based on $addressObject.
         */
        
        return $address;
    }


    /**
     * @param Mage_Core_Model_Store $store
     *
     * @return $this
     */
    protected function simulateStore(Mage_Core_Model_Store $store)
    {
        Mage::app()->setCurrentStore($store);
        return $this;
    }
    
    
    /**
     * @return Mage_Core_Model_Store
     */
    protected function getStore()
    {
        return $this->getNewOrdersDefaultStore();
    }
    
    
    /**
     * @param string $code
     *
     * @return string
     */
    protected function getOrderId($code)
    {
        /** @var BSeller_SkyHub_Model_Resource_Sales_Order $orderResource */
        $orderResource = Mage::getResourceModel('bseller_skyhub/sales_order');
        $orderId = $orderResource->getEntityIdBySkyhubCode($code);
        return $orderId;
    }

    protected function getOrderIncrementId($quote, $code)
    {
        $useDefaultIncrementId = $this->getSkyHubModuleConfig('use_default_increment_id', 'cron_sales_order_queue');
        if (!$useDefaultIncrementId) {
            return $code;
        }

        return $quote->getReservedOrderId();
    }
}
