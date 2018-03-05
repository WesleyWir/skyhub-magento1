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

        $defaultDataType  = BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping::DATA_TYPE_STRING;

        /** @var array $attribute */
        foreach ($attributes as $identifier => $data) {
            $skyhubCode  = $this->arrayExtract($data, 'code');
            $label       = $this->arrayExtract($data, 'label');
            $castType    = $this->arrayExtract($data, 'cast_type', $defaultDataType);
            $description = $this->arrayExtract($data, 'description');
            $validation  = $this->arrayExtract($data, 'validation');
            $enabled     = (bool) $this->arrayExtract($data, 'required', true);
            $required    = (bool) $this->arrayExtract($data, 'required', true);
            $editable    = (bool) $this->arrayExtract($data, 'editable', true);
            
            if (empty($skyhubCode) || empty($castType)) {
                continue;
            }
            
            $attributeData = [
                'skyhub_code'        => $skyhubCode,
                'skyhub_label'       => $label,
                'skyhub_description' => $description,
                'enabled'            => $enabled,
                'cast_type'          => $castType,
                'validation'         => $validation,
                'required'           => $required,
                'editable'           => $editable,
            ];

            $installConfig = (array) $this->arrayExtract($data, 'attribute_install_config', []);
            $magentoCode   = $this->arrayExtract($installConfig, 'attribute_code');
            
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
    
    
    /**
     * @param array $statuses
     *
     * @return $this
     */
    public function createAssociatedSalesOrderStatuses(array $states = [])
    {
        foreach ($states as $stateCode => $statuses) {
            $this->createSalesOrderStatus($stateCode, $statuses);
        }
        
        return $this;
    }
    
    
    /**
     * @param string $state
     * @param array  $status
     *
     * @return $this
     */
    public function createSalesOrderStatus($state, array $status)
    {
        foreach ($status as $statusCode => $statusLabel) {
            $statusData = [
                'status' => $statusCode,
                'label'  => $statusLabel
            ];
        
            $this->getConnection()->insertIgnore($this->getSalesOrderStatusTable(), $statusData);
            $this->associateStatusToState($state, $statusCode);
        }
        
        return $this;
    }
    
    
    /**
     * @param string $state
     * @param string $status
     * @param int    $isDefault
     *
     * @return $this
     */
    public function associateStatusToState($state, $status, $isDefault = 0)
    {
        $associationData = [
            'status'     => (string) $status,
            'state'      => (string) $state,
            'is_default' => (int)    $isDefault,
        ];
    
        $this->getConnection()->insertIgnore($this->getSalesOrderStatusStateTable(), $associationData);
        
        return $this;
    }
    
    
    /**
     * @return string
     */
    public function getSalesOrderStatusTable()
    {
        return $this->getTable('sales/order_status');
    }
    
    
    /**
     * @return string
     */
    public function getSalesOrderStatusStateTable()
    {
        return $this->getTable('sales/order_status_state');
    }
}
