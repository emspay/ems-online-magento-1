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

class EMS_Payment_Model_System_Config_Source_Dropdown_Email extends EMS_Payment_Model_System_Config_Source_AbstractSource
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = array(
                array(
                    'value' => '1',
                    'label' => $this->helper->__('Direct'),
                ),
                array(
                    'value' => '',
                    'label' => $this->helper->__('After payment'),
                ),

            );
        }

        return $this->options;
    }
}