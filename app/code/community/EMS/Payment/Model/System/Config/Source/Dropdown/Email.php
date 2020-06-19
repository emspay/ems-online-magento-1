<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_Model_System_Config_Source_Dropdown_Email
    extends EMS_Payment_Model_System_Config_Source_AbstractSource
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