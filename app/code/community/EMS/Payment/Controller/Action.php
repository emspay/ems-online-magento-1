<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

abstract class EMS_Payment_Controller_Action extends Mage_Core_Controller_Front_Action
{

    /**
     * @var EMS_Payment_Helper_Data
     */
    protected $_helper;

    /**
     * @var Mage_Core_Helper_Http
     */
    protected $_coreHttp;

    /**
     * Initialization basic properties
     */
    public function _construct()
    {
        $this->_helper = Mage::helper('ems_payment');
        $this->_coreHttp = Mage::helper('core/http');

        parent::_construct();
    }

    /**
     * @return void
     */
    protected function _restoreCart()
    {
        $session = $this->_getCheckout();
        $orderId = $session->getLastRealOrderId();

        if (!empty($orderId)) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if ($order->canCancel()) {
                try {
                    $order->cancel();
                    $order->setState(
                        Mage_Sales_Model_Order::STATE_CANCELED,
                        Mage_Sales_Model_Order::STATE_CANCELED,
                        $this->_helper->__(EMS_Payment_Model_Ideal::PAYMENT_FLAG_CANCELLED),
                        true
                    )->save();
                } catch (\Exception $exception) {
                    $this->_helper->log('_restoreCart', $exception->getMessage(), 3);
                }
            }

            $quoteId = $order->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quoteId)->setIsActive(true)->save();
            $session->replaceQuote($quote);
        }
    }

    /**
     * Gets the current checkout session with order information
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Format price with currency sign
     * Common method for Bancontact, Creditcart, Homepay, Ideal, Payconiq, Paypal, Sofort controllers
     *
     * @param Mage_Sales_Model_Order $order
     * @param float                  $amount
     *
     * @return string
     */
    protected function _formatPrice(Mage_Sales_Model_Order $order, $amount)
    {
        return $order->getOrderCurrency()->formatTxt($amount);
    }
}