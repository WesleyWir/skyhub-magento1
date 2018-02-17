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

class BSeller_SkyHub_Model_Cron_Catalog_Category extends BSeller_SkyHub_Model_Cron_Abstract
{

    /**
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function createCategoriesQueue(Mage_Cron_Model_Schedule $schedule)
    {
        if (!$this->canRun($schedule)) {
            return;
        }

        $rootCategoryLevel = 1;

        /** @var Mage_Catalog_Model_Resource_Category_Collection $categories */
        $categories = $this->getCategoryCollection()
            ->addFieldToFilter('level', ['gt' => $rootCategoryLevel]);

        if (!$categories->getSize()) {
            $schedule->setMessages($this->__('No category to be listed right now.'));
            return;
        }

        $categoryIds = [];

        /** @var Mage_Catalog_Model_Category $category */
        foreach ($categories as $category) {
            if ($category->isInRootCategoryList()) {
                continue;
            }

            $this->queue(
                $category->getId(),
                BSeller_SkyHub_Model_Entity::TYPE_CATALOG_CATEGORY,
                true,
                null,
                $category->getStoreId()
            );

            $categoryIds[] = $category->getId();
        }

        $schedule->setMessages(
            $this->__('The categories were successfully queued. Category IDs: %s.', implode(',', $categoryIds))
        );
    }


    /**
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function executeCategoriesQueue(Mage_Cron_Model_Schedule $schedule)
    {
        if (!$this->canRun($schedule)) {
            return;
        }

        $categoryIds = (array) $this->getQueueResource()
            ->getPendingEntityIds(BSeller_SkyHub_Model_Entity::TYPE_CATALOG_CATEGORY);

        if (empty($categoryIds)) {
            $schedule->setMessages($this->__('No category to be integrated right now.'));
            return;
        }

        /** @var Mage_Catalog_Model_Resource_Category_Collection $collection */
        $collection = $this->getCategoryCollection()
            ->addFieldToFilter('entity_id', $categoryIds);

        $successIds = [];
        $errorIds   = [];

        /** @var Mage_Catalog_Model_Category $category */
        foreach ($collection as $category) {
            /** @var bool|\SkyHub\Api\Handler\Response\HandlerInterface $response */
            $response = $this->catalogCategoryIntegrator()->createOrUpdate($category);

            if ($this->isErrorResponse($response)) {
                $errorIds[] = $category->getId();

                $this->getQueueResource()->setFailedEntityIds(
                    $category->getId(),
                    BSeller_SkyHub_Model_Entity::TYPE_CATALOG_CATEGORY,
                    $response->message(),
                    $category->getStoreId()
                );

                continue;
            }

            $this->getQueueResource()->removeFromQueue(
                $category->getId(),
                BSeller_SkyHub_Model_Entity::TYPE_CATALOG_CATEGORY,
                $category->getStoreId()
            );

            $successIds[] = $category->getId();
        }

        $schedule->setMessages($this->__(
            'Queue was processed. Success: %s. Errors: %s.',
            implode(',', $successIds),
            implode(',', $errorIds)
        ));
    }


    /**
     * @return Mage_Catalog_Model_Resource_Category_Collection
     */
    protected function getCategoryCollection()
    {
        /** @var Mage_Catalog_Model_Resource_Category_Collection $collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        return $collection;
    }


    /**
     * @param array $categoryIds
     *
     * @return mixed
     *
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function removeRootCategory(array &$categoryIds)
    {
        foreach ($categoryIds as $key => $categoryId) {
            if ($categoryId == Mage::app()->getStore()->getRootCategoryId()) {
                unset($categoryIds[$key]);
            }
        }

        return $categoryIds;
    }


    /**
     * @param Mage_Cron_Model_Schedule $schedule
     *
     * @return bool
     */
    protected function canRun(Mage_Cron_Model_Schedule $schedule)
    {
        if (!$this->getCronConfig()->catalogCategory()->isEnabled()) {
            $schedule->setMessages($this->__('Catalog Category Cron is Disabled'));
            return false;
        }

        return parent::canRun($schedule);
    }
}
