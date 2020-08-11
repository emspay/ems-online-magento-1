<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Abstract model for all EMS payment methods
 *
 * @method string getOrderId()
 * @method EMS_Payment_Model_Abstract setOrderId(string $orderId)
 * @method string getAmount()
 * @method EMS_Payment_Model_Abstract setAmount(string $amount)
 * @method string getDescription()
 * @method string getReturnURL()
 * @method string getPaymentUrl()
 * @method EMS_Payment_Model_Abstract setPaymentUrl(string $paymentUrl)
 *
 * @category    EMS
 * @package     EMS_Payment
 */
abstract class EMS_Payment_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{

    const ENDPOINT_EMS = 'https://api.online.emspay.eu/';
    const PAYMENT_FLAG_PENDEMS = "Payment is pending";
    const PAYMENT_FLAG_CANCELLED = "Payment is cancelled";
    const PAYMENT_FLAG_ERROR = "Payment is cancelled because or error";

    const ERROR_MESSAGE_START = 'Could not start transaction. Contact the owner.';
    const ERROR_NO_TRANSACTION = 'Could not fetch transaction ID. Please try again.';
    const ERROR_NO_REDIRECT = 'Could not fetch redirect url. Please try again';

    /**
     * @var EMS_Payment_Helper_Data
     */
    protected $_helper;

    /**
     * @var Mage_Core_Helper_String
     */
    protected $_string;

    /**
     * @var Mage_Core_Helper_Http
     */
    protected $_coreHttp;

    /**
     * @var Ginger\ApiClient
     */
    protected $_emsLib;

    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_mageOrder;

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapture = true;
    protected $_errorMessage = '';

    protected $_apiKey = null;
    protected $_controller;
    protected $_webhookUrl;
    protected $_transaction;
    protected $_includeOrderLines = false;
    protected $_includeCustomerData = false;
    protected $_shouldRedirect = true;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_construct();
    }

    /**
     * Model initialization
     */
    protected function _construct()
    {
        $this->_helper = Mage::helper('ems_payment');
        $this->_coreHttp = Mage::helper('core/http');
        $this->_string = Mage::helper('core/string');

        if (null === $this->_apiKey) {
            $this->_apiKey = Mage::getStoreConfig("payment/ems_payment/apikey");
        }

        try {
            if ($this->_apiKey !== '') {
                $this->_emsLib = \Ginger\Ginger::createClient(self::ENDPOINT_EMS, $this->_apiKey);
            }
        } catch (\Exception $exception) {
            $this->_emsLib = null;
            $this->_helper->log('Load API', $exception->getMessage(), 3);
        }

        parent::_construct();
    }

    /**
     * Creates EMS order, saves data to Magento order and sets redirect url
     *
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     * @throws Exception
     */
    public function createOrder()
    {
        $this->_createOrderBefore();

        $orderData = array(
            'amount'            => $this->_helper->getAmountInCents($this->getAmount()),
            'currency'          => $this->_mageOrder->getOrderCurrencyCode(),
            'description'       => $this->getDescription(),
            'merchant_order_id' => $this->_mageOrder->getEntityId(),
            'return_url'        => $this->getReturnUrl(),
            'webhook_url'       => $this->_webhookUrl,
            'transactions'      => array(array('payment_method' => $this->_methodCode)),
            'extra'             => array('plugin' => $this->_helper->getPluginVersion()),
        );

        if ($this->_includeOrderLines) {
            $orderData += array('order_lines' => $this->_getOrderLines($this->_mageOrder));
        }

        if ($this->_includeCustomerData) {
            $orderData += array('customer' => $this->_getCustomerData($this->_mageOrder));
        }

        if ($this->getIssuerId()) {
            $orderData['transactions'] = array(
                array(
                    'payment_method'         => $this->_methodCode,
                    'payment_method_details' => array('issuer_id' => $this->getIssuerId())
                )
            );
        }

        $this->_helper->log('createOrder', $orderData);
        $this->_transaction = $this->_emsLib->createOrder($orderData);
        $this->_processRequest();
    }

    /**
     * Prepares and validates data before creation EMS order
     *
     * @return void
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _createOrderBefore()
    {
        $this->_mageOrder = $this->_loadMagentoOrderFromSession();

        if (!$this->_mageOrder->getId()) {
            $this->_errorMessage = $this->_helper->__('Processing order not found');
            $this->_helper->log('orderBefore', $this->getErrorMessage(), 3);
            Mage::throwException($this->getErrorMessage());
        }

        $description = $this->_helper->__(
            '#%s at %s', $this->_mageOrder->getIncrementId(),
            Mage::app()->getStore()->getName()
        );

        $this->_webhookUrl = Mage::getUrl('ems_payment/webhook');

        if ($this->_code == EMS_Payment_Model_Klarnapaylater::CODE) {
            $klarnaTestKey = Mage::getStoreConfig("payment/ems_payment/klarna_test_apikey");
            if (!empty($klarnaTestKey)) {
                $this->_webhookUrl .= '?test=klarna';
            }
        }

        if ($this->_code == EMS_Payment_Model_Afterpay::CODE) {
            $afterpayTestKey = Mage::getStoreConfig("payment/ems_payment/afterpay_test_apikey");
            if (!empty($afterpayTestKey)) {
                $this->_webhookUrl .= '?test=afterpay';
            }
        }

        $returnUrl = Mage::getUrl('ems_payment/return');

        if (!$this->setOrderId($this->_mageOrder->getIncrementId())->hasOrderId()
            || !$this->setAmount($this->_mageOrder->getGrandTotal())->hasAmount()
            || !$this->setDescription($description)->hasDescription()
            || !$this->setReturnUrl($returnUrl)->hasReturnUrl()
        ) {
            $this->_errorMessage = $this->_helper->__('Error in the given payment data');
            Mage::throwException($this->getErrorMessage());
        }
    }

    /**
     * Load Order from Session
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _loadMagentoOrderFromSession()
    {
        /** @var Mage_Sales_Model_Order $orderModel */
        $orderModel = Mage::getModel('sales/order');
        return $orderModel->loadByIncrementId($this->_getCheckout()->getLastRealOrderId());
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        return $session;
    }

    /**
     * Retrieves error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    /**
     * Retrieves true if model has order ID
     *
     * @return bool
     */
    protected function hasOrderId()
    {
        return (bool)$this->getOrderId();
    }

    /**
     * Retrieves true if model has amount and amount is not 0
     *
     * @return bool
     */
    protected function hasAmount()
    {
        return (bool)$this->getAmount();
    }

    /**
     * Retrieves true if model has description and description is not empty
     *
     * @return bool
     */
    protected function hasDescription()
    {
        return (bool)$this->getDescription();
    }

    /**
     * Sets order description
     *
     * @param $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        return $this->setData('description', $this->_string->truncate($description, 29, ''));
    }

    /**
     * Retrieves true if model has Return URL
     *
     * @return bool
     */
    protected function hasReturnUrl()
    {
        return (bool)$this->getReturnUrl();
    }

    /**
     * @param $returnUrl
     *
     * @return self|Varien_Object
     */
    public function setReturnUrl($returnUrl)
    {
        if (!preg_match('|(\w+)://([^/:]+)(:\d+)?(.*)|', $returnUrl)) {
            return $this;
        }

        return $this->setData('return_url', $returnUrl);
    }

    /**
     * Retrieves order lines
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    protected function _getOrderLines(Mage_Sales_Model_Order $order)
    {
        $orderLines = array();

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $orderLines[] = array(
                'type'                   => 'physical',
                'url'                    => $item->getProduct()->getProductUrl(),
                'name'                   => (string)$item->getName(),
                'amount'                 => $this->_helper->getItemTotalAmount($item),
                'currency'               => $order->getOrderCurrencyCode(),
                'quantity'               => $item->getQtyOrdered() ? round($item->getQtyOrdered()) : 1,
                'image_url'              => $this->_helper->getImageUrl($item),
                'vat_percentage'         => $this->_helper->getAmountInCents($item->getTaxPercent()),
                'merchant_order_line_id' => (string)$item->getId()
            );
        }

        if ($order->getShippingAmount() > 0) {
            $orderLines[] = $this->_getShippingOrderLine($order, count($orderLines));
        }

        return $orderLines;
    }

    /**
     * Retrieves shipping order line
     *
     * @param Mage_Sales_Model_Order $order
     * @param int                    $noOrderLines
     *
     * @return array
     */
    protected function _getShippingOrderLine(Mage_Sales_Model_Order $order, $noOrderLines)
    {
        return array(
            'type'                   => 'shipping_fee',
            'name'                   => (string)$order->getShippingDescription(),
            'amount'                 => $this->_helper->getShippingTotalAmount($order),
            'currency'               => $order->getOrderCurrencyCode(),
            'vat_percentage'         => $this->_helper->getShippingTax($order),
            'quantity'               => 1,
            'merchant_order_line_id' => (string)($noOrderLines + 1),
        );
    }

    /**
     * Retrieves customer data from order without NULL values
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    protected function _getCustomerData(Mage_Sales_Model_Order $order)
    {
        $customerAddress = $this->_getCustomerDataAddress($order);
        return array_filter(
            array(
            'address_type'         => 'billing',
            'merchant_customer_id' => $order->getCustomerId(),
            'email_address'        => $order->getCustomerEmail(),
            'first_name'           => $order->getCustomerFirstname(),
            'last_name'            => $order->getCustomerLastname(),
            'address'              => $customerAddress['address'],
            'postal_code'          => $customerAddress['postal_code'],
            'housenumber'          => $customerAddress['housenumber'],
            'country'              => $customerAddress['country'],
            'phone_numbers'        => $customerAddress['phone_numbers'],
            'user_agent'           => $this->_coreHttp->getHttpUserAgent(),
            'referrer'             => $this->_coreHttp->getHttpReferer(),
            'ip_address'           => $this->_coreHttp->getRemoteAddr(),
            'forwarded_ip'         => Mage::app()->getRequest()->getServer('HTTP_X_FORWARDED_FOR'),
            'gender'               => $this->_getCustomerGender($order),
            'birthdate'            => $this->_getCustomerBirthdate($order),
            'locale'               => Mage::app()->getLocale()->getLocaleCode()
            )
        );
    }

    /**
     * Retrieves address data from order
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    protected function _getCustomerDataAddress(Mage_Sales_Model_Order $order)
    {
        $billingAddress = $order->getBillingAddress();
        $street = implode(' ', $billingAddress->getStreet());

        list($address, $houseNumber) = $this->_helper->parseAddress($street);

        $postalCode = $billingAddress->getPostcode();
        if (strlen($postalCode) == 6) {
            $postalCode = wordwrap($postalCode, 4, ' ', true);
        }

        $customerData = array(
            'address'       => (string)trim($billingAddress->getCity()) . ' ' . trim($address),
            'postal_code'   => (string)$postalCode,
            'housenumber'   => (string)$houseNumber,
            'country'       => (string)$billingAddress->getCountryId(),
            'phone_numbers' => array($billingAddress->getTelephone())
        );

        if ($this->_code == EMS_Payment_Model_Klarnapaylater::CODE
            || $this->_code == EMS_Payment_Model_Afterpay::CODE
        ) {
            $customerData['address'] = trim($street)
                . ' ' . $postalCode
                . ' ' . trim($billingAddress->getCity());
        }

        if ($this->_code == EMS_Payment_Model_Klarnapaynow::CODE) {
            $customerData['address'] = trim($billingAddress->getCity()) . ' ' . trim($address);
        }

        return $customerData;
    }

    /**
     * Get customer gender from order or request
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return string|null
     */
    protected function _getCustomerGender(Mage_Sales_Model_Order $order)
    {
        if ($order->getCustomerGender()) {
            $gender = $order->getCustomerGender();
        } elseif (defined('static::REQUEST_PARAM_KEY_GENDER')) {
            $gender = urldecode(Mage::app()->getRequest()->getParam(static::REQUEST_PARAM_KEY_GENDER));
        }

        return isset($gender) ? (($gender == '1') ? 'male' : (($gender == '2') ? 'female' : null)) : null;
    }

    /**
     * Get date of birth from order or request
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return string|null
     */
    protected function _getCustomerBirthdate(Mage_Sales_Model_Order $order)
    {
        if ($order->getCustomerDob()) {
            return Mage::getSingleton('core/date')->date('Y-m-d', strtotime($order->getCustomerDob()));
        } elseif (defined('static::REQUEST_PARAM_KEY_DOB')) {
            if ($dob = urldecode(Mage::app()->getRequest()->getParam(static::REQUEST_PARAM_KEY_DOB))) {
                return Mage::getSingleton('core/date')->date('Y-m-d', strtotime($dob));
            }
        }

        return null;
    }

    /**
     * Common instructions for all payment methods after creating EMS order
     *
     * @throws Exception
     */
    protected function _processRequest()
    {
        $this->_helper->log('createOrder', $this->_transaction);
        $transactionId = !empty($this->_transaction['id']) ? $this->_transaction['id'] : null;

        $this->_updateMailingAddress();

        if ($error = $this->_helper->getError($this->_transaction)) {
            $this->_errorMessage = $this->_helper->__($error);
            Mage::throwException($this->getErrorMessage());
        }

        if (!$transactionId) {
            $this->_errorMessage = $this->_helper->__(self::ERROR_NO_TRANSACTION);
            Mage::throwException($this->getErrorMessage());
        }

        /** @var $payment Mage_Sales_Model_Order_Payment */
        $payment = $this->_mageOrder->getPayment();
        $storeId = $this->_mageOrder->getStoreId();

        if (!$payment->getId()) {
            $payment = Mage::getModel('sales/order_payment')->setId(null);
        }

        $payment->setIsTransactionClosed(false)
            ->setEmsPaymentOrderId($transactionId)
            ->setEmsPaymentIdealIssuerId($this->getIssuerId())
            ->setTransactionId($transactionId)
            ->setParentTransactionId($transactionId);

        if ($this->_code == EMS_Payment_Model_Klarnapaylater::CODE ||
            $this->_code == EMS_Payment_Model_Afterpay::CODE
        ) {
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        } else {
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        }

        $this->_mageOrder->setPayment($payment);
        $this->_mageOrder->setEmsPaymentOrderId($transactionId);
        $this->_mageOrder->setEmsPaymentIdealIssuerId($this->getIssuerId());
        $this->_mageOrder->save();

        $pendingMessage = $this->_helper->__(self::PAYMENT_FLAG_PENDEMS);

        if ($emsOrderId = $this->_mageOrder->getEmsPaymentOrderId()) {
            $pendingMessage .= '. EMS Order ID: ' . $emsOrderId;
        }

        $status = Mage::getStoreConfig('payment/ems_payment/status_pending', $storeId);
        $this->_mageOrder->addStatusToHistory($status, $pendingMessage, false);

        if (Mage::getStoreConfig('payment/' . $this->_code . '/send_order_mail', $storeId)) {
            if (!$this->_mageOrder->getEmailSent()) {
                $this->_mageOrder->setEmailSent(true)->sendNewOrderEmail()->save();
            }
        }

        $this->_mageOrder->save();

        if (!$this->_shouldRedirect) {
            return;
        }

        if (!empty($this->_transaction['transactions'][0]['payment_url'])) {
            $this->setPaymentUrl($this->_transaction['transactions'][0]['payment_url']);
        } else {
            $this->_errorMessage = $this->_helper->__(self::ERROR_NO_REDIRECT);
            Mage::throwException($this->getErrorMessage());
        }
    }

    /**
     * Update Order Information
     *
     * @throws Mage_Core_Exception
     */
    protected function _updateMailingAddress()
    {
        if ($this->_mageOrder->getPayment()->getMethodInstance() instanceof EMS_Payment_Model_Banktransfer
            && $paymentBlock = $this->_mageOrder->getPayment()->getMethodInstance()->getMailingAddress()
        ) {
            $details = array();
            $storeId = $this->_mageOrder->getStoreId();
            $amountStr = $this->_mageOrder->getOrderCurrency()->formatTxt($this->_mageOrder->getGrandTotal());
            $reference = $this->_transaction['transactions'][0]['payment_method_details']['reference'];

            $accountDetails = Mage::getStoreConfig("payment/ems_payment_banktransfer/account_details", $storeId);
            $paymentBlock = str_replace('%AMOUNT%', $amountStr, $paymentBlock);
            $paymentBlock = str_replace('%REFERENCE%', $reference, $paymentBlock);
            $paymentBlock = str_replace('%IBAN%', $accountDetails['iban'], $paymentBlock);
            $paymentBlock = str_replace('%BIC%', $accountDetails['bic'], $paymentBlock);
            $paymentBlock = str_replace('%HOLDER%', $accountDetails['holder'], $paymentBlock);
            $paymentBlock = str_replace('%CITY%', $accountDetails['city'], $paymentBlock);
            $paymentBlock = str_replace('\n', PHP_EOL, $paymentBlock);

            $details['mailing_address'] = $paymentBlock;
            $this->_mageOrder->getPayment()
                ->getMethodInstance()
                ->getInfoInstance()
                ->setAdditionalData(serialize($details));
        }
    }

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if ($this->_emsLib === null) {
            return false;
        }

        if (!Mage::getStoreConfig('payment/ems_payment/enabled', $quote ? $quote->getStoreId() : null)) {
            return false;
        }

        if (Mage::getStoreConfig('payment/ems_payment/apikey', $quote ? $quote->getStoreId() : null)) {
            return parent::isAvailable($quote);
        }

        return false;
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->_getCheckout()->getQuote();
    }

    /**
     * Services is only active if 'EURO' is currency
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if ($currencyCode !== 'EUR') {
            return false;
        }

        return parent::canUseForCurrency($currencyCode);
    }

    /**
     * Retrieves a redirect url by click 'Place Order' to selected method
     *
     * @param array $query
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl($query = array())
    {
        return Mage::getUrl(
            'ems_payment/' . $this->_controller . '/payment',
            array(
                '_secure' => true,
                '_query'  => $query
            )
        );
    }

    /**
     * Refunds payment online
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $transactionId = $order->getEmsPaymentOrderId();

        try {
            $emsOrder = $this->_emsLib->refundOrder(
                $transactionId,
                array(
                    'amount'   => $this->_helper->getAmountInCents((float)$amount),
                    'currency' => $order->getOrderCurrencyCode()
                )
            );
        } catch (\Exception $e) {
            $exceptionMsg = $this->_helper->__('Error: not possible to create an online refund: %s', $e->getMessage());
            $this->_helper->log('Refund', $exceptionMsg, 3);
            Mage::throwException($exceptionMsg);
        }

        if (in_array($emsOrder['status'], array('error', 'cancelled', 'expired'))) {
            if (isset(current($emsOrder['transactions'])['reason'])) {
                $exceptionMsg = $this->_helper->__(
                    'Error: not possible to create an online refund: %s',
                    current($emsOrder['transactions'])['reason']
                );
            } else {
                $exceptionMsg = $this->_helper->__(
                    'Error: not possible to create an online refund: Refund order is not completed'
                );
            }
            $this->_helper->log('Refund', $exceptionMsg, 3);
            Mage::throwException($exceptionMsg);
        }

        return $this;
    }
}
