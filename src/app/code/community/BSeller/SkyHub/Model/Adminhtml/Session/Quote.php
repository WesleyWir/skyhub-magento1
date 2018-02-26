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
 
class BSeller_SkyHub_Model_Adminhtml_Session_Quote extends Mage_Adminhtml_Model_Session_Quote
{
    
    /**
     * @return $this
     */
    public function clear()
    {
        $this->_quote    = null;
        $this->_customer = null;
        $this->_order    = null;
        $this->_store    = null;
        
        parent::clear();
        
        return $this;
    }


    public function getCustomer($forceReload=false, $useSetStore=false)
    {
        return parent::getCustomer($forceReload, $useSetStore);
    }
}
