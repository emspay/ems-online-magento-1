<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Banktransfer payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Banktransfer extends EMS_Payment_Model_Abstract
{
    /** Payment Method Code */
    const CODE = 'ems_payment_banktransfer';

    /** Platform Method Code */
    const PLATFORM_CODE = 'bank-transfer';

    protected $_code = self::CODE;
    protected $_methodCode = self::PLATFORM_CODE;
    protected $_formBlockType = 'ems_payment/payment_banktransfer_form';
    protected $_infoBlockType = 'ems_payment/payment_banktransfer_info';
    protected $_paymentMethod = 'Banktransfer';
    protected $_controller = 'banktransfer';
    protected $_shouldRedirect = false;

    /**
     * @return string
     */
    public function getMailingAddress()
    {
        return implode(
            PHP_EOL, array(
            $this->_helper->__('Amount: %AMOUNT%'),
            $this->_helper->__('Reference: %REFERENCE%'),
            $this->_helper->__('IBAN: %IBAN%'),
            $this->_helper->__('BIC: %BIC%'),
            $this->_helper->__('Account holder: %HOLDER%'),
            $this->_helper->__('City: %CITY%'),
            )
        );
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getSuccessHtml(Mage_Sales_Model_Order $order)
    {
        if ($order->getPayment()->getMethodInstance() instanceof EMS_Payment_Model_Banktransfer) {
            $additionalData = @unserialize(
                $order->getPayment()->getMethodInstance()->getInfoInstance()->getAdditionalData()
            );
            if (!empty($additionalData['mailing_address'])) {
                return $additionalData['mailing_address'];
            }
        }

        return '';
    }
}
