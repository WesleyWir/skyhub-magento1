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

class BSeller_SkyHub_Model_Cron_Config
{

    /**
     * @return BSeller_SkyHub_Model_Cron_Config_Catalog_Product_Attribute
     */
    public function catalogProductAttribute()
    {
        return Mage::getSingleton('bseller_skyhub/cron_config_catalog_product_attribute');
    }


    /**
     * @return BSeller_SkyHub_Model_Cron_Config_Catalog_Product
     */
    public function catalogProduct()
    {
        return Mage::getSingleton('bseller_skyhub/cron_config_catalog_product');
    }


    /**
     * @return BSeller_SkyHub_Model_Cron_Config_Catalog_Category
     */
    public function catalogCategory()
    {
        return Mage::getSingleton('bseller_skyhub/cron_config_catalog_category');
    }


    /**
     * @return BSeller_SkyHub_Model_Cron_Config_Sales_Order_Status
     */
    public function salesOrderStatus()
    {
        return Mage::getSingleton('bseller_skyhub/cron_config_sales_order_status');
    }


    /**
     * @return BSeller_SkyHub_Model_Cron_Config_Sales_Order_Queue
     */
    public function salesOrderQueue()
    {
        return Mage::getSingleton('bseller_skyhub/cron_config_sales_order_queue');
    }

    /**
     * @return BSeller_SkyHub_Model_Cron_Config_Queue_Clean
     */
    public function queueClean()
    {
        return Mage::getSingleton('bseller_skyhub/cron_config_queue_clean');
    }
}
