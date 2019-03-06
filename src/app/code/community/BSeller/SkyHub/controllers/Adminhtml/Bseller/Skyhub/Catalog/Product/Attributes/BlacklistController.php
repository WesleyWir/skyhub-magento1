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
 * @author    Rafael Falcao <rafael.falcao@e-smart.com.br>
 */

class BSeller_SkyHub_Adminhtml_Bseller_Skyhub_Catalog_Product_Attributes_BlacklistController
    extends BSeller_SkyHub_Controller_Admin_Action
{
    /**
     * @return $this
     */
    protected function init($actionTitle = null)
    {
        parent::init('Product Attributes');

        if (!empty($actionTitle)) {
            $this->_title($this->__($actionTitle));
        }

        return $this;
    }

    /**
     * Attributes Mapping Grid Action.
     */
    public function indexAction()
    {
        $this->init('Attributes Blacklist');

        $this->renderLayout();
    }


    /**
     * Action to save attributes to custom blacklist
     */
    public function saveAction()
    {
        $attributeBlacklist = $this->getRequest()->getPost('blacklist');

        if(is_array($attributeBlacklist)){
            $attributeBlacklist = implode(',', $attributeBlacklist);
        }

        $attributesSource = Mage::getModel('bseller_skyhub/system_config_source_catalog_product_blacklist_attributes');
        $attributesSource->setCustomProductAttributeBlacklist($attributeBlacklist);

        $this->_getSession()
            ->addSuccess(
                $this->__(
                    'Successfully updated Skyhub Blacklist.'
                )
            );

        $this->_redirect('*/*');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        $this->_aclSuffix = 'catalog_product_attributes_blacklist';
        return parent::_isAllowed();
    }
}