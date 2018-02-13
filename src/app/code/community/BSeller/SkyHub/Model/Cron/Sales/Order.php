<?php

class BSeller_SkyHub_Model_Cron_Sales_Order
{

    use BSeller_SkyHub_Trait_Service;


    /**
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function importOrders(Mage_Cron_Model_Schedule $schedule)
    {
        $mock = '{
          "total": 1,
          "orders": [
            {
              "updated_at": "2018-02-12T19:23:48-02:00",
              "total_ordered": 123.45,
              "sync_status": "NOT_SYNCED",
              "status": {
                "type": "NEW",
                "label": "Pagamento Pendente (SkyHub)",
                "code": "book_product"
              },
              "shipping_method": "Correios PAC",
              "shipping_cost": 0,
              "shipping_address": {
                "street": "Rua Sacadura Cabral",
                "secondary_phone": "21 3722-3902",
                "region": "RJ",
                "reference": null,
                "postcode": "20081262",
                "phone": "21 3722-3902",
                "number": "130",
                "neighborhood": "Centro",
                "full_name": "Bruno santos",
                "detail": "foo",
                "country": "BR",
                "city": "Rio de Janeiro"
              },
              "shipments": [],
              "placed_at": "2018-02-12T19:23:48-02:00",
              "payments": [
                {
                  "value": 0,
                  "status": null,
                  "parcels": 1,
                  "method": "skyhub_payment",
                  "description": "Skyhub"
                }
              ],
              "items": [
                {
                  "special_price": 0,
                  "qty": 1,
                  "product_id": "PS4SLIM",
                  "original_price": 55.22,
                  "name": "Console Playstation 4 Slim 500GB",
                  "id": "PS4SLIM"
                }
              ],
              "invoices": [],
              "interest": 0,
              "import_info": {
                "remote_id": null,
                "remote_code": "1518470628274"
              },
              "estimated_delivery_shift": null,
              "estimated_delivery": "2018-02-10T22:00:00-02:00",
              "discount": 0,
              "customer": {
                "vat_number": "78732371683",
                "phones": [
                  "21 3722-3902",
                  "21 3722-3902"
                ],
                "name": "Bruno santos",
                "gender": "male",
                "email": "exemplo@skyhub.com.br",
                "date_of_birth": "1998-01-25"
              },
              "code": "TESTE-'.Mage::helper('core')->getRandomString(15, '1234567890').'",
              "channel": "Teste",
              "calculation_type": null,
              "billing_address": {
                "street": "Rua Fidencio Ramos",
                "secondary_phone": "21 3722-3902",
                "region": "RJ",
                "reference": null,
                "postcode": "04551101",
                "phone": "21 3722-3902",
                "number": "302",
                "neighborhood": "Centro",
                "full_name": "Bruno santos",
                "detail": "foo",
                "country": "BR",
                "city": "Rio de Janeiro"
              }
            }
          ]
        }';

        /** @var \SkyHub\Api\EntityInterface\Sales\Order\Queue $interface */
        // $interface = $this->api()->queue()->entityInterface();
        // $interface->orders();

        /** @var array $order */
        $orders = json_decode($mock, true);

