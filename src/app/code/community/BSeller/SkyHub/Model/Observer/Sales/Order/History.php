<?php

class BSeller_SkyHub_Model_Observer_Sales_Order_History extends BSeller_SkyHub_Model_Observer_Abstract
{

    /**
     * @param Varien_Event_Observer $observer
     */
    public function sendTaxInvoiceKey(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Status_History $history */
        $history = $observer->getData('status_history');

        if (!$history || !$history->getId()) {
            return;
        }

        $comment = trim($history->getComment());
        if (empty($comment)) {
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        $order = $history->getOrder();

        if (!$order || !$order->getId()) {
            return;
        }

        $invoiceKeyNumber = $this->extractInvoiceKeyNumber($comment);

        if (empty($invoiceKeyNumber)) {
            return;
        }

        $result = $this->orderIntegrator()->invoice($order->getId(), $invoiceKeyNumber);
    }


    /**
     * @param $comment
     *
     * @return null|string
     */
    protected function extractInvoiceKeyNumber($comment)
    {
        $keyPattern = $this->getTaxInvoiceKeyPattern();

        if (!$this->validateKeyPattern($keyPattern)) {
            return null;
        }

        $number = null;

        preg_match('/.*?([0-9]{44}).*?/', $comment, $matches);

        if (!empty($matches) && isset($matches[1])) {
            $number = $matches[1];
        }

        return (string) $number;
    }


    /**
     * @param $pattern
     *
     * @return bool
     */
    protected function validateKeyPattern($pattern = null)
    {
        if (empty($pattern)) {
            return false;
        }

        return (bool) (strpos($pattern, '%d') != false);
    }

}
