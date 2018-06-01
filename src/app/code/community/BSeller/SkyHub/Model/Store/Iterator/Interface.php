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

interface BSeller_SkyHub_Model_Store_Iterator_Interface
{
    
    const REGISTRY_KEY = 'skyhub_store_iterator_iterating';
    
    
    /**
     * @return array
     */
    public function getStores();
    
    
    /**
     * @param string $class
     * @param string $object
     * @param array  $params
     *
     * @return $this
     */
    public function iterate($object, $method, array $params = []);
    
    
    /**
     * @param object                $subject
     * @param string                $method
     * @param array                 $params
     * @param Mage_Core_Model_Store $store
     * @param bool                  $force
     *
     * @return mixed
     */
    public function call($subject, $method, array $params = [], Mage_Core_Model_Store $store, $force = false);
    
    
    /**
     * This method should simulate the store.
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return $this
     */
    public function simulateStore(Mage_Core_Model_Store $store);
    
    
    /**
     * @return Mage_Core_Model_Store
     */
    public function getCurrentStore();
    
    
    /**
     * @return Mage_Core_Model_Store
     */
    public function getPreviousStore();
    
    
    /**
     * @return Mage_Core_Model_Store
     */
    public function getInitialStore();
    
    
    /**
     * Checks if the Store Iterator is already iterating in the moment.
     *
     * @return boolean
     */
    public function isIterating();
}
