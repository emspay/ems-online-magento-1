<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_Block_Payment_Banktransfer_Form extends Mage_Payment_Block_Form
{
    /**
     * Sets Banktransfer template into the checkout page
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ems_payment/form/banktransfer.phtml');
    }
}
