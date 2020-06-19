<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_Block_Payment_Klarna_Form extends Mage_Payment_Block_Form
{
    /**
     * Sets Klarna template into the checkout page
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ems_payment/form/klarna.phtml');
    }
}
