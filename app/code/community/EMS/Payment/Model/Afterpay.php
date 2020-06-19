<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Afterpay payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Afterpay extends EMS_Payment_Model_Abstract
{

    /** Payment Method Code */
    const CODE = 'ems_payment_afterpay';

    /** Platform Method Code */
    const PLATFORM_CODE = 'afterpay';

    /** @var string Request Param Date of Birth */
    const REQUEST_PARAM_KEY_DOB = 'afterpay_dob';

    /** @var string Request Param Gender */
    const REQUEST_PARAM_KEY_GENDER = 'afterpay_gender';

    protected $_code = self::CODE;
    protected $_methodCode = self::PLATFORM_CODE;
    protected $_formBlockType = 'ems_payment/payment_afterpay_form';
    protected $_infoBlockType = 'ems_payment/payment_afterpay_info';
    protected $_paymentMethod = 'AfterPay';
    protected $_controller = 'afterpay';
    protected $_includeOrderLines = true;
    protected $_includeCustomerData = true;
    protected $_shouldRedirect = false;
    protected $_canRefundInvoicePartial = false;

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
        if (!Mage::getStoreConfig('payment/ems_payment_afterpay/active')) {
            return false;
        }

        $ipFilterList = Mage::getStoreConfig("payment/ems_payment_afterpay/ip_filter");
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
        if (Mage::registry('afterpay_dob')) {
            Mage::unregister('afterpay_dob');
        }

        if (Mage::registry('afterpay_gender')) {
            Mage::unregister('afterpay_gender');
        }

        Mage::register('afterpay_dob', Mage::app()->getRequest()->getParam('afterpay_dob'));
        Mage::register('afterpay_gender', Mage::app()->getRequest()->getParam('afterpay_gender'));

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
                'afterpay_dob'    => Mage::registry('afterpay_dob'),
                'afterpay_gender' => Mage::registry('afterpay_gender')
            )
        );
    }

    /**
     * Refunds payment online
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return EMS_Payment_Model_Afterpay
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();

        if ($creditmemo->getAdjustmentPositive() != 0 || $creditmemo->getAdjustmentNegative() != 0) {
            $msg = $this->_helper->__('EMS does not accept adjustment fees for refunds using order lines');
            throw new Mage_Core_Exception($msg);
        }

        if ($creditmemo->getShippingAmount() > 0 &&
            ($creditmemo->getShippingAmount() != $creditmemo->getBaseShippingInclTax())
        ) {
            $msg = $this->_helper->__('EMS does not accept adjustment fees for shipments using order lines');
            throw new Mage_Core_Exception($msg);
        }

        try {
            $addShipping = $creditmemo->getShippingAmount() > 0 ? 1 : 0;
            $this->_emsLib->refundOrder(
                $payment->getOrder()->getEmsPaymentOrderId(),
                array(
                    'order_lines' => $this->_getRefundLines($creditmemo->getAllItems(), $addShipping)
                )
            );
        } catch (Exception $exception) {
            $this->_helper->log('refund', $exception->getMessage(), 3);
            throw new Mage_Core_Exception($exception->getMessage());
        }

        return $this;
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
     * @throws Mage_Core_Exception
     */
    protected function _createOrderBefore()
    {
        parent::_createOrderBefore();
        $customer = $this->_getCustomerData($this->_mageOrder);

        if (!$customer['gender']) {
            $this->_errorMessage = $this->_helper->__('%s: Gender is required!', $this->_paymentMethod);
            Mage::throwException(
                $this->getErrorMessage()
            );
        }

        if (!$customer['birthdate']) {
            $this->_errorMessage = $this->_helper->__('%s: Date of birth is required!', $this->_paymentMethod);
            Mage::throwException(
                $this->getErrorMessage()
            );
        }
    }

    /**
     * Build constructor
     */
    protected function _construct()
    {
        $this->_apiKey = Mage::getStoreConfig("payment/ems_payment/afterpay_test_apikey") ?: null;
        parent::_construct();
    }
}
