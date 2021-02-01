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
 * Access https://ajuda.skyhub.com.br/hc/pt-br/requests/new for questions and other requests.
 */

class BSeller_SkyHub_Model_System_Config_Source_Skyhub_Status_Types
    extends BSeller_Core_Model_System_Config_Source_Abstract
{

    const TYPE_NEW       = 'NEW';
    const TYPE_CANCELED  = 'CANCELED';
    const TYPE_APPROVED  = 'APPROVED';
    const TYPE_SHIPPED   = 'SHIPPED';
    const TYPE_DELIVERED = 'DELIVERED';
    const TYPE_SHIPMENT_EXCEPTION = 'SHIPMENT_EXCEPTION';


    /**
     * @return array
     */
    protected function optionsKeyValue($multiselect = null)
    {
        return array(
            self::TYPE_NEW       => $this->__('New'),
            self::TYPE_CANCELED  => $this->__('Canceled'),
            self::TYPE_APPROVED  => $this->__('Approved'),
            self::TYPE_SHIPPED   => $this->__('Shipped'),
            self::TYPE_DELIVERED => $this->__('Delivered'),
            self::TYPE_SHIPMENT_EXCEPTION => $this->__('Shipment Exception'),
        );
    }
}
