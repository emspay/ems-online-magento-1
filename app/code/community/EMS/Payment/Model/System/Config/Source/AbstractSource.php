<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_Model_System_Config_Source_AbstractSource
{

    /**
     * @var EMS_Payment_Helper_Data
     */
    public $helper;

    /**
     * @var array
     */
    public $options = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('ems_payment');
    }

    /**
     * @param $state
     *
     * @return array
     */
    public function getStateStatuses($state)
    {
        return Mage::getSingleton('sales/order_config')->getStateStatuses($state);
    }
}