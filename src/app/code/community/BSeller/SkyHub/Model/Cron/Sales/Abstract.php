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

abstract class BSeller_SkyHub_Model_Cron_Sales_Abstract extends BSeller_SkyHub_Model_Cron_Abstract
{

    /**
     * @return BSeller_SkyHub_Model_Integrator_Sales_Order_Queue
     */
    protected function getOrderQueueIntegrator()
    {
        return Mage::getSingleton('bseller_skyhub/integrator_sales_order_queue');
    }

    /**
     * @return BSeller_SkyHub_Model_Integrator_Sales_Order
     */
    protected function getOrderIntegrator()
    {
        return Mage::getSingleton('bseller_skyhub/integrator_sales_order');
    }


    /**
     * @return BSeller_SkyHub_Model_Processor_Sales_Order
     */
    protected function getOrderProcessor()
    {
        return Mage::getModel('bseller_skyhub/processor_sales_order');
    }


    /**
     * @return BSeller_SkyHub_Model_Processor_Sales_Order_Status
     */
    protected function getOrderStatusProcessor()
    {
        return Mage::getModel('bseller_skyhub/processor_sales_order_status');
    }
}
