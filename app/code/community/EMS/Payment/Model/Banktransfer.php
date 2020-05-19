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

/**
 * Banktransfer payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Banktransfer extends EMS_Payment_Model_Abstract
{

    protected $_code = 'ems_payment_banktransfer';
    protected $_formBlockType = 'ems_payment/payment_banktransfer_form';
    protected $_infoBlockType = 'ems_payment/payment_banktransfer_info';
    protected $_paymentMethod = 'Banktransfer';
    protected $_controller = 'banktransfer';

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
     * {@inheritDoc}
     */
    public function createOrder()
    {
        $this->_createOrderBefore();

        /** @var GingerPayments\Payment\Order $emsOrder */
        $emsOrder = $this->_emsLib->createSepaOrder(
            $this->_helper->getAmountInCents($this->getAmount()),
            $this->_mageOrder->getOrderCurrencyCode(),
            array(),
            $this->getDescription(),
            $this->getOrderId(),
            null,
            null,
            $this->_getCustomerData($this->_mageOrder),
            array('plugin' => $this->_helper->getPluginVersion()),
            $this->_webhookUrl
        );

        $this->_mageOrder->setEmsPaymentOrderId($emsOrder->getId())->save();
        $this->_helper->log('createOrder', $emsOrder->toArray());

        if ($emsOrder->status()->isError()) {
            Mage::throwException($this->_helper->__(static::ERROR_MESSAGE_START));
        }

        if (!($reference = $emsOrder->transactions()->current()->paymentMethodDetails()->reference())) {
            Mage::throwException($this->_helper->__(static::ERROR_MESSAGE_START));
        }

        $this->_setAdditionalData($reference);
        $this->setOrderId($emsOrder->getId());
        $this->_createOrderAfter();

        $this->_mageOrder->addStatusToHistory($this->_mageOrder->getStatus(), 'Reference: ' . $reference)->save();
    }

    /**
     * Update Order Information
     *
     * @param $reference
     *
     * @throws Mage_Core_Exception
     */
    protected function _setAdditionalData($reference)
    {
        if ($this->_mageOrder->getPayment()->getMethodInstance() instanceof EMS_Payment_Model_Banktransfer
            && $paymentBlock = $this->_mageOrder->getPayment()->getMethodInstance()->getMailingAddress()
        ) {
            $details = array();
            $storeId = $this->_mageOrder->getStoreId();
            $amountStr = $this->_mageOrder->getOrderCurrency()->formatTxt($this->_mageOrder->getGrandTotal());

            $accountDetails = Mage::getStoreConfig("payment/ems_payment_banktransfer/account_details", $storeId);
            $paymentBlock = str_replace('%AMOUNT%', $amountStr, $paymentBlock);
            $paymentBlock = str_replace('%REFERENCE%', $reference, $paymentBlock);
            $paymentBlock = str_replace('%IBAN%', $accountDetails['iban'], $paymentBlock);
            $paymentBlock = str_replace('%BIC%', $accountDetails['bic'], $paymentBlock);
            $paymentBlock = str_replace('%HOLDER%', $accountDetails['holder'], $paymentBlock);
            $paymentBlock = str_replace('%CITY%', $accountDetails['city'], $paymentBlock);
            $paymentBlock = str_replace('\n', PHP_EOL, $paymentBlock);

            $details['mailing_address'] = $paymentBlock;
            if (!empty($details)) {
                $this->_mageOrder->getPayment()
                    ->getMethodInstance()
                    ->getInfoInstance()
                    ->setAdditionalData(serialize($details));
            }
        }
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
            $additionalData = @unserialize($order->getPayment()->getMethodInstance()->getInfoInstance()->getAdditionalData());
            if (!empty($additionalData['mailing_address'])) {
                return $additionalData['mailing_address'];
            }
        }

        return '';
    }
}
