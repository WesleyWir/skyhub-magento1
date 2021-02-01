<?php
/**
 * BSeller Platform | B2W - Companhia Digital
 *
 * Do not edit this file if you want to update this module for future new versions.
 *
 * @category  ${MAGENTO_MODULE_NAMESPACE}
 * @package   ${MAGENTO_MODULE_NAMESPACE}_${MAGENTO_MODULE}
 *
 * @copyright Copyright (c) 2018 B2W Digital - BSeller Platform. (http://www.bseller.com.br)
 *
 * Access https://ajuda.skyhub.com.br/hc/pt-br/requests/new for questions and other requests.
 */
require dirname(__FILE__) . '/abstract.php';

class BSeller_SkyHub_Shell_Queue extends BSeller_SkyHub_Shell_Abstract
{
    
    public function run()
    {
        $this->executeCron();
    }
    
    
    protected function executeCron()
    {
        /** @var BSeller_SkyHub_Model_Cron_Queue_Abstract $cron */
        $cron = $this->getCronModel();
        
        if (!$cron) {
            return;
        }
        
        $method = $this->getMethod();
        
        if (!method_exists($cron, $method)) {
            return;
        }
    
        /** @var Mage_Cron_Model_Schedule $schedule */
        $schedule = Mage::getModel('cron/schedule');
    
        $cron->{$method}($schedule);
        
        if ($schedule->getMessages()) {
            $this->printLine($schedule->getMessages());
        }
    }
    
    
    /**
     * @return bool|string
     */
    protected function getMethod()
    {
        $method = $this->getArg('m');
        
        if (!$method) {
            return false;
        }
        
        return $method;
    }
}

(new BSeller_SkyHub_Shell_Queue())->run();