        /** @var array $order */
        foreach ($orders['orders'] as $orderData) {
            /** @var Mage_Sales_Model_Order $order */
            $order = $this->createOrder($orderData);
            $order->save();
        }
    }


    /**
     * @param array $data
     *
     * @return Mage_Sales_Model_Order
     */
    protected function createOrder(array $data)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->setStoreId($this->getStore()->getId());

        $code = $data['code'];
        $channel = $data['channel'];
        $placedAt = $data['placed_at'];
        $updatedAt = $data['updated_at'];
        $totalOrdered = $data['total_ordered'];
        $interest = $data['interest'];
        $shippingCost = $data['shipping_cost'];
        $shippingMethod = $data['shipping_method'];
        $estimatedDelivery = $data['estimated_delivery'];

        $order->setGrandTotal((float) $totalOrdered);
        $order->setShippingAmount($shippingCost);
        $order->setIncrementId($code);

        /** Bind Customer */
        $customer = $this->bindCustomer($data['customer'], $order);

        /**
         * @var Mage_Sales_Model_Order_Address $billingAddress
         * @var Mage_Sales_Model_Order_Address $shippingAddress
         */
        $billingAddress  = $this->createAddress($data['billing_address'], $customer);
        $shippingAddress = $this->createAddress($data['shipping_address'], $customer);

        $order->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress);

        /** Create Order Items */
        $this->createItems($data['items'], $order);

        /** Create Order Payment */
        $this->createPayment($data['payments'], $order);

        return $order;

        /** @var Mage_Sales_Model_Quote $quote */
        // $incrementId = Mage::getModel('sales/quote')->reserveOrderId();
    }


    /**
     * @param array                  $data
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function createItems(array $data, Mage_Sales_Model_Order $order)
    {
        /** @var array $itemData */
        foreach ($data as $itemData) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $item         = Mage::getModel('sales/order_item');
            $productId    = $itemData['product_id'];
            $price        = (float) $itemData['original_price'];
            $specialPrice = (float) $itemData['special_price'];
            $finalPrice   = (float) ($price - $specialPrice);
            $discount     = (float) ($price * (($price / $finalPrice) - 1));
            $qty          = (float) $itemData['qty'];

            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product');
            $product->loadByAttribute('sku', $productId);

            $item->setName($product->getName());
            $item->setPrice((float) $price);
            $item->setRowTotal((float) $price);
            $item->setDiscountAmount((float) $discount);
            $item->setQtyOrdered((float) $qty);

            $order->addItem($item);
        }

        return $order;
    }


    /**
     * @param array                  $data
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     *
     * @throws Exception
     */
    protected function bindCustomer(array $data, Mage_Sales_Model_Order $order)
    {
        $email = $data['email'];

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->setStore($this->getStore());
        $customer->loadByEmail($email);

        if (!$customer->getId()) {
            $this->createCustomer($data, $customer);
            $customer->save();
        }

        $order->setCustomerId($customer->getId());
        $order->setCustomerDob($customer->getDob());
        $order->setCustomerEmail($customer->getEmail());
        $order->setCustomerFirstname($customer->getFirstname());
        $order->setCustomerLastname($customer->getLastname());
        $order->setCustomerGender($customer->getGender());

        return $order;
    }


    /**
     * @param array                        $data
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return Mage_Customer_Model_Customer
     */
    protected function createCustomer(array $data, Mage_Customer_Model_Customer $customer)
    {
        $customer->setStore(Mage::app()->getStore());

        $dateOfBirth = $data['date_of_birth'];
        $email       = $data['email'];
        $gender      = $data['gender'];
        $name        = $data['name'];
        $vatNumber   = $data['vat_number'];
        $phones      = $data['phones'];

        $names = (array) explode(' ', $name);

        $firstname  = ucwords(array_shift($names));
        $lastname   = ucwords(array_pop($names));
        $middlename = ucwords(implode(' ', $names));

        $customer->setFirstname($firstname);
        $customer->setLastname($lastname);
        $customer->setMiddlename($middlename);
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

        return $customer;
    }


    /**
     * @param array                  $paymentData
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function createPayment(array $paymentData = [], Mage_Sales_Model_Order $order)
    {
        foreach ($paymentData as $data) {
            /** @var Mage_Sales_Model_Order_Payment $payment */
            $payment = Mage::getModel('sales/order_payment');
            $payment->setMethod('checkmo');
            $order->setPayment($payment);

            break;
        }

        return $order;
    }


    protected function createShippingMethod()
    {

    }


    /**
     * @param array $data
     *
     * @return Mage_Sales_Model_Order_Address
     */
    protected function createOrderAddress(array $data)
    {
        /** @var Mage_Sales_Model_Order_Address $address */
        $address = Mage::getModel('sales/order_address');

        $countryId    = 'BR';
        $city         = $data['city'];
        $region       = $data['region'];
        $postcode     = $data['postcode'];
        $street       = $data['street'];
        $number       = $data['number'];
        $neighborhood = $data['neighborhood'];

        $address->setCountryId($countryId);
        $address->setCity($city);
        $address->setRegion($region);
        $address->setPostcode($postcode);
        $address->setStreet([
            $street,
            $number,
            $neighborhood
        ]);

        return $address;
    }


    /**
     * @return Mage_Core_Model_Store
     */
    protected function getStore()
    {
        return Mage::app()->getDefaultStoreView();
    }

}
