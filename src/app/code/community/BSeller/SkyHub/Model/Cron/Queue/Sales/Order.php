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
 * Access https://ajuda.skyhub.com.br/hc/pt-br/requests/new for questions and other requests.
 */
 
class BSeller_SkyHub_Model_Cron_Queue_Sales_Order extends BSeller_SkyHub_Model_Cron_Queue_Sales_Abstract
{

    /**
     * This method is not mapped (being used) anywhere because it can be harmful to store performance.
     * This is just a method created for tests and used when there's no order in the queue (SkyHub) to be consumed.
     *
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function execute(Mage_Cron_Model_Schedule $schedule)
    {
        $this->processStoreIteration($this, 'executeIntegration', $schedule);
    }
    
    
    /**
     * @param Mage_Cron_Model_Schedule $schedule
     * @param Mage_Core_Model_Store    $store
     */
    public function executeIntegration(Mage_Cron_Model_Schedule $schedule, Mage_Core_Model_Store $store)
    {
        if (!$this->canRun($schedule, $store->getId())) {
            return;
        }
    
        $orders = (array) $this->orderIntegrator()->orders();
    
        foreach ($orders as $orderData) {
            try {
                /** @var Mage_Sales_Model_Order $order */
                $order = $this->salesOrderProcessor()->createOrder($orderData);
            } catch (BSeller_SkyHub_Exceptions_UnprocessableException $e) {
                $schedule->setMessages($e->getMessage());
                continue;
            } catch (Exception $e) {
                Mage::logException($e);
                continue;
            }
        
            if (!$order || !$order->getId()) {
                continue;
            }
        
            $statusType = $this->arrayExtract($orderData, 'status/type');
            $statusCode = $this->arrayExtract($orderData, 'status/code');

            $this->salesOrderStatusProcessor()->processOrderStatus($statusCode, $statusType, $order, $orderData);
        
            $message  = $schedule->getMessages();
        
            if ($order->getData('is_created')) {
                $message .= $this->__(
                    'Order ID %s was successfully created in store %s.',
                    $order->getIncrementId(),
                    $store->getName()
                );
            } elseif ($order->hasDataChanges()) {
                $message .= $this->__(
                    'Order ID %s was updated in store %s.',
                    $order->getIncrementId(),
                    $store->getName()
                );
            }
        
            $schedule->setMessages($message);
        }
    }
    
    
    /**
     * @param Mage_Cron_Model_Schedule $schedule
     * @param int|null                 $storeId
     *
     * @return bool
     */
    protected function canRun(Mage_Cron_Model_Schedule $schedule, $storeId = null)
    {
        if (!$this->getCronConfig()->salesOrderQueue()->isEnabled($storeId)) {
            $schedule->setMessages($this->__('Sales Order Queue Cron is Disabled'));
            return false;
        }

        return parent::canRun($schedule, $storeId);
    }
}
