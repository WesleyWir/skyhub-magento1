<?php

class BSeller_SkyHub_Model_Observer_Sales_Order_Shipment extends BSeller_SkyHub_Model_Observer_Abstract
{

    /**
     * @param Varien_Event_Observer $observer
     */
    public function integrateOrderShipmentTracking(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        $track = $observer->getData('track');

        if (!$track || !$track->getId()) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getOrder($track->getOrderId());

        $items = [];

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = [
                'sku' => (string) $item->getSku(),
                'qty' => (int)    $item->getQtyOrdered(),
            ];
        }

        /** @var  $result */
        $result = $this->orderIntegrator()
            ->shipment(
                $order->getId(),
                $items,
                $track->getNumber(),
                $track->getCarrierCode(),
                '',
                ''
            );
    }


    /**
     * @param int $orderId
     *
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder($orderId)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);
        return $order;
    }

}
