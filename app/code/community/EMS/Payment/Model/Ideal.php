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
 * iDEAL payment method model
 *
 * @method string getIssuerId()
 * @method EMS_Payment_Model_Ideal setIssuerId(string $issuerId)
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Ideal extends EMS_Payment_Model_Abstract
{

    protected $_code = 'ems_payment_ideal';
    protected $_formBlockType = 'ems_payment/payment_ideal_form';
    protected $_infoBlockType = 'ems_payment/payment_ideal_info';
    protected $_paymentMethod = 'iDEAL';
    protected $_controller = 'ideal';

    protected $_canRefund = true;
    protected $_canCapture = true;

    /**
     * On click payment button, this function is called to assign data
     *
     * @param $data
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        if (Mage::registry('issuer_id')) {
            Mage::unregister('issuer_id');
        }

        Mage::register('issuer_id', Mage::app()->getRequest()->getParam('issuer_id'));

        return $this;
    }

    /**
     * Retrieves redirect url for client by click 'Place Order' to selected iDEAL method
     *
     * @param array $array
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl($array = array())
    {
        return parent::getOrderPlaceRedirectUrl(
            array(
                'issuer_id' => Mage::registry('issuer_id')
            )
        );
    }

    /**
     * Fetch the list of issuers
     *
     * @return null|array
     */
    public function getIssuers()
    {
        return $this->_emsLib->getIdealIssuers()->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function createOrder()
    {
        $this->_createOrderBefore();

        /** @var GingerPayments\Payment\Order $emsOrder */
        $emsOrder = $this->_emsLib->createIdealOrder(
            $this->_helper->getAmountInCents($this->getAmount()),
            $this->_mageOrder->getOrderCurrencyCode(),
            $this->getIssuerId(),
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

    /**
     * {@inheritDoc}
     */
    protected function _createOrderBefore()
    {
        parent::_createOrderBefore();
        $issuerId = Mage::app()->getRequest()->getParam('issuer_id');

        if (!$this->setIssuerId($issuerId)->hasIssuerId()) {
            $this->_errorMessage = $this->_helper->__('Error in the given payment data');
            Mage::throwException(
                $this->getErrorMessage()
            );
        }
    }

    /**
     * Retrieves true if model has Issuer ID
     *
     * @return bool
     */
    protected function hasIssuerId()
    {
        return (bool)$this->getIssuerId();
    }
}
