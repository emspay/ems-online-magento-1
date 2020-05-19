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
 * Cashondelivery payment method model
 *
 * @method string getReturnUrl()
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Cashondelivery extends EMS_Payment_Model_Abstract
{

    protected $_code = 'ems_payment_cashondelivery';
    protected $_formBlockType = 'ems_payment/payment_cashondelivery_form';
    protected $_infoBlockType = 'ems_payment/payment_cashondelivery_info';
    protected $_paymentMethod = 'Cashondelivery';
    protected $_controller = 'cashondelivery';

    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    /**
     * @return string
     */
    public function getMailingAddress()
    {
        $paymentBlock = $this->_helper->__('Amount:') . ' %AMOUNT%' . PHP_EOL;
        return $paymentBlock;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrder()
    {
        $this->_createOrderBefore();
        $this->_setAdditionalData();
        $this->setOrderId($this->getOrderId());
        $this->_createOrderAfter();
        $this->_createInvoiceForOrder();
    }

    /**
     * Update Order Information
     *
     * @throws Mage_Core_Exception
     */
    protected function _setAdditionalData()
    {
        if ($this->_mageOrder->getPayment()->getMethodInstance() instanceof EMS_Payment_Model_Cashondelivery
            && $paymentBlock = $this->_mageOrder->getPayment()->getMethodInstance()->getMailingAddress()
        ) {
            $details = array();
            $amountStr = $this->_mageOrder->getOrderCurrency()->formatTxt($this->_mageOrder->getGrandTotal());
            $paymentBlock = str_replace('%AMOUNT%', $amountStr, $paymentBlock);
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
     * @throws Exception
     */
    protected function _createInvoiceForOrder()
    {
        $storeId = $this->_mageOrder->getStoreId();
        $autoInvoice = Mage::getStoreConfig('payment/ems_payment_cashondelivery/auto_invoice', $storeId);
        if (!$autoInvoice) {
            return;
        }

        $payment = $this->_mageOrder->getPayment();

        if (!$payment->getIsTransactionClosed()) {
            $payment->setTransactionId($this->getOrderId());
            $payment->setCurrencyCode($this->_mageOrder->getBaseCurrencyCode());
            $payment->setIsTransactionClosed(true);
            $payment->registerCaptureNotification($this->_mageOrder->getBaseGrandTotal(), true);

            if ($status = Mage::getStoreConfig('payment/ems_payment_cashondelivery/status_processing', $storeId)) {
                $this->_mageOrder->setStatus($status);
            }

            $this->_mageOrder->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->save();

            if (!$this->_mageOrder->getEmailSent()) {
                $this->_mageOrder->sendNewOrderEmail()->setEmailSent(true)->save();
            }

            /** @var Mage_Sales_Model_Order_Invoice $invoice */
            $invoice = $payment->getCreatedInvoice();
            $sendInvoice = Mage::getStoreConfig('payment/ems_payment/invoice_email', $storeId);
            if ($invoice && $sendInvoice && !$invoice->getEmailSent()) {
                $invoice->setEmailSent(true)->sendEmail()->save();
            }
        }
    }

}
