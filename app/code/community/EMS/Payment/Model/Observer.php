<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_Model_Observer
{

    /**
     * @var Ginger\ApiClient
     */
    protected $_emsLib;

    /**
     * @var EMS_Payment_Helper_Data
     */
    protected $_helper;

    /**
     * EMS_Payment_Model_Observer constructor.
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('ems_payment');

        try {
            $this->_createEmsPaymentClient('default');
        } catch (\Exception $exception) {
            $this->_helper->log('Observer', $exception->getMessage(), 3);
        }
    }

    /**
     * @param $method
     */
    protected function _createEmsPaymentClient($method)
    {
        $apiKey = $this->_getApiKeyForMethod($method);
        if ($apiKey !== '') {
            $this->_emsLib = \Ginger\Ginger::createClient(EMS_Payment_Model_Abstract::ENDPOINT_EMS, $apiKey);
        }
    }

    /**
     * Method resolves the api key by method
     *
     * @param string $method
     *
     * @return string
     */
    protected function _getApiKeyForMethod($method)
    {
        if ($method == EMS_Payment_Model_Klarnapaylater::CODE) {
            return Mage::getStoreConfig("payment/ems_payment/klarna_test_apikey")
                ?: Mage::getStoreConfig("payment/ems_payment/apikey");
        }

        if ($method == EMS_Payment_Model_Afterpay::CODE) {
            return Mage::getStoreConfig("payment/ems_payment/afterpay_test_apikey")
                ?: Mage::getStoreConfig("payment/ems_payment/apikey");
        }

        return Mage::getStoreConfig("payment/ems_payment/apikey");
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function convertPayment(Varien_Event_Observer $observer)
    {
        $orderPayment = $observer->getEvent()->getOrderPayment();
        $quotePayment = $observer->getEvent()->getQuotePayment();

        $orderPayment->setEmsPaymentOrderId($quotePayment->getEmsPaymentOrderId());
        $orderPayment->setEmsPaymentBanktransferReference($quotePayment->getEmsPaymentBanktransferReference());
        $orderPayment->setEmsPaymentIdealIssuerId($quotePayment->getEmsPaymentIdealIssuerId());

        return $this;
    }

    /**
     * Set Klarna/AfterPay order to 'captured' in EMS Payment
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function captureOrder(Varien_Event_Observer $observer)
    {
        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getEvent()->getShipment()->getOrder();
            if ($this->_isCaptureOrderAllowed($order->getPayment()->getMethod())) {
                $this->_doOrderCapture($order->getPayment()->getMethod(), $order->getData('ems_payment_order_id'));
            }
        } catch (\Exception $exception) {
            $this->_helper->log(
                'captureOrder',
                'Failed to capture EMS Payment order: ' . $exception->getMessage()
            );

            $msg = $this->_helper->__('Unable to capture payment for this order, full detail: var/log/ems-payment.log');
            Mage::getSingleton('core/session')->addError($msg);
        }

        return $this;
    }

    /**
     * @param $method
     *
     * @return bool
     */
    protected function _isCaptureOrderAllowed($method)
    {
        return in_array($method, array(EMS_Payment_Model_Klarnapaylater::CODE, EMS_Payment_Model_Afterpay::CODE));
    }

    /**
     * @param $method
     * @param $emsOrderDataEmsId
     */
    protected function _doOrderCapture($method, $emsOrderDataEmsId)
    {
        $this->_createEmsPaymentClient($method);
        $transaction = $this->_emsLib->getOrder($emsOrderDataEmsId);
        $orderId = $transaction['id'];
        $transactionId = !empty(current($transaction['transactions'])) ? current($transaction['transactions'])['id'] : null;
        $this->_emsLib->captureOrderTransaction($orderId, $transactionId);
    }
}
