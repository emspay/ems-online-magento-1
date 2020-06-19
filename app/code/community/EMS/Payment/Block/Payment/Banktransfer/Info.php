<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_Block_Payment_Banktransfer_Info extends Mage_Payment_Block_Info
{

    /**
     * @var string
     */
    protected $_mailingAddress;

    /**
     * Banktransfer template info block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ems_payment/info/banktransfer.phtml');
    }

    /**
     * @return string
     */
    public function getMailingAddress()
    {
        if (null === $this->_mailingAddress) {
            $this->_convertAdditionalData();
        }

        return $this->_mailingAddress;
    }

    /**
     * @return EMS_Payment_Block_Payment_Banktransfer_Info
     */
    protected function _convertAdditionalData()
    {
        $details = @unserialize($this->getInfo()->getAdditionalData());
        if (is_array($details)) {
            $this->_mailingAddress = isset($details['mailing_address']) ? (string) $details['mailing_address'] : '';
        } else {
            $this->_mailingAddress = '';
        }

        return $this;
    }
}
