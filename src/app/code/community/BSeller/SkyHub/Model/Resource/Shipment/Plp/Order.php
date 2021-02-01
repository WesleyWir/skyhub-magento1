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

class BSeller_SkyHub_Model_Resource_Shipment_Plp_Order extends BSeller_Core_Model_Resource_Abstract
{

    protected function _construct()
    {
        $this->_init('bseller_skyhub/plp_orders', 'id');
    }

}
