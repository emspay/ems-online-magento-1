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

class EMS_Payment_Block_Payment_Cashondelivery_Info extends Mage_Payment_Block_Info
{

    protected $_mailingAddress;

    /**
     *
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
     * Initialization block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ems_payment/info/cashondelivery.phtml');
    }

    /**
     * @return EMS_Payment_Block_Payment_Cashondelivery_Info
     */
    protected function _convertAdditionalData()
    {
        $details = @unserialize($this->getInfo()->getAdditionalData());
        if (is_array($details)) {
            $this->_mailingAddress = isset($details['mailing_address']) ? (string)$details['mailing_address'] : '';
        } else {
            $this->_mailingAddress = '';
        }

        return $this;
    }
}
