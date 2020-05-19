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
 * Klarna payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Klarna extends EMS_Payment_Model_Abstract
{

    /**#@+
     * Constants
     */
    const REQUEST_PARAM_KEY_DOB = 'klarna_dob';
    const REQUEST_PARAM_KEY_GENDER = 'klarna_gender';
    /**#@-*/

    protected $_code = 'ems_payment_klarna';
    protected $_formBlockType = 'ems_payment/payment_klarna_form';
    protected $_infoBlockType = 'ems_payment/payment_klarna_info';
    protected $_paymentMethod = 'Klarna';
    protected $_controller = 'klarna';

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (!$this->_ipAllowed()) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Function checks if payment method is allowed for current IP.
     *
     * @return bool
     */
    protected function _ipAllowed()
    {
        if (!Mage::getStoreConfig('payment/ems_payment_klarna/active')) {
            return false;
        }

        $ipFilterList = Mage::getStoreConfig("payment/ems_payment_klarna/ip_filter");
        if ($ipFilterList !== '') {
            $ipWhitelist = array_map('trim', explode(",", $ipFilterList));
            if (!in_array(Mage::helper('core/http')->getRemoteAddr(), $ipWhitelist)) {
                return false;
            }
        }

        return true;
    }

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
        if (Mage::registry('klarna_dob')) {
            Mage::unregister('klarna_dob');
        }

        if (Mage::registry('klarna_gender')) {
            Mage::unregister('klarna_gender');
        }

        Mage::register('klarna_dob', Mage::app()->getRequest()->getParam('klarna_dob'));
        Mage::register('klarna_gender', Mage::app()->getRequest()->getParam('gender'));

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
                'klarna_dob'    => Mage::registry('klarna_dob'),
                'klarna_gender' => Mage::registry('klarna_gender')
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
        $emsOrder = $this->_emsLib->createKlarnaOrder(
            $this->_helper->getAmountInCents($this->getAmount()),
            $this->_mageOrder->getOrderCurrencyCode(),
            $this->getDescription(),
            $this->getOrderId(),
            null,
            null,
            $this->_getCustomerData($this->_mageOrder),
            array('plugin' => $this->_helper->getPluginVersion()),
            $this->_webhookUrl,
            $this->_getOrderLines($this->_mageOrder)
        );

        $this->_helper->log('createOrder', $emsOrder->toArray());

        if ($emsOrder->status()->isError()) {
            $this->_errorMessage = $emsOrder->transactions()->current()->reason()->toString();
            Mage::throwException($this->getErrorMessage());
        } elseif ($emsOrder->status()->isCancelled()) {
            $this->_errorMessage = $this->_helper->__(
                'Unfortunately, we can not currently accept your purchase with Klarna. 
                Please choose another payment option to complete your order. 
                We apologize for the inconvenience.'
            );
            Mage::throwException($this->getErrorMessage());
        }

        $this->setOrderId($emsOrder->getId());
        $this->_createOrderAfter();
    }

    /**
     * @throws Mage_Core_Exception
     */
    protected function _createOrderBefore()
    {
        parent::_createOrderBefore();
        $customer = $this->_getCustomerData($this->_mageOrder);

        if (!$customer['gender']) {
            $this->_errorMessage = $this->_helper->__('%1: Gender is required!', $this->_paymentMethod);
            Mage::throwException(
                $this->getErrorMessage()
            );
        }

        if (!$customer['birthdate']) {
            $this->_errorMessage = $this->_helper->__('%1: Date of birth is required!', $this->_paymentMethod);
            Mage::throwException(
                $this->getErrorMessage()
            );
        }
    }

    /**
     * Refunds payment online
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return Mage_Payment_Model_Abstract
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();

        if ($creditmemo->getAdjustmentPositive() != 0 || $creditmemo->getAdjustmentNegative() != 0) {
            $msg = $this->_helper->__('Api does not accept adjustment fees for refunds using order lines');
            throw new Mage_Core_Exception($msg);
        }

        if ($creditmemo->getShippingAmount() > 0 && ($creditmemo->getShippingAmount() != $creditmemo->getBaseShippingInclTax())) {
            $msg = $this->_helper->__('Api does not accept adjustment fees for shipments using order lines');
            throw new Mage_Core_Exception($msg);
        }

        try {
            $paymentModel = parent::refund($payment, $amount);
            $addShipping = $creditmemo->getShippingAmount() > 0 ? 1 : 0;
            $this->_emsLib->createOrderRefund(
                $payment->getOrder()->getEmsPaymentOrderId(),
                null,
                $this->_getRefundLines($creditmemo->getAllItems(), $addShipping)
            );
            return $paymentModel;
        } catch (GingerPayments\Payment\Client\OrderNotFoundException $exception) {
            throw new Mage_Core_Exception($exception->getMessage());
        } catch (GingerPayments\Payment\Client\ClientException $exception) {
            throw new Mage_Core_Exception($exception->getMessage());
        }
    }

    /**
     * Prepares refund items
     *
     * @param array $refundItems
     * @param bool  $addShipping
     *
     * @return array
     */
    protected function _getRefundLines(array $refundItems, $addShipping = false)
    {
        $orderLines = array();

        /** @var Mage_Sales_Model_Order_Creditmemo_Item $item */
        foreach ($refundItems as $item) {
            $orderLines[] = array(
                'merchant_order_line_id' => $item->getOrderItemId(),
                'quantity'               => $item->getQty()
            );
        }

        if ($addShipping) {
            $orderLines[] = array(
                'merchant_order_line_id' => 'shipping',
                'quantity'               => '1'
            );
        }

        return $orderLines;
    }

    /**
     * Build constructor
     */
    protected function _construct()
    {
        $this->_apiKey = Mage::getStoreConfig("payment/ems_payment/klarna_test_apikey") ?: null;
        parent::_construct();
    }
}
