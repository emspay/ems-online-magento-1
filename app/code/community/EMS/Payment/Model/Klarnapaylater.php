<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Klarna Pay Later payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Klarnapaylater extends EMS_Payment_Model_Abstract
{

    /** Payment Method Code */
    const CODE = 'ems_payment_klarnapaylater';

    /** Platform Method Code */
    const PLATFORM_CODE = 'klarna-pay-later';

    const REQUEST_PARAM_KEY_DOB = 'klarna_dob';
    const REQUEST_PARAM_KEY_GENDER = 'klarna_gender';

    protected $_code = self::CODE;
    protected $_methodCode = self::PLATFORM_CODE;
    protected $_formBlockType = 'ems_payment/payment_klarna_form';
    protected $_infoBlockType = 'ems_payment/payment_klarna_info';
    protected $_paymentMethod = 'Klarna Pay Later';
    protected $_controller = 'klarnapaylater';
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
        if (!Mage::getStoreConfig('payment/ems_payment_klarnapaylater/active')) {
            return false;
        }

        $ipFilterList = Mage::getStoreConfig("payment/ems_payment_klarnapaylater/ip_filter");
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
        Mage::register('klarna_gender', Mage::app()->getRequest()->getParam('klarna_gender'));

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
     * Refunds payment online
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return EMS_Payment_Model_Klarnapaylater
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

        if ($creditmemo->getShippingAmount() > 0
            && ($creditmemo->getShippingAmount() != $creditmemo->getBaseShippingInclTax())
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
                'merchant_order_line_id' => (string)$item->getOrderItemId(),
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
     * Build constructor
     */
    protected function _construct()
    {
        $this->_apiKey = Mage::getStoreConfig("payment/ems_payment/klarna_test_apikey") ?: null;
        parent::_construct();
    }
}
