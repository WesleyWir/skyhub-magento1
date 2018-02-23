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

/**
 * @method $this setSkyhubCode(string $code)
 * @method $this setSkyhubLabel(string $label)
 * @method $this setSkyhubDescription(string $description)
 * @method $this setAttributeId(int $id)
 * @method $this setEditable(bool $flag)
 * @method $this setCastType(string $type)
 *
 * @method string getSkyhubCode()
 * @method string getSkyhubLabel()
 * @method string getSkyhubDescription()
 * @method int    getAttributeId()
 * @method bool   getEditable()
 * @method string getCastType()
 */
class BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping extends BSeller_Core_Model_Abstract
{

    use BSeller_SkyHub_Trait_Config,
        BSeller_SkyHub_Trait_Catalog_Product;

    
    const DATA_TYPE_STRING   = 'string';
    const DATA_TYPE_BOOLEAN  = 'boolean';
    const DATA_TYPE_DECIMAL  = 'decimal';
    const DATA_TYPE_INTEGER  = 'integer';


    public function _construct()
    {
        $this->_init('bseller_skyhub/catalog_product_attributes_mapping');
    }
    
    
    /**
     * @return Mage_Eav_Model_Entity_Attribute
     */
    public function getAttribute()
    {
        if ($this->hasData('attribute')) {
            return $this->getData('attribute');
        }
    
        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        $attribute = Mage::getModel('eav/entity_attribute');
    
        if ($this->getAttributeId()) {
            $attribute->load((int) $this->getAttributeId());
            $this->setData('attribute', $attribute);
        }
        
        return $attribute;
    }


    /**
     * @return string
     */
    public function getSkyhubLabelTranslated()
    {
        return $this->__($this->getSkyhubLabel());
    }


    /**
     * @return string
     */
    public function getDataType()
    {
        $type = $this->getCastType();

        if (!$type || !in_array($type, $this->getValidDataTypes())) {
            $type = self::DATA_TYPE_STRING;
        }

        return $type;
    }


    /**
     * @return array
     */
    public function getAttributeInstallConfig()
    {
        $config = (array) $this->getSkyHubConfig()->getAttributeInstallConfig($this->getSkyhubCode());

        foreach ($config as $key => $value) {
            $config[$key] = (''==$value) ? null : $value;
        }

        return $config;
    }


    /**
     * @param string|int|bool|float $value
     *
     * @return bool|float|int|string
     */
    public function castValue($value)
    {
        switch ($this->getDataType()) {
            case self::DATA_TYPE_INTEGER:
                return (int) $value;
                break;
            case self::DATA_TYPE_DECIMAL:
                return (float) $value;
                break;
            case self::DATA_TYPE_BOOLEAN:
                return (bool) $value;
                break;
            case self::DATA_TYPE_STRING:
                return (string) $value;
                break;
            default:
                return $value;
        }
    }


    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array|bool|mixed|string
     */
    public function extractProductValue(Mage_Catalog_Model_Product $product)
    {
        try {
            $value = $this->productAttributeRawValue($product, $this->getAttribute());
            return $value;
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return null;
    }


    /**
     * @return array
     */
    protected function getValidDataTypes()
    {
        return [
            self::DATA_TYPE_BOOLEAN,
            self::DATA_TYPE_DECIMAL,
            self::DATA_TYPE_INTEGER,
            self::DATA_TYPE_STRING,
        ];
    }
}
