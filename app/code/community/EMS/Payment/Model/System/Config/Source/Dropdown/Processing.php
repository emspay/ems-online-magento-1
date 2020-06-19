<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_Model_System_Config_Source_Dropdown_Processing
    extends EMS_Payment_Model_System_Config_Source_AbstractSource
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options[] = array(
                'value' => '',
                'label' => Mage::helper('adminhtml')->__('-- Use Default --')
            );
            $statuses = $this->getStateStatuses(Mage_Sales_Model_Order::STATE_PROCESSING);
            foreach ($statuses as $code => $label) {
                $this->options[] = array(
                    'value' => $code,
                    'label' => $label
                );
            }
        }

        return $this->options;
    }

}