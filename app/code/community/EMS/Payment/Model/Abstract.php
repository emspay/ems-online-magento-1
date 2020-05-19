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

    // Payment flags
    const PAYMENT_FLAG_PENDEMS = "Payment is pending";
    const PAYMENT_FLAG_COMPLETED = "Payment is completed";
    const PAYMENT_FLAG_CANCELLED = "Payment is cancelled";
    const PAYMENT_FLAG_ERROR = "Payment failed with an error";
    const PAYMENT_FLAG_FRAUD = "Amounts don't match. Possible fraud";

    // Error messages
    const ERROR_MESSAGE_START = 'Could not start transaction. Contact the owner.';

    /** @var EMS_Payment_Helper_Data */
    protected $_helper;

    /** @var Mage_Core_Helper_String */
    protected $_string;

    /** @var Mage_Core_Helper_Http */
    protected $_coreHttp;

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapture = true;

    protected $_orderStatus = null;
    protected $_consumerInfo = array();
    protected $_errorMessage = '';
    protected $_errorCode = 0;
    protected $_apiKey = null;
    protected $_controller;
    protected $_webhookUrl;

    /** @var GingerPayments\Payment\Client */
    protected $_emsLib;

    /** @var Mage_Sales_Model_Order */
    protected $_mageOrder;

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

        require_once($this->_helper->getAutoloadPath());

        if (null === $this->_apiKey) {
            $this->_apiKey = Mage::getStoreConfig("payment/ems_payment/apikey");
        }

        try {
            if ($this->_apiKey !== '') {
                $this->_emsLib = \GingerPayments\Payment\Ginger::createClient(
                    $this->_apiKey,
                    Mage::getStoreConfig("payment/ems_payment/product")
                );

                if (Mage::getStoreConfig("payment/ems_payment/bundle_cacert")) {
                    $this->_emsLib->useBundledCA();
                }
            }
        } catch (\Exception $exception) {
            $this->_emsLib = null;
            $this->_helper->log('Load API', $exception->getMessage(), 3);
        }

        parent::_construct();
    }

    /**
     * Creates EMS order and saves data to Magento order
     *
     * @return void
     * @throws Exception
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    abstract public function createOrder();

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
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
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
                '_query'  => $query,
            )
        );
    }

    /**
     * Retrieves order details from EMS order
     *
     * @param $emsOrderId
     *
     * @return array
     */
    public function getOrderDetails($emsOrderId)
    {
        return $this->_emsLib->getOrder($emsOrderId)->toArray();
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
        try {
            $paymentModel = parent::refund($payment, $amount);
            $this->_emsLib->createOrderRefund(
                $payment->getOrder()->getEmsPaymentOrderId(),
                $this->_helper->getAmountInCents($payment->getCreditmemo()->getGrandTotal())
            );

            return $paymentModel;
        } catch (GingerPayments\Payment\Client\OrderNotFoundException $exception) {
            throw new Mage_Core_Exception($exception->getMessage());
        } catch (GingerPayments\Payment\Client\ClientException $exception) {
            throw new Mage_Core_Exception($exception->getMessage());
        }
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
        $this->_mageOrder = Mage::getModel('sales/order')
            ->loadByIncrementId($this->_getCheckout()->getLastRealOrderId());

        if (!$this->_mageOrder->getId()) {
            $this->_errorMessage = $this->_helper->__('Processing order not found');
            $this->_helper->log('orderBefore', $this->getErrorMessage(), 3);
            Mage::throwException($this->getErrorMessage());
        }

        $description = $this->_helper->__(
            'Your order %s at %s', $this->_mageOrder->getIncrementId(),
            Mage::app()->getStore()->getName()
        );

        $this->_webhookUrl = Mage::getUrl('ems_payment/webhook');

        if ($this->_code == 'ems_payment_klarna') {
            $klarnaTestKey = Mage::getStoreConfig("payment/ems_payment/klarna_test_apikey");
            if (!empty($klarnaTestKey)) {
                $this->_webhookUrl .= '?test=klarna';
            }
        }

        if ($this->_code == 'ems_payment_afterpay') {
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
     * @return self|Varien_Object
     */
    public function setDescription($description)
    {
        $description = $this->_string->truncate($description, 29, '');

        return $this->setData('description', $description);
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
     * Common instructions for all payment methods after creating EMS order
     *
     * @throws Exception
     */
    protected function _createOrderAfter()
    {
        /** @var $payment Mage_Sales_Model_Order_Payment */
        $payment = $this->_mageOrder->getPayment();
        $storeId = $this->_mageOrder->getStoreId();

        if (!$payment->getId()) {
            $payment = Mage::getModel('sales/order_payment')->setId(null);
        }

        $payment->setIsTransactionClosed(false)
            ->setEmsPaymentOrderId($this->getOrderId())
            ->setEmsPaymentIdealIssuerId($this->getIssuerId())
            ->setTransactionId($this->getOrderId())
            ->setParentTransactionId($this->getOrderId());

        if ($this->_code == 'ems_payment_klarna' || $this->_code == 'ems_payment_afterpay') {
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        } else {
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        }

        $this->_mageOrder->setPayment($payment);
        $this->_mageOrder->setEmsPaymentOrderId($this->getOrderId());
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

        return GingerPayments\Payment\Common\ArrayFunctions::withoutNullValues(
            array(
                'merchant_customer_id' => $order->getCustomerId(),
                'email_address'        => $order->getCustomerEmail(),
                'first_name'           => $order->getCustomerFirstname(),
                'last_name'            => $order->getCustomerLastname(),
                'address_type'         => 'billing',
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
            'address'       => trim($billingAddress->getCity()) . ' ' . trim($address),
            'postal_code'   => $postalCode,
            'housenumber'   => $houseNumber,
            'country'       => $billingAddress->getCountryId(),
            'phone_numbers' => array($billingAddress->getTelephone())
        );

        if ($this->_code == 'ems_payment_klarna' || 'ems_payment_afterpay') {
            $customerData['address'] = trim($street)
                . ' ' . $postalCode
                . ' ' . trim($billingAddress->getCity());
        }

        if ($this->_code == 'ems_payment_sofort') {
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
     * Retrieves order lines
     *
     * @param $order
     *
     * @return array
     */
    protected function _getOrderLines(Mage_Sales_Model_Order $order)
    {
        $orderLines = array();

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $orderLines[] = array(
                'url'                    => $item->getProduct()->getProductUrl(),
                'name'                   => $item->getName(),
                'type'                   => \GingerPayments\Payment\Order\OrderLine\Type::PHYSICAL,
                'amount'                 => $this->_helper->getItemTotalAmount($item),
                'currency'               => \GingerPayments\Payment\Currency::EUR,
                'quantity'               => (int)$item->getQtyOrdered() ? $item->getQtyOrdered() : 1,
                'image_url'              => $this->_helper->getImageUrl($item),
                'vat_percentage'         => $this->_helper->getAmountInCents($item->getTaxPercent()),
                'merchant_order_line_id' => $item->getId()
            );
        }

        if ($order->getShippingAmount() > 0) {
            $orderLines[] = $this->_getShippingOrderLine($order);
        }

        return $orderLines;
    }

    /**
     * Retrieves shipping order line
     *
     * @param $order
     *
     * @return array
     */
    protected function _getShippingOrderLine(Mage_Sales_Model_Order $order)
    {
        return array(
            'name'                   => $order->getShippingDescription(),
            'type'                   => \GingerPayments\Payment\Order\OrderLine\Type::SHIPPING_FEE,
            'amount'                 => $this->_helper->getShippingTotalAmount($order),
            'currency'               => \GingerPayments\Payment\Currency::EUR,
            'vat_percentage'         => $this->_helper->getShippingTax($order),
            'quantity'               => 1,
            'merchant_order_line_id' => 'shipping'
        );
    }
}
