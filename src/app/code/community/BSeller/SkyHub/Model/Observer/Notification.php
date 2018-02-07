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
class BSeller_SkyHub_Model_Observer_Notification
{
    
    use BSeller_SkyHub_Trait_Data;
    
    
    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    public function manageAdminNotification(Varien_Event_Observer $observer)
    {
        if (!$this->isAdmin()) {
            return;
        }
        
        if (empty($this->getPendingAttributeCodes())) {
            $this->deleteNotifications($this->getNotificationModuleIdentifier());
            return;
        }
        
        /** @var Mage_AdminNotification_Model_Inbox $inbox */
        $inbox = $this->getNotificationsCollection()->getFirstItem();
        $notificationTitle = implode(' - ', [
            $this->getNotificationIdentifier(),
            $this->getUnlinkedAttributesMessage()
        ]);
        
        $inbox->setTitle($notificationTitle);
        $inbox->setSeverity($inbox::SEVERITY_MAJOR);
        $inbox->setUrl($this->getMappingManagerUrl());
        $inbox->save();
    }
    
    
    /**
     * @return $this
     */
    protected function deleteNotifications($filter = null)
    {
        /** @var Mage_AdminNotification_Model_Inbox $inbox */
        foreach ($this->getNotificationsCollection($filter) as $inbox) {
            $inbox->isDeleted(true);
        }
    
        $this->getNotificationsCollection($filter)->save();
        return $this;
    }
    
    
    /**
     * @return Mage_AdminNotification_Model_Resource_Inbox_Collection
     */
    protected function getNotificationsCollection($identifier = null)
    {
        $key = 'notification_pending_notifications_collection';
    
        if (Mage::registry($key)) {
            return Mage::registry($key);
        }
        
        if (empty($identifier)) {
            $identifier = $this->getNotificationIdentifier();
        }
        
        /** @var Mage_AdminNotification_Model_Resource_Inbox_Collection $notifications */
        $notifications = Mage::getResourceModel('adminnotification/inbox_collection');
        $notifications->getSelect()
                      ->where("title like (?)", $identifier.'%');
        
        Mage::register($key, $notifications, true);
        
        return $notifications;
    }
    
    
    /**
     * @return string
     */
    protected function getUnlinkedAttributesMessage()
    {
        $text    = 'You have required attributes which are not mapped with your Magento attributes: "%s".';
        $message = $this->__($text, implode('", "', $this->getPendingAttributeCodes()));
        
        return $message;
    }
    
    
    /**
     * @return string
     *
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getMappingManagerUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/bseller_skyhub_catalog_product_attributes_mapping/index');
    }
    
    
    /**
     * @return string
     */
    protected function getNotificationIdentifier()
    {
        $reason = $this->__('Unlinked Attributes');
        return $this->getNotificationModuleIdentifier() . ' - ' . $reason;
    }
    
    
    /**
     * @return string
     */
    protected function getNotificationModuleIdentifier()
    {
        return $this->__('BSeller SkyHub');
    }
    
    
    /**
     * @return array
     */
    protected function getPendingAttributeCodes()
    {
        $key = 'notification_pending_attributes_codes';
        
        if (Mage::registry($key)) {
            return Mage::registry($key);
        }
        
        $codes = (array) $this->getPendingAttributesCollection()->getColumnValues('skyhub_code');
    
        Mage::register($key, $codes, true);
        
        return $codes;
    }
    
    
    /**
     * @return BSeller_SkyHub_Model_Resource_Catalog_Product_Attributes_Mapping_Collection
     */
    protected function getPendingAttributesCollection()
    {
        $key = 'notification_pending_attributes_collection';
        
        if (Mage::registry($key)) {
            return Mage::registry($key);
        }
        
        /** @var BSeller_SkyHub_Model_Resource_Catalog_Product_Attributes_Mapping_Collection $collection */
        $collection = Mage::getResourceModel('bseller_skyhub/catalog_product_attributes_mapping_collection');
        $collection->setPendingAttributesFilter();
        
        Mage::register($key, $collection, true);
        
        return $collection;
    }
    
    
    /**
     * @return bool
     *
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function isAdmin()
    {
        return (bool) Mage::app()->getStore()->isAdmin();
    }

}
