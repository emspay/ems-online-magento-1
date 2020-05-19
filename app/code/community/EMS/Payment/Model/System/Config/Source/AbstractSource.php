<?php
/**
 *   ╲          ╱
 * ╭──────────────╮  COPYRIGHT (C) 2019 GINGER PAYMENTS B.V.
 * │╭──╮      ╭──╮│
 * ││//│      │//││
 * │╰──╯      ╰──╯│
 * ╰──────────────╯
 *   ╭──────────╮    The MIT License (MIT)
 *   │ () () () │
 *
 * @category    EMS
 * @package     EMS_Payment
 * @author      Ginger Payments B.V. (info@gingerpayments.com)
 * @copyright   COPYRIGHT (C) 2019 GINGER PAYMENTS B.V. (https://www.gingerpayments.com)
 * @license     The MIT License (MIT)
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