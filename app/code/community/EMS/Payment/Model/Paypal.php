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
 * Paypal payment method model
 *
 * @method string getReturnUrl()
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Paypal extends EMS_Payment_Model_Abstract
{

    protected $_code = 'ems_payment_paypal';
    protected $_paymentMethod = 'PayPal';
    protected $_controller = 'paypal';

    protected $_canRefund = true;
    protected $_canCapture = true;

    /**
     * {@inheritDoc}
     */
    public function createOrder()
    {
        $this->_createOrderBefore();

        /** @var GingerPayments\Payment\Order $emsOrder */
        $emsOrder = $this->_emsLib->createPaypalOrder(
            $this->_helper->getAmountInCents($this->getAmount()),
            $this->_mageOrder->getOrderCurrencyCode(),
            array(),
            $this->getDescription(),
            $this->getOrderId(),
            $this->getReturnUrl(),
            null,
            $this->_getCustomerData($this->_mageOrder),
            array('plugin' => $this->_helper->getPluginVersion()),
            $this->_webhookUrl
        );

        $this->_helper->log('createOrder', $emsOrder->toArray());
        if ($emsOrder->status()->isError()) {
            Mage::throwException($this->_helper->__(static::ERROR_MESSAGE_START));
        }

        $this->setOrderId($emsOrder->getId());
        $this->setPaymentUrl($emsOrder->firstTransactionPaymentUrl());
        $this->_createOrderAfter();
    }
}