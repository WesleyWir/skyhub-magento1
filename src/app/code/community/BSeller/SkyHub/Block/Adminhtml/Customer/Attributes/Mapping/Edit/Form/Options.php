<?php

/**
 * BSeller - B2W Companhia Digital
 *
 * DISCLAIMER
 *
 * Do not edit this file if you want to update this module for future new versions.
 *
 * @copyright     Copyright (c) 2017 B2W Companhia Digital. (http://www.bseller.com.br/)
 *
 * @author        Julio Reis <julio.reis@e-smart.com.br>
 */
class BSeller_SkyHub_Block_Adminhtml_Customer_Attributes_Mapping_Edit_Form_Options
    extends BSeller_Core_Block_Adminhtml_Template
{
    use BSeller_SkyHub_Trait_Customer_Attribute_Mapping;

    protected function _construct()
    {
        $this->setTemplate('bseller/skyhub/notifications/skyhub/customer/form/options.phtml');
        parent::_construct();
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    public function getMagentoAttribute()
    {
        return Mage::getModel('eav/entity_attribute')->load($this->getRequestData('magentoAttributeId'));
    }

    /*
     * @return BSeller_SkyHub_Trait_Customer_Attribute_Mapping
     */
    public function getMappingAttribute()
    {
        return Mage::getModel('bseller_skyhub/customer_attributes_mapping')->load($this->getRequestData('mappingAttributeId'));
    }

    /**
     * @return array
     */
    public function getSubMappingAttributeOptions()
    {
        return $this->getMappingAttribute()->getOptions();
    }

    /**
     * @return array
     */
    public function getMagentoAttributeOptions()
    {
        $attribute = $this->getMagentoAttribute();
        if (!$attribute->usesSource()) {
            return null;
        }
        return $attribute->getSource()->getAllOptions();
    }
}