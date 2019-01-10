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
 * @author    Julio Reis <julio.reis@b2wdigital.com>
 */
class BSeller_SkyHub_Test_AbstractTest extends EcomDev_PHPUnit_Test_Case
{
    public function setUp()
    {
        @session_start();
    }

    public function cleanEntityTable($aliase)
    {
        Mage::app()->setCurrentStore(0);
        $collection = Mage::getModel($aliase)->getCollection();
        /** @var Mage_Sales_Model_Order $order */
        foreach ($collection as $item) {
            $item->delete();
        }
    }
}