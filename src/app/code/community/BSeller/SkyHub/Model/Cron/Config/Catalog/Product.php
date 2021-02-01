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

class BSeller_SkyHub_Model_Cron_Config_Catalog_Product extends BSeller_SkyHub_Model_Cron_Config_Abstract
{

    protected $group = 'cron_catalog_products';
    
    
    /**
     * @return integer
     */
    public function getQueueCreateLimit()
    {
        return (int) $this->getGroupConfig('queue_create_limit');
    }


    /**
     * @return integer
     */
    public function getQueueExecuteLimit()
    {
        return (int) $this->getGroupConfig('queue_execute_limit');
    }
}
