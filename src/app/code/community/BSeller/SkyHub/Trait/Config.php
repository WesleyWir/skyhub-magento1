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
 * @author    Bruno Gemelli <bruno.gemelli@e-smart.com.br>
 * @author    Julio Reis <julio.reis@e-smart.com.br>
 */

trait BSeller_SkyHub_Trait_Config
{

    use BSeller_Core_Trait_Config,
        BSeller_SkyHub_Trait_Config_Service,
        BSeller_SkyHub_Trait_Config_Log,
        BSeller_SkyHub_Trait_Config_General;


    /**
     * @param string                      $field
     * @param string                      $group
     * @param Mage_Core_Model_Config|null $store
     *
     * @return mixed
     */
    protected function getSkyHubModuleConfig($field, $group, Mage_Core_Model_Config $store = null)
    {
        return $this->getModuleConfig($field, $group, 'bseller_skyhub', $store);
    }


    /**
     * @param string                      $field
     * @param string                      $group
     * @param Mage_Core_Model_Config|null $store
     *
     * @return array
     */
    protected function getSkyHubModuleConfigAsArray($field, $group, Mage_Core_Model_Config $store = null)
    {
        $values =  $this->getModuleConfig($field, $group, 'bseller_skyhub', $store);
        $arrayValues = explode(',', $values);
        return $arrayValues;
    }


    /**
     * @param string $field
     *
     * @return string|integer
     */
    protected function getGeneralConfig($field)
    {
        return $this->getSkyHubModuleConfig($field, 'general');
    }


    /**
     * @return boolean
     */
    protected function isModuleEnabled()
    {
        return (bool) $this->getGeneralConfig('enabled');
    }


    /**
     * @return int
     */
    protected function getNewOrdersDefaultStoreId()
    {
        return $this->getNewOrdersDefaultStore()->getId();
    }


    /**
     * @return Mage_Core_Model_Store
     */
    protected function getNewOrdersDefaultStore()
    {
        $storeId = (int) $this->getSkyHubModuleConfig('default_store_id', 'cron_sales_order_queue');

        try {
            if (Mage::app()->getStore($storeId)->isAdmin()) {
                $storeId = Mage::app()->getDefaultStoreView()->getId();
            }

            return Mage::app()->getStore($storeId);
        } catch (Exception $e) {}

        return Mage::app()->getDefaultStoreView();
    }


    /**
     * @return string
     */
    protected function getNewOrdersStatus()
    {
        $status = (string) $this->getSkyHubModuleConfig('new_order_status', 'sales_order_status');

        if (empty($status)) {
            $status = $this->getDefaultStatusByState(Mage_Sales_Model_Order::STATE_NEW);
        }

        return $status;
    }


    /**
     * @return string
     */
    protected function getApprovedOrdersStatus()
    {
        $status = (string) $this->getSkyHubModuleConfig('approved_order_status', 'sales_order_status');

        if (empty($status)) {
            $status = $this->getDefaultStatusByState(Mage_Sales_Model_Order::STATE_PROCESSING);
        }

        return $status;
    }


    /**
     * @return string
     */
    protected function getDeliveredOrdersStatus()
    {
        $status = (string) $this->getSkyHubModuleConfig('delivered_order_status', 'sales_order_status');

        if (empty($status)) {
            $status = $this->getDefaultStatusByState(Mage_Sales_Model_Order::STATE_COMPLETE);
        }

        return $status;
    }


    /**
     * @return string
     */
    protected function getShipmentExceptionOrderStatus()
    {
        $status = (string) $this->getSkyHubModuleConfig('shipment_exception_order_status', 'sales_order_status');

        if (empty($status)) {
            $status = $this->getDefaultStatusByState(Mage_Sales_Model_Order::STATE_COMPLETE);
        }

        return $status;
    }
    
    
    /**
     * @param string $state
     *
     * @return string
     */
    protected function getDefaultStatusByState($state)
    {
        /** @var Mage_Sales_Model_Order_Status $status */
        $status = Mage::getModel('sales/order_status')->loadDefaultByState($state);
        return (string) $status->getId();
    }


    /**
     * @return string
     */
    protected function getTaxInvoiceKeyPattern()
    {
        return (string) $this->getSkyHubModuleConfig('pattern', 'tax_invoice_key');
    }
    
    
    /**
     * @return BSeller_SkyHub_Model_Config
     */
    protected function getProductSkyHubConfig()
    {
        return Mage::getSingleton('bseller_skyhub/config_catalog_product');
    }

    /**
     * @return BSeller_SkyHub_Model_Config
     */
    protected function getCustomerSkyHubConfig()
    {
        return Mage::getSingleton('bseller_skyhub/config_customer');
    }

    /**
     * @return boolean
     */
    protected function hasActiveIntegrateOnSaveFlag()
    {
        return (bool)$this->getGeneralConfig('immediately_integrate_product_on_save_price_stock_change');
    }
}
