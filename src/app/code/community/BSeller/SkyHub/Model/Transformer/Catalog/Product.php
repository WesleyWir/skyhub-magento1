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
 * @author    Rafael Falcao <rafael.falcao@e-smart.com.br>
 */

use SkyHub\Api\EntityInterface\Catalog\Product;

class BSeller_SkyHub_Model_Transformer_Catalog_Product extends BSeller_SkyHub_Model_Transformer_Abstract
{

    use BSeller_SkyHub_Trait_Catalog_Product,
        BSeller_SkyHub_Trait_Catalog_Category,
        BSeller_SkyHub_Trait_Eav_Option,
        BSeller_SkyHub_Trait_Catalog_Product_Attribute,
        BSeller_SkyHub_Trait_Catalog_Product_Attribute_Mapping;
    
    
    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return Product
     *
     * @throws Mage_Core_Exception
     */
    public function convert(Mage_Catalog_Model_Product $product)
    {
        $this->initProductAttributes();
        
        /** @var Product $interface */
        $interface = $this->api()->product()->entityInterface();
        $this->prepareMappedAttributes($product, $interface)
             ->prepareSpecificationAttributes($product, $interface)
             ->prepareProductCategories($product, $interface)
             ->prepareProductImages($product, $interface)
             ->prepareProductVariations($product, $interface);

        Mage::dispatchEvent(
            'bseller_skyhub_product_convert_after',
            array(
                'product'   => $product,
                'interface' => $interface,
            )
        );

        return $interface;
    }
    
    
    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Product                    $interface
     *
     * @return $this
     *
     * @throws Mage_Core_Exception
     */
    protected function prepareProductVariations(Mage_Catalog_Model_Product $product, Product $interface)
    {
        switch($product->getTypeId()) {
            case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                /** @var BSeller_SkyHub_Model_Transformer_Catalog_Product_Variation_Type_Configurable $variation */
                $variation = Mage::getModel('bseller_skyhub/transformer_catalog_product_variation_type_configurable');
                $variation->create($product, $interface);
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
                /** @var BSeller_SkyHub_Model_Transformer_Catalog_Product_Variation_Type_Grouped $variation */
                $variation = Mage::getModel('bseller_skyhub/transformer_catalog_product_variation_type_grouped');
                $variation->create($product, $interface);
                break;
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                /** @todo Create the bundle integration. */
            case Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL:
                /** @todo Create the bundle integration. */
            case Mage_Catalog_Model_Product_Type::TYPE_SIMPLE:
            default:
                break;
        }

        return $this;
    }
    
    
    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Product                    $interface
     *
     * @return $this
     *
     * @throws Mage_Core_Exception
     */
    public function prepareProductImages(Mage_Catalog_Model_Product $product, Product $interface)
    {
        if (!$product->getMediaGalleryImages()) {
            /** @var Mage_Eav_Model_Entity_Attribute $attribute */
            $attribute = Mage::getModel('eav/entity_attribute')->loadByCode(
                Mage_Catalog_Model_Product::ENTITY,
                'media_gallery'
            );

            /** @var Mage_Catalog_Model_Product_Attribute_Backend_Media $media */
            Mage::getModel('catalog/product_attribute_backend_media')
                ->setAttribute($attribute)
                ->afterLoad($product);
        }

        /** @var Varien_Data_Collection|null $gallery */
        $gallery = $product->getMediaGalleryImages();

        if (!$gallery || !$gallery->getSize()) {
            return $this;
        }

        /** @var Varien_Object $image */
        foreach ($gallery as $image) {
            $url = $image->getData('url');
            $interface->addImage($url);
        }
        
        return $this;
    }
    
    
    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Product                    $interface
     *
     * @return $this
     *
     * @throws Mage_Core_Exception
     */
    public function prepareProductCategories(Mage_Catalog_Model_Product $product, Product $interface)
    {
        /** @var Mage_Catalog_Model_Resource_Category_Collection $categories */
        $categories = $product->getCategoryCollection();
        $categories->addAttributeToSelect(array(
            'name',
        ));

        $categories->addAttributeToFilter('level', array('gteq' => 2));
        
        /** @var Mage_Catalog_Model_Category $category */
        foreach ($categories as $category) {
            $interface->addCategory(
                $category->getId(),
                $this->extractProductCategoryPathString($category)
            );
        }
        
        return $this;
    }


    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Product                    $interface
     *
     * @return $this
     */
    public function prepareSpecificationAttributes(Mage_Catalog_Model_Product $product, Product $interface)
    {
        /**
         * Let's get the processed attributes to exclude'em from the specification list.
         */
        $processedAttributeIds = (array) $product->getData('processed_attributes');
        $remainingAttributes   = (array) $this->getProductAttributes(array(), array_keys($processedAttributeIds));
    
        /** @var Mage_Eav_Model_Entity_Attribute $specificationAttribute */
        foreach ($remainingAttributes as $attribute) {
            /**
             * If the specification attribute is not valid then skip.
             *
             * @var Mage_Eav_Model_Entity_Attribute $attribute
             */
            if (!$attribute || !$this->validateSpecificationAttribute($attribute)) {
                continue;
            }
            
            try {
                $value = $this->extractProductData($product, $attribute);

                if (empty($value) && $value !== 0 && $value !== '0') {
                    continue;
                }

                if ($this->_validateProductAttributeBlacklist($attribute)) {
                    continue;
                }
    
//                $interface->addSpecification($attribute->getFrontend()->getLabel(), $value);
                $interface->addSpecification($attribute->getAttributeCode(), $value);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        
        return $this;
    }

    /**
     * Check if attribute is not in blacklist
     *
     * @param $attribute
     * @return bool
     */
    protected function _validateProductAttributeBlacklist($attribute)
    {
        $productAttributeBlackList = Mage::getSingleton('bseller_skyhub/system_config_source_catalog_product_blacklist_attributes')
                                            ->getCustomProductAttributeBlacklist();

        if(!count($productAttributeBlackList)){
            return false;
        }

        if(in_array($attribute->getId(), $productAttributeBlackList)){
            return true;
        }

        return false;
    }
    
    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     *
     * @return bool
     */
    public function validateSpecificationAttribute(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        if ($this->isAttributeCodeInBlacklist($attribute->getAttributeCode())) {
            return false;
        }
        
        return true;
    }


    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Product                    $interface
     *
     * @return $this
     */
    public function prepareMappedAttributes(Mage_Catalog_Model_Product $product, Product $interface)
    {
        /** @var BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping $mappedAttribute */
        foreach ($this->getMappedAttributes() as $mappedAttribute) {
            /** @var string $code */
            $code   = (string) $mappedAttribute->getSkyhubCode();
            $method = 'set'.preg_replace('/[^a-zA-Z]/', null, uc_words($code));
        
            if (!method_exists($interface, $method)) {
                continue;
            }
            
            switch ($code) {
                case 'qty':
                case 'price':
                case 'promotional_price':
                    continue;
                default:
                    /** @var Mage_Eav_Model_Entity_Attribute|bool $attribute */
                    if (!$attribute = $this->getAttributeById($mappedAttribute->getAttributeId())) {
                        $attribute = $mappedAttribute->getAttribute();
                    }
                    
                    if (!$attribute) {
                        continue;
                    }

                    $value = $this->getProductAttributeValue($product, $attribute, $mappedAttribute->getCastType());
    
                    $this->addProcessedAttribute($product, $attribute);
    
                    call_user_func(array($interface, $method), $value);
            }
        }
    
        $this->prepareProductQty($product, $interface);
        $this->prepareProductPrices($product, $interface);
        
        return $this;
    }
    
    
    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Product                    $interface
     *
     * @return $this
     */
    protected function prepareProductQty(Mage_Catalog_Model_Product $product, Product $interface)
    {
        /** @var BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping $mappedAttribute */
        $mappedAttribute = $this->getMappedAttribute('qty');

        if (!$mappedAttribute || !$mappedAttribute->getId()) {
            return $this;
        }
        
        $value = $this->getProductStockQty($product);
        $interface->setQty($value);
        
        return $this;
    }


    /**
     * @param Mage_Catalog_Model_Product             $product
     * @return int
     */
    protected function getProductStockQty(Mage_Catalog_Model_Product $product)
    {
        if (!$product->isSalable()) {
            return 0;
        }

        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = Mage::getModel('cataloginventory/stock_item');
        $stockItem->loadByProduct($product);

        if (!$stockItem->getManageStock()) {
            return 1000;
        }

        if (!$stockItem->getIsInStock()) {
            return 0;
        }

        return $stockItem->getQty();
    }
    
    
    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Product                    $interface
     *
     * @return $this
     */
    protected function prepareProductPrices(Mage_Catalog_Model_Product $product, Product $interface)
    {
        /**
         * @var BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping $mappedPrice
         * @var BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping $mappedPromoPrice
         */
        $mappedPrice      = $this->getMappedAttribute('price');
        $mappedPromoPrice = $this->getMappedAttribute('promotional_price');
        
        $priceCode        = $mappedPrice->getAttribute()->getAttributeCode();
        $specialPriceCode = $mappedPromoPrice->getAttribute()->getAttributeCode();
    
        /**
         * Add Price.
         */
        $price = $this->extractProductPrice($product, $priceCode);
        
        if (!empty($price)) {
            $price = (float) $price;
        } else {
            null;
        }
    
        $interface->setPrice($price);
        
        $this->addProcessedAttribute($product, $mappedPrice->getAttribute());
    
        /**
         * Add Promotional Price.
         */
        $specialPrice = $this->extractProductSpecialPrice($product, $specialPriceCode, $price);
        
        $interface->setPromotionalPrice($specialPrice);
    
        $this->addProcessedAttribute($product, $mappedPromoPrice->getAttribute());
        
        return $this;
    }
    
    
    /**
     * @param Mage_Catalog_Model_Product      $product
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     *
     * @return $this
     */
    protected function addProcessedAttribute(
        Mage_Catalog_Model_Product $product,
        Mage_Eav_Model_Entity_Attribute $attribute = null
    )
    {
        if (!$attribute) {
            return $this;
        }
        
        $processedAttributes = (array) $product->getData('processed_attributes');
        $processedAttributes[$attribute->getId()] = $attribute;
    
        $product->setData('processed_attributes', $processedAttributes);
        
        return $this;
    }


    /**
     * @param Mage_Catalog_Model_Product      $product
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param null|string                     $type
     *
     * @return array|bool|float|int|mixed|string
     */
    public function getProductAttributeValue(
        Mage_Catalog_Model_Product $product,
        Mage_Eav_Model_Entity_Attribute $attribute,
        $type = null
    )
    {
        if (!$attribute) {
            return false;
        }
    
        $value = $this->extractProductData($product, $attribute);
        $value = $this->castValue($value, $type);
        
        return $value;
    }


    /**
     * @param Mage_Catalog_Model_Product      $product
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     *
     * @return array|bool|mixed|string
     */
    public function extractProductData(Mage_Catalog_Model_Product $product, Mage_Eav_Model_Entity_Attribute $attribute)
    {
        $data = $this->productAttributeRawValue($product, $attribute);
        
        if ((false === $data) || is_null($data)) {
            return false;
        }
        
        switch ($attribute->getAttributeCode()) {
            case 'status':
                if ($data == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
                    return true;
                }
                
                if ($data == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
                    return false;
                }
                
                break;
        }
    
        /**
         * Attribute is from type select.
         */
        if (in_array($attribute->getFrontend()->getInputType(), array('select', 'multiselect'))) {
            try {
                $data = $this->extractAttributeOptionValue($attribute, $data, $this->getStore());
            } catch (Exception $e) {
                // Mage::logException($e);
            }
        }

        if ((false !== $data) && !is_null($data)) {
            if (is_array($data) && empty($data)) {
                return false;
            }
            return $data;
        }
        
        return false;
    }
    
    
    /**
     * @param string $value
     * @param string $type
     *
     * @return bool|float|int|string
     */
    protected function castValue($value, $type)
    {
        switch ($type) {
            case BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping::DATA_TYPE_INTEGER:
                return (int) $value;
                break;
            case BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping::DATA_TYPE_DECIMAL:
                return (float) $value;
                break;
            case BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping::DATA_TYPE_BOOLEAN:
                return (bool) $value;
                break;
            case BSeller_SkyHub_Model_Catalog_Product_Attributes_Mapping::DATA_TYPE_STRING:
            default:
                return (string) $value;
        }
    }
    
    
    /**
     * @return Mage_Core_Model_Store
     *
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getStore()
    {
        return Mage::app()->getStore();
    }
}
