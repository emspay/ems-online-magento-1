<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_WebhookController extends EMS_Payment_Controller_Action
{

    /**
     * @var Ginger\ApiClient
     */
    protected $_emsLib;

    /**
     * @var string
     */
    protected $_apiKey;

    /**
     * Initialization basic properties
     */
    public function _construct()
    {
        parent::_construct();
        $this->_apiKey = Mage::getStoreConfig("payment/ems_payment/apikey");

        if ($this->getRequest()->getParam('test') == 'klarna') {
            $this->_apiKey = Mage::getStoreConfig("payment/ems_payment/klarna_test_apikey") ?: null;
        }

        if ($this->getRequest()->getParam('test') == 'afterpay') {
            $this->_apiKey = Mage::getStoreConfig("payment/ems_payment/afterpay_test_apikey") ?: null;
        }

        $this->_emsLib = \Ginger\Ginger::createClient(
            EMS_Payment_Model_Abstract::ENDPOINT_EMS,
            $this->_apiKey
        );
    }

    /**
     *
     */
    public function indexAction()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $emsOrderId = isset($input['order_id']) ? $input['order_id'] : null;
        $event = isset($input['event']) ? $input['event'] : null;
        if ($emsOrderId === null || $event != 'status_changed') {
            return;
        }

        try {
            $transaction = $this->_emsLib->getOrder($emsOrderId);
            $this->_helper->log('webhook', $transaction);
            $transactionStatus = !empty($transaction['status']) ? $transaction['status'] : '';
            $orderId = !empty($transaction['merchant_order_id']) ? $transaction['merchant_order_id'] : '';
            /** @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->load($orderId);
            $order->getPayment()->setAdditionalInformation('ems_payment_order_status', $transactionStatus)->save();
            $storeId = $order->getStoreId();

            switch ($transactionStatus) {
                case 'completed':
                    $payment = $order->getPayment();
                    if (!$payment->getIsTransactionClosed()) {
                        $orderAmountCents = $this->_helper->getAmountInCents($order->getGrandTotal());
                        $amountPaidCents = $transaction['amount'];
                        if ($amountPaidCents == $orderAmountCents) {
                            $payment->setTransactionId($emsOrderId);
                            $payment->setCurrencyCode($order->getBaseCurrencyCode());
                            $payment->setIsTransactionClosed(true);
                            $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);

                            if ($status = Mage::getStoreConfig('payment/ems_payment/status_processing', $storeId)) {
                                $order->setStatus($status);
                            }

                            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->save();
                        }

                        if (!$order->getEmailSent()) {
                            $order->sendNewOrderEmail()->setEmailSent(true)->save();
                        }

                        /** @var Mage_Sales_Model_Order_Invoice $invoice */
                        $invoice = $payment->getCreatedInvoice();
                        $sendInvoice = Mage::getStoreConfig('payment/ems_payment/invoice_email', $storeId);

                        if ($invoice && $sendInvoice && !$invoice->getEmailSent()) {
                            $invoice->setEmailSent(true)->sendEmail()->save();
                        }
                    }
                    break;
                case 'cancelled':
                case 'expired':
                    if ($order->canCancel()) {
                        $order->cancel();
                        $order->setState(
                            Mage_Sales_Model_Order::STATE_CANCELED,
                            Mage_Sales_Model_Order::STATE_CANCELED,
                            Mage::helper('ems_payment')->__(EMS_Payment_Model_Abstract::PAYMENT_FLAG_CANCELLED),
                            true
                        )->save();
                    }
                    break;
                case 'error':
                    if ($order->canCancel()) {
                        $order->cancel();
                        $order->setState(
                            Mage_Sales_Model_Order::STATE_CANCELED,
                            Mage_Sales_Model_Order::STATE_CANCELED,
                            Mage::helper('ems_payment')->__(EMS_Payment_Model_Abstract::PAYMENT_FLAG_ERROR),
                            true
                        )->save();
                    }
                    break;
                case 'pending':
                case 'see-transactions':
                case 'new':
                default:
                    break;
            }
        } catch (Exception $exception) {
            $this->_helper->log('webhook', $exception->getMessage(), 3);
            if (isset($orderId)) {
                $this->getResponse()->setHttpResponseCode(503);
            }
        }
    }
}