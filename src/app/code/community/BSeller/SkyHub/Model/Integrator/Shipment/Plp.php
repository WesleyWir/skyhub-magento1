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
 */

class BSeller_SkyHub_Model_Integrator_Shipment_Plp extends BSeller_SkyHub_Model_Integrator_Abstract
{
    /**
     * @return bool|\SkyHub\Api\Handler\Response\HandlerInterface
     */
    public function getOrdersAvailableToGroup()
    {
        /** @var \SkyHub\Api\EntityInterface\Shipment\Plp $interface */
        $interface = $this->getEntityInterface();
        $result    = $interface->ordersReadyToGroup();

        if ($result->exception() || $result->invalid()) {
            return false;
        }

        /** @var \SkyHub\Api\Handler\Response\HandlerDefault $ordersToGroup */
        $ordersToGroup = $result->toArray();

        if (empty($ordersToGroup) || !isset($ordersToGroup['orders'])) {
            return false;
        }

        return (array) $ordersToGroup['orders'];
    }


    /**
     * @param $orders
     * 
     * @return array|bool
     */
    public function group($orders)
    {
        /** @var \SkyHub\Api\EntityInterface\Shipment\Plp $interface */
        $interface = $this->getEntityInterface();

        foreach ($orders as $order) {
            $interface->addOrder($order);
        }

        $result = $interface->group();

        if ($result->exception() || $result->invalid()) {
            return false;
        }

        /** @var \SkyHub\Api\Handler\Response\HandlerDefault $data */
        $data = $result->toArray();

        if (empty($data)) {
            return false;
        }

        return (array) $data;
    }


    /**
     * @return \SkyHub\Api\EntityInterface\Shipment\Plp
     */
    protected function getEntityInterface()
    {
        return $this->api()->plp()->entityInterface();
    }
}