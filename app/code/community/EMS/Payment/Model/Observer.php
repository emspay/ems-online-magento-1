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

class EMS_Payment_Model_Observer
{

    /**
     * @var \GingerPayments\Payment\Client
     */
    protected $_emsAPI;

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

        require_once($this->_helper->getAutoloadPath());

        try {
            $this->_createEmsPaymentClient('default');
        } catch (\Exception $exception) {
            $this->_helper->log('Observer', $exception->getMessage(), 3);
        } catch (\Assert\AssertionFailedException $exception) {
            $this->_helper->log('Observer', $exception->getMessage(), 3);
        }
    }

    /**
     * @param $method
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function _createEmsPaymentClient($method)
    {
        $apiKey = $this->_getApiKeyForMethod($method);
        if ($apiKey !== '') {
            $this->_emsAPI = \GingerPayments\Payment\Ginger::createClient(
                $apiKey,
                Mage::getStoreConfig("payment/ems_payment/product")
            );

            if (Mage::getStoreConfig("payment/ems_payment/bundle_cacert")) {
                $this->_emsAPI->useBundledCA();
            }
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
        if ($method == 'ems_payment_klarna') {
            return Mage::getStoreConfig("payment/ems_payment/klarna_test_apikey")
                ?: Mage::getStoreConfig("payment/ems_payment/apikey");
        }

        if ($method == 'ems_payment_afterpay') {
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
     * @throws \Assert\AssertionFailedException
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
            $this->_helper->log('captureOrder', 'Failed to capture EMS Payment order: ' . $exception->getMessage());
            Mage::getSingleton('core/session')->addError($exception->getMessage());
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
        return in_array($method, array('ems_payment_klarna', 'ems_payment_afterpay'));
    }

    /**
     * @param $method
     * @param $emsOrderDataEmsId
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function _doOrderCapture($method, $emsOrderDataEmsId)
    {
        $this->_createEmsPaymentClient($method);
        $this->_emsAPI->setOrderCapturedStatus(
            $this->_emsAPI->getOrder(
                $emsOrderDataEmsId
            )
        );
    }
}
