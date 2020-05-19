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

class EMS_Payment_WebhookController extends EMS_Payment_Controller_Action
{

    /** @var GingerPayments\Payment\Client */
    protected $_emsLib;
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

        if ($this->_apiKey !== '') {
            $this->_emsLib = GingerPayments\Payment\Ginger::createClient(
                $this->_apiKey,
                Mage::getStoreConfig("payment/ems_payment/product")
            );

            if (Mage::getStoreConfig("payment/ems_payment/bundle_cacert")) {
                $this->_emsLib->useBundledCA();
            }
        }
    }

    /**
     *
     */
    public function indexAction()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $this->_helper->log('webhook', $input); //Remove once done

        $emsOrderId = isset($input['order_id']) ? $input['order_id'] : null;
        $event = isset($input['event']) ? $input['event'] : null;
        if ($emsOrderId === null || $event != 'status_changed') {
            return;
        }

        try {
            /** @var GingerPayments\Payment\Order */
            $emsOrder = $this->_emsLib->getOrder($emsOrderId);
            $this->_helper->log('webhook', $emsOrder->toArray());

            $orderId = $emsOrder->getMerchantOrderId();
            $orderStatus = $emsOrder->getStatus();

            /** @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $order->getPayment()->setAdditionalInformation('ems_payment_order_status', $orderStatus)->save();
            $storeId = $order->getStoreId();

            switch ($orderStatus) {
                case 'completed':
                    $payment = $order->getPayment();
                    if (!$payment->getIsTransactionClosed()) {
                        $orderAmountCents = $this->_helper->getAmountInCents($order->getGrandTotal());
                        $amountPaidCents = $emsOrder->getAmount();
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
                case 'pending':
                case 'see-transactions':
                case 'new':
                default:
                    break;
            }
        } catch (Exception $exception) {
            $this->_helper->log('webhook', $exception, 3);
            if (isset($orderId)) {
                $this->getResponse()->setHttpResponseCode(503);
            }
        }
    }
}