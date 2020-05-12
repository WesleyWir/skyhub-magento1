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
 * @author    Bruno Gemelli <bruno.gemelli@e-smart.com.br>
 * @author    Tiago Sampaio <tiago.sampaio@e-smart.com.br>
 * @author    Luiz Tucillo <luiz.tucillo@e-smart.com.br>
 * @author    Jefferson Porto <jefferson.porto@e-smart.com.br>
 */

class BSeller_SkyHub_Model_Processor_Sales_Order extends BSeller_SkyHub_Model_Processor_Abstract
{
    /** @var string */
    const ADDRESS_TYPE_BILLING  = 'billing_address';

    /** @var string */
    const ADDRESS_TYPE_SHIPPING = 'shipping_address';

    /** @var boolean */
    private $saveCustomer = false;

    /** @var array|AddressInterface[] */
    protected $addresses = [
        self::ADDRESS_TYPE_BILLING  => null,
        self::ADDRESS_TYPE_SHIPPING => null,
    ];

    use BSeller_SkyHub_Trait_Sales_Order;
    use BSeller_SkyHub_Trait_Customer_Attribute_Mapping,
        BSeller_SkyHub_Trait_Config_General,
        BSeller_SkyHub_Trait_Customer_Attribute;

    /**
     * @param array $data
     *
     * @return bool|Mage_Sales_Model_Order
     */
    public function createOrder(array $data)
    {
        try {

            Mage::register('bseller_skyhub_process_order_creation', true, true);

            /** @var Mage_Sales_Model_Order $order */
            $order = $this->processOrderCreation($data);

            if ($order && $order->getId()) {
                $this->updateOrderStatus($data, $order);
            }

        } catch (BSeller_SkyHub_Exceptions_UnprocessableException $e) {
            Mage::dispatchEvent(
                'bseller_skyhub_order_import_exception',
                array(
                    'exception' => $e,
                    'order_data' => $data,
                )
            );

            throw $e;
        } catch (Exception $e) {

            $this->removeOrderQuote();

            Mage::dispatchEvent(
                'bseller_skyhub_order_import_exception',
                array(
                    'exception' => $e,
                    'order_data' => $data,
                )
            );

            Mage::logException($e);

            return false;
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
        $orderId     = $this->getOrderId($code);
        $status      = $this->arrayExtract($data, 'status/type');
        $order       = false;
        if ($orderId) {
            /**
             * @var Mage_Sales_Model_Order $order
             *
             * Order already exists.
             */
            $order = Mage::getModel('sales/order')->load($orderId);
        } else if ($status == "CANCELED") {
            $exceptText = $code . ' ' . $this->__("Order doesn't create, because status is CANCELED");
            throw new BSeller_SkyHub_Exceptions_UnprocessableException($exceptText);
        }

        //$this->simulateStore($this->getStore());

        $billingAddress  = new Varien_Object($this->arrayExtract($data, 'billing_address'));
        $shippingAddress = new Varien_Object($this->arrayExtract($data, 'shipping_address'));

        $customerData = (array) $this->arrayExtract($data, 'customer', array());
        $customerData = array_merge_recursive(
            $customerData,
            array(
                'billing_address' => $billingAddress,
                'shipping_address' => $shippingAddress
            )
        );

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $this->getCustomer($customerData);
        $this->assignAddressToCustomer($customerData, $customer);
        
        $shippingCarrier = (string) $this->arrayExtract($data, 'shipping_carrier');
        $shippingCarrier  = $this->_getShippingCarrierConfig($shippingCarrier, $channel);
        $shippingMethod  = (string) $this->arrayExtract($data, 'shipping_method');
        $shippingMethod  = $this->_getShippingMethodConfig($shippingMethod, $channel);
        $shippingCost    = (float)  $this->arrayExtract($data, 'shipping_cost', 0.0000);
        $discountAmount  = (float)  $this->arrayExtract($data, 'discount', 0.0000);
        $interestAmount  = (float)  $this->arrayExtract($data, 'interest', 0.0000);

        if ($order) {
            $order  ->setCustomerEmail($customer->getEmail())
                    ->setCustomerFirstname($customer->getFirstname())
                    ->setCustomerMiddlename($customer->getMiddlename())
                    ->setCustomerLastname($customer->getLastname())
                    ->setCustomerGender($customer->getGender());

            $this->updateOrderAddressData($customer, $order->getBillingAddress(), $this->getBillingAddress());
            $this->updateOrderAddressData($customer, $order->getShippingAddress(), $this->getShippingAddress());
            
            $order->save();

            return $order;
        }

        /** @var BSeller_SkyHub_Model_Support_Sales_Order_Create $creation */
        $creation = Mage::getModel('bseller_skyhub/support_sales_order_create', $this->getStore());

        $incrementId = $this->getNewOrderIncrementId($code);
        $info = new Varien_Object(
            array(
                'increment_id' => $incrementId,
                'send_confirmation' => 0
            )
        );

        $creation->setOrderInfo($info)
            ->setCustomer($customer)
            ->setShippingMethod($shippingMethod, $shippingCarrier, (float) $shippingCost)
            ->setPaymentMethod('bseller_skyhub_standard')
            ->setDiscountAmount($discountAmount)
            ->setInterestAmount($interestAmount)
            ->addOrderAddress('billing', $billingAddress, $channel)
            ->addOrderAddress('shipping', $shippingAddress, $channel)
            ->setComment('This order was automatically created by SkyHub import process.')
            ->setCustomData(
                array(
                    'bseller_skyhub'            => true,
                    'bseller_skyhub_code'       => $code,
                    'bseller_skyhub_channel'    => $channel,
                    'bseller_skyhub_json'       => Mage::helper('core')->jsonEncode($data),
                    //Bizcommerce_SkyHub uses these fields
                    'skyhub_code'               => $code,
                    'skyhub_marketplace'        => $channel
                )
            );

        $products = $this->getProducts((array) $this->arrayExtract($data, 'items'));
        if (empty($products)) {
            Mage::throwException($this->__('The SkyHub products cannot be matched with Magento products.'));
        }

        /** @var array $productData */
        foreach ((array) $products as $productData) {
            $creation->addProduct($productData);
        }

        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = $creation->create();
        } catch (Mage_Exception $e) {
            Mage::logException($e);
        }


        if (!$order) {
            //throw excpetion
            return false;
        }

        $order->getResource()->save($order);

        $order->setData('is_created', true);

        return $order;
    }
    
    /**
     * Return Config methodShipping
     *
     * @return bool|array
     */
    protected function _getMethodShippingConfig()
    {
        $config = Mage::getStoreConfig('bseller_skyhub/methodShipping/marketplaces');
        if (!$config) {
            return false;
        }
        return unserialize($config);
    }

    /**
     * Return Method Shipping Default
     *
     * @param string $shippingMethod
     * @return string
     */
    protected function _getShippingMethodConfig($shippingMethod, $channel)
    {
        $config = $this->_getMethodShippingConfig();
        if (!$config) {
            return $shippingMethod;
        }

        foreach ($config as $value) {
            if ($channel != $value['channel']) {
                continue;
            }
            return $value['method_shipping_default'];
        }
        return $shippingMethod;
    }

    /**
     * Return Carrier Shipping Default
     *
     * @param string $carrierMethod
     * @return string
     */
    protected function _getShippingCarrierConfig($shippingCarrier, $channel)
    {
        $config = $this->_getMethodShippingConfig();
        if (!$config) {
            return $shippingCarrier;
        }
        
        foreach ($config as $value) {
            if ($channel != $value['channel']) {
                continue;
            }
            return $value['carrier_shipping_default'];
        }
        return $shippingCarrier;
    }

    /**
     * @return AddressInterface|mixed
     */
    protected function getBillingAddress()
    {
        /** @todo Create a logic to retrieve this address when address was not created in this process. */
        $address = $this->addresses[self::ADDRESS_TYPE_BILLING];

        if (empty($address)) {
            $address = $this->addresses[self::ADDRESS_TYPE_SHIPPING];
        }

        return $address;
    }

    /**
     * @return AddressInterface|mixed
     */
    protected function getShippingAddress()
    {
        /** @todo Create a logic to retrieve this address when address was not created in this process. */
        $address = $this->addresses[self::ADDRESS_TYPE_SHIPPING];

        if (empty($address)) {
            $address = $this->addresses[self::ADDRESS_TYPE_BILLING];
        }

        return $address;
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
             ->processOrderStatus($skyhubStatusCode, $skyhubStatusType, $order, $skyhubOrderData);
        
        return $this;
    }
    
    /**
     * @param Mage_Customer_Model_Customer $customer
     * @param Mage_Sales_Model_Quote_Address $orderAddress
     * @param Varien_Object $address
     */
    protected function updateOrderAddressData(
        Mage_Customer_Model_Customer $customer,
        $orderAddress,
        $address
    ) {
        $orderAddress
            ->setEmail($customer->getEmail())
            ->setFirstname($address->getFirstname())
            ->setMiddlename($address->getMiddlename())
            ->setLastname($address->getLastname())
            ->setStreet($address->getStreet())
            ->setTelephone($address->getTelephone())
            ->setPostcode($address->getPostcode())
            ->setCity($address->getCity())
            ->setRegion($address->getRegionCode())
            ->setRegionId($address->getRegionId());
    }
    
    /**
     * @param array $items
     *
     * @return array
     */
    protected function getProducts(array $items)
    {
        $products = array();
        
        foreach ($items as $item) {
            $parentSku    = $this->arrayExtract($item, 'product_id');
            $childSku     = $this->arrayExtract($item, 'id');
            $qty          = $this->arrayExtract($item, 'qty');
            
            $price        = (float) $this->arrayExtract($item, 'original_price');
            if (!$productId = $this->getProductIdBySku($parentSku)) {
                continue;
            }
    
            $data = array(
                'product_id'    => (int)    $productId,
                'product_sku'   => (string) $parentSku,
                'qty'           => (float)  ($qty ? $qty : 1),
                'price'         => (float)  $price,
                'special_price' => (float)  $price,
                'final_price'   => (float)  $price
            );
    
            if ($childId = $this->getProductIdBySku($childSku)) {
                $data['children'][] = array(
                    'product_id'  => (int)    $childId,
                    'product_sku' => (string) $childSku,
                );
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
        $taxvat = $this->arrayExtract($data, 'vat_number');

        if (!$email || strpos($email, '@') === false) {
            if ($this->allowCustomerEmailCreationWithTaxvat()) {
                $vatNumber = $this->arrayExtract($data, 'vat_number');
                $email = $vatNumber . $this->customerEmailCreationWithTaxvatPattern();
                $data['email'] = $email;
            }
        }

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $this->getCustomerByEmailOrTaxvat($email, $taxvat);
        
        if ($this->saveCustomer) {
            $this->createCustomer($data, $customer);
        }
        
        return $customer;
    }

    protected function getCustomerByEmailOrTaxvat($email, $taxvat)
    {
        $this->saveCustomer = false;
        $websiteId = $this->getStore()->getWebsiteId();
        $patternEmailWithTaxvat = $this->customerEmailCreationWithTaxvatPattern();
     
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->setStore($this->getStore());
        $customer->loadByEmail($email);

        $emailDefault = $taxvat . $patternEmailWithTaxvat;
        if ($customer->getId()) {
            if ($customer->getEmail() == $emailDefault) {
                $this->saveCustomer = true;
            }
            return $customer;
        }

        $customerCollection = Mage::getModel('customer/customer')->getCollection();
        $customerCollection->addAttributeToSelect('*')
                 ->addAttributeToFilter('taxvat', ['eq' => $taxvat])
                 ->addFieldToFilter('website_id', $websiteId)
                 ->load();
        $customer = $customerCollection->getFirstItem();
        if (!$customer->getId()) {
            $this->saveCustomer = true;
            return $customer;
        }

        if (strpos($customer->getEmail(), $patternEmailWithTaxvat) !== false) {
            $this->saveCustomer = true;
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
        $customer->setStore($this->getStore());

        $dateOfBirth = $this->arrayExtract($data, 'date_of_birth');
        $email       = $this->arrayExtract($data, 'email');
        $gender      = $this->arrayExtract($data, 'gender');
        $name        = $this->arrayExtract($data, 'name');
        $phones      = $this->arrayExtract($data, 'phones', array());
        
        /** @var Varien_Object $nameObject */
        $nameObject = $this->breakName($name);
        
        $customer->setFirstname($nameObject->getData('firstname'));
        $customer->setLastname($nameObject->getData('lastname'));
        $customer->setMiddlename($nameObject->getData('middlename'));
        $customer->setEmail($email);
        $customer->setDob($dateOfBirth);

        $this->setPersonTypeInformation($data, $customer);
        
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

        return $customer;
    }

    /**
     * @param array $data
     * @param Mage_Customer_Model_Customer $customer
     */
    public function assignAddressToCustomer(array $data, Mage_Customer_Model_Customer $customer)
    {
        try {
            /** @var Varien_Object $billing */
            if ($shipping = $this->arrayExtract($data, 'shipping_address')) {
                $address = $this->createCustomerAddress(
                    $shipping,
                    $customer,
                    null,
                    self::ADDRESS_TYPE_SHIPPING
                );
                $this->pushAddress($address, self::ADDRESS_TYPE_SHIPPING);
            }

            /** @var Varien_Object $billing */
            if ($billing = $this->arrayExtract($data, 'billing_address')) {
                $address = $this->createCustomerAddress(
                    $billing,
                    $customer,
                    $shipping ? $shipping : null,
                    self::ADDRESS_TYPE_BILLING
                );
                $this->pushAddress($address, self::ADDRESS_TYPE_BILLING);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param Mage_Customer_Model_Address $address
     * @param string           $type
     *
     * @return $this
     */
    protected function pushAddress($address, $type)
    {
        $this->addresses[$type] = $address;
        return $this;
    }

    /**
     * @param Varien_Object $addressObject
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return bool
     */
    protected function canRegisterAddress(Varien_Object $addressObject, Mage_Customer_Model_Customer $customer)
    {
        $addressSize = $this->getAddressSizeConfig();
        $simpleAddressData = $this->formatAddress($addressObject, $addressSize);

        /** @var Mage_Customer_Model_Resource_Customer_Collection $collection*/
        $collection = Mage::getResourceModel('customer/address_collection');
        $collection->addAttributeToFilter('street', array('eq' => $simpleAddressData))
                   ->addAttributeToFilter('parent_id', array('eq' => $customer->getId()))
                   ->addAttributeToFilter('postcode', array('eq' => $addressObject->getPostcode()));
        if ($collection->getSize()) {
            return false;
        }

        return true;
    }

    /**
     * @param Varien_Object $addressObject
     * @param Mage_Customer_Model_Customer $customer
     * @param Varien_Object|null $fallbackAddress
     * @param string $type
     *
     * @throws Exception
     * @return Mage_Customer_Model_Address|void
     */
    protected function createCustomerAddress(
        Varien_Object $addressObject,
        Mage_Customer_Model_Customer $customer,
        Varien_Object $fallbackAddress = null,
        $type
    ) {
        /** @var BSeller_SkyHub_Model_Support_Sales_Order_Create $creation */
        $creation = Mage::getSingleton('bseller_skyhub/support_sales_order_create');
        $addressSize = $this->getAddressSizeConfig();
        $simpleAddressData = $this->formatAddress($addressObject, $addressSize, $fallbackAddress);

        $fullname = trim($addressObject->getData('full_name'));

        /** @var Varien_Object $nameObject */
        $nameObject = $this->breakName($fullname);

        /** @var Mage_Customer_Model_Address $address */
        $address = Mage::getSingleton('customer/address');

        $currentAddress = false;
        if ($type === self::ADDRESS_TYPE_BILLING) {
            $currentAddress = $customer->getDefaultBillingAddress();
        } elseif ($type === self::ADDRESS_TYPE_SHIPPING) {
            $currentAddress = $customer->getDefaultShippingAddress();
        }

        if ($currentAddress && ($currentAddress->getPostcode() === $addressObject->getData('postcode'))) {
            return $currentAddress;
        }
        
        if ($currentAddress && $currentAddress->getPostcode() === '00000000') {
            $address = $currentAddress;
        }

        $address->setData(
            array(
                'firstname' => $nameObject->getData('firstname'),
                'middlename' => $nameObject->getData('middlename'),
                'lastname' => $nameObject->getData('lastname'),
                'suffix' => '',
                'company' => '',
                'street' => $simpleAddressData,
                'city' => $addressObject->getData('city'),
                'country_id' => $addressObject->getData('country'),
                'region' => $creation->getRegion($addressObject),
                'region_id' => $creation->getRegionId($addressObject),
                'postcode' => $addressObject->getData('postcode'),
                'telephone' => $this->formatPhone($addressObject->getData('phone')),
                'fax' => $addressObject->getData('secondary_phone'),
                'save_in_address_book' => '1',
                'is_default_shipping' => true,
                'is_default_billing' => true
            )
        );

        if (!$this->canRegisterAddress($addressObject, $customer)) {
            return $address;
        }

        if ($customer->getAddresses() && $addressObject->getData('postcode') == '00000000') {
            return $address;
        }

        if ($this->allowRegisterCustomerAddress()) {
            $address->setCustomer($customer)->save();
        }

        return $address;
    }

    /**
     * Remove quote in case of order creation exception.
     */
    protected function removeOrderQuote()
    {
        /** @var BSeller_SkyHub_Model_Support_Sales_Order_Create $creation */
        $creation = Mage::getSingleton('bseller_skyhub/support_sales_order_create');
        return $creation->removeSessionQuote();
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
        $store = Mage::app()->getStore();
        if ($store->isAdmin()) {
            $store = Mage::app()->getDefaultStoreView();
        }

        return $store;
    }
    
    /**
     * @param string $code
     *
     * @return string
     */
    protected function getOrderIncrementId($code)
    {
        return $code;
    }

    /**
     * @param $data
     * @param $customer
     *
     * @return void
     */
    protected function setPersonTypeInformation($data, $customer)
    {
        //get the vat number
        $vatNumber = $this->arrayExtract($data, 'vat_number');
        //the taxvat is filled anyway
        $customer->setTaxvat($vatNumber);
        //check if is a PJ customer (if not, it's a PF customer)
        $customerIsPj = $this->customerIsPj($vatNumber);

        //get customer mapped attributes
        $mappedCustomerAttributes = $this->getMappedAttributes();

        //if the store has the attribute "person_type" mapped
        if (isset($mappedCustomerAttributes['person_type'])) {
            $personTypeAttributeId = $mappedCustomerAttributes['person_type']->getAttributeId();
            $personTypeAttribute = $this->getAttributeById($personTypeAttributeId);

            if ($customerIsPj) {
                $personTypeAttributeValue = $this->getAttributeMappingOptionMagentoValue('person_type', 'legal_person');
            } else {
                $personTypeAttributeValue = $this->getAttributeMappingOptionMagentoValue('person_type', 'physical_person');
            }
            $customer->setData($personTypeAttribute->getAttributeCode(), $personTypeAttributeValue);
        }

        if ($customerIsPj) {
            //set the mapped PJ attribute value on customer if exists
            if (isset($mappedCustomerAttributes['cnpj'])) {
                $mappedAttribute = $mappedCustomerAttributes['cnpj'];
                $attribute = $this->getAttributeById($mappedAttribute->getAttributeId());
                $customer->setData($attribute->getAttributeCode(), $vatNumber);
            }
        } else {
            //set the mapped PF attribute value on customer if exists
            if (isset($mappedCustomerAttributes['cpf'])) {
                $mappedAttribute = $mappedCustomerAttributes['cpf'];
                $attribute = $this->getAttributeById($mappedAttribute->getAttributeId());
                $customer->setData($attribute->getAttributeCode(), $vatNumber);
            }
        }

        //set the mapped IE attribute value on customer if exists
        if (isset($mappedCustomerAttributes['ie'])) {
            $mappedAttribute = $mappedCustomerAttributes['ie'];
            $attribute = $this->getAttributeById($mappedAttribute->getAttributeId());
            $customer->setData($attribute->getAttributeCode(), $this->arrayExtract($data, 'state_registration'));
        }

        //set the mapped IE attribute value on customer if exists
        if (isset($mappedCustomerAttributes['social_name'])) {
            $mappedAttribute = $mappedCustomerAttributes['social_name'];
            $attribute = $this->getAttributeById($mappedAttribute->getAttributeId());
            $customer->setData($attribute->getAttributeCode(), $this->arrayExtract($data, 'name'));
        }
    }
}
