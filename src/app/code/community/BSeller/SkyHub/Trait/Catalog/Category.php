<?php

trait BSeller_SkyHub_Trait_Catalog_Category
{

    /**
     * @param Mage_Catalog_Model_Category $category
     * @param null                        $store
     *
     * @return string
     */
    protected function extractProductCategoryPathString(Mage_Catalog_Model_Category $category, $store = null)
    {
        if (!$store) {
            $store = $this->getCategoryStore($category);
        }

        $ids            = $this->getCategoryPathIds($category, $store);
        $categoryPieces = [];

        foreach ($ids as $id) {
            $name = $category->getName();

            if (!$name || !($id == $category->getId())) {
                $name = $category->getResource()->getAttributeRawValue($id, 'name', $store);
            }

            $categoryPieces[] = $name;
        }

        return implode(' > ', $categoryPieces);
    }


    /**
     * @param Mage_Catalog_Model_Category $category
     * @param null                        $store
     *
     * @return array
     */
    protected function getCategoryPathIds(Mage_Catalog_Model_Category $category, $store = null)
    {
        if (!$store) {
            $store = $this->getCategoryStore($category);
        }

        $ids     = array_reverse(explode('/', $category->getPath()));
        $pathIds = [];

        /** @var int $id */
        foreach ($ids as $id) {
            if ($id == $store->getRootCategoryId()) {
                break;
            }

            $pathIds[] = (int) $id;
        }

        return (array) array_reverse($pathIds);
    }


    /**
     * @param Mage_Catalog_Model_Category $category
     *
     * @return Mage_Core_Model_Store
     *
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getCategoryStore(Mage_Catalog_Model_Category $category = null)
    {
        if ($category && $category->getStore()->getRootCategoryId()) {
            return $category->getStore();
        }

        return Mage::app()->getStore();
    }
}
