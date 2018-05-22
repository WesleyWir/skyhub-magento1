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

class BSeller_SkyHub_Block_Adminhtml_Queue_Catalog_Product_Grid extends BSeller_SkyHub_Block_Adminhtml_Widget_Grid
{
    /**
     * @var string
     */
    protected $_entityType = 'catalog_product';

    /**
     * @param BSeller_SkyHub_Model_Resource_Queue_Collection $collection
     *
     * @return BSeller_SkyHub_Model_Resource_Queue_Collection
     *
     * @throws Mage_Core_Exception
     */
    protected function getPreparedCollection(BSeller_SkyHub_Model_Resource_Queue_Collection $collection)
    {
        /** @var Mage_Eav_Model_Entity_Attribute $name */
        $name = Mage::getModel('eav/entity_attribute');
        $name->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'name');

        $condition  = "eav.entity_id = main_table.entity_id ";
        $condition .= "AND eav.attribute_id = '{$name->getId()}'";
        $condition .= "AND eav.store_id = '{$this->getStoreId()}'";

        /** @var BSeller_SkyHub_Model_Resource_Queue_Collection $collection */
        $collection->getSelect()
            ->joinLeft(
                ['entity' => Mage::getSingleton('core/resource')->getTableName('catalog/product')],
                "entity.entity_id = main_table.entity_id",
                ['sku']
            )->joinLeft(
                ['eav' => $name->getBackendTable()],
                $condition,
                ['product_name' => 'value']
            )
        ;

        $collection->addFieldToFilter('entity_type', BSeller_SkyHub_Model_Entity::TYPE_CATALOG_PRODUCT);

        return $collection;
    }


    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumnAfter('sku', [
            'header'       => $this->__('Product SKU'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '200px',
            'index'        => 'sku',
            'filter_index' => 'entity.sku',
        ], 'entity_id');

        $this->addColumnAfter('product_name', [
            'header'       => $this->__('Product Name'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'product_name',
            'filter_index' => 'eav.value',
        ], 'sku');

        $this->sortColumnsByOrder();

        return $this;
    }
    
}
