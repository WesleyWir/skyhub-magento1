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

class BSeller_SkyHub_Model_Resource_Customer_Attributes_Mapping_Collection
    extends BSeller_Core_Model_Resource_Collection_Abstract
{
    
    public function _construct()
    {
        $this->_init('bseller_skyhub/customer_attributes_mapping');
    }


    /**
     * @param string $code
     *
     * @return Varien_Object
     */
    public function getBySkyHubCode($code)
    {
        $this->load();
        return $this->getItemByColumnValue('skyhub_code', $code);
    }
    
    
    /**
     * @return $this
     */
    public function setMappedAttributesFilter()
    {
        $this->addFieldToFilter(
            [
                'attribute_id',
                'editable',
            ],
            [
                ['notnull' => true],
                ['eq' => 0]
            ]
        );
        
        return $this;
    }
    
    
    /**
     * @return $this
     */
    public function setPendingAttributesFilter()
    {
        $this->addFieldToFilter('attribute_id', ['null' => true]);
        $this->addFieldToFilter('editable', 1);
        
        return $this;
    }
    
}
