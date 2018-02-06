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
class BSeller_SkyHub_Model_Resource_Setup extends BSeller_Core_Model_Resource_Setup
{
    
    use BSeller_SkyHub_Trait_Data,
        BSeller_SkyHub_Trait_Config,
        BSeller_SkyHub_Trait_Catalog_Product_Attribute;
    
    
    /**
     * @return array
     */
    public function getSkyHubFixedAttributes()
    {
        return $this->getSkyHubConfig()->getSkyHubFixedAttributes();
    }
    
    
    /**
     * @return $this
     */
    public function installSkyHubRequiredAttributes()
    {
        $attributes = (array)  $this->getSkyHubFixedAttributes();
        $table      = (string) $this->getTable('bseller_skyhub/product_attributes_mapping');
        
        /** @var array $attribute */
        foreach ($attributes as $skyhubCode => $data) {
            $label       = $this->arrayExtract($data, 'label');
            $type        = $this->arrayExtract($data, 'type');
            $description = $this->arrayExtract($data, 'description');
            $validation  = $this->arrayExtract($data, 'validation');
            $magentoCode = $this->arrayExtract($data, 'attribute_code');
            $enabled     = (bool) $this->arrayExtract($data, 'required', true);
            $required    = (bool) $this->arrayExtract($data, 'required', true);
            $editable    = (bool) $this->arrayExtract($data, 'editable', true);
            
            if (empty($skyhubCode) || empty($type)) {
                continue;
            }
            
            $attributeData = [
                'skyhub_code'        => $skyhubCode,
                'skyhub_label'       => $label,
                'skyhub_description' => $description,
                'enabled'            => $enabled,
                'type'               => $type,
                'validation'         => $validation,
                'required'           => $required,
                'editable'           => $editable,
            ];
            
            /** @var Mage_Eav_Model_Entity_Attribute $attribute */
            if ($attribute = $this->getAttributeByCode($magentoCode)) {
                $attributeData['attribute_id'] = $attribute->getId();
            }
            
            $this->getConnection()->beginTransaction();
            
            try {
                /** @var Varien_Db_Select $select */
                $select = $this->getConnection()
                               ->select()
                               ->from($table, 'id')
                               ->where('skyhub_code = :skyhub_code')
                               ->limit(1);
    
                $id = $this->getConnection()->fetchOne($select, [':skyhub_code' => $skyhubCode]);
    
                if ($id) {
                    $this->getConnection()->update($table, $attributeData, "id = {$id}");
                    $this->getConnection()->commit();
                    continue;
                }
    
                $this->getConnection()->insert($table, $attributeData);
                $this->getConnection()->commit();
            } catch (Exception $e) {
                $this->getConnection()->rollBack();
            }
        }
        
        return $this;
    }
}
