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
 * @author    Bruno Gemelli <bruno.gemelli@e-smart.com.br>
 */

class BSeller_SkyHub_Model_Observer_Adminhtml_Skyhub_Product_Queue extends BSeller_SkyHub_Model_Observer_Abstract
{

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addResetButtonToProductQueuePage(Varien_Event_Observer $observer)
    {
        if (!$this->canRun()) {
            return;
        }

        /** @var BSeller_SkyHub_Block_Adminhtml_Queue_Catalog_Product_Grid $block */
        $block = $observer->getData('block');

        if (!($block instanceof BSeller_SkyHub_Block_Adminhtml_Queue_Catalog_Product_Grid)) {
            return;
        }

        /** @var Mage_Adminhtml_Block_Widget_Button $resetFilterButton */
        $resetFilterButton = $block->getChild('reset_filter_button');

        if (!($resetFilterButton instanceof Mage_Adminhtml_Block_Widget_Button)) {
            return;
        }

        $resetEntityMessage = Mage::helper('bseller_skyhub')->__('This action must be accompanied by Skyhub Integration Team. Do you want to continue?');

        /** @var Mage_Adminhtml_Block_Widget_Button $button */
        $button = $block->getLayout()->createBlock('adminhtml/widget_button');
        $button->setData([
            'label'   => Mage::helper('bseller_skyhub')->__('Reset SkyHub Products Integration History'),
            'onclick' => "if (confirm('{$resetEntityMessage}')) { setLocation('{$this->getTruncateEntityTableUrl($block)}')}",
            'class'   => 'delete'
        ]);

        $resetFilterButton->setData('before_html', $button->toHtml());
    }


    /**
     * @param BSeller_SkyHub_Block_Adminhtml_Queue_Catalog_Product_Grid $block
     *
     * @return string
     */
    protected function getTruncateEntityTableUrl(BSeller_SkyHub_Block_Adminhtml_Queue_Catalog_Product_Grid $block)
    {
        $url = (string) $block->getUrl('*/bseller_skyhub_catalog_product_entity/resetEntity');
        return $url;
    }
}