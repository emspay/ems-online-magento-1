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

class EMS_Payment_ReturnController extends EMS_Payment_Controller_Action
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

        if ($this->_apiKey !== '') {
            $this->_emsLib = \GingerPayments\Payment\Ginger::createClient(
                $this->_apiKey,
                Mage::getStoreConfig("payment/ems_payment/product")
            );

            if (Mage::getStoreConfig("payment/ems_payment/bundle_cacert")) {
                $this->_emsLib->useBundledCA();
            }
        }
    }

    /**
     * Customer returning with an order_id
     * Depending on the order state redirected to the corresponding page
     */
    public function indexAction()
    {
        try {
            /** @var Mage_Core_Controller_Request_Http $request */
            $request = Mage::app()->getRequest();
            $orderId = $request->getParam('order_id');

            if (empty($orderId)) {
                $msg = $this->__('Invalid return, please try again');
                Mage::getSingleton('core/session')->addError($msg);
                $this->_redirect('checkout/cart', array('_secure' => true));
                return;
            }

            $emsOrder = $this->_emsLib->getOrder($orderId);
            $orderStatus = $emsOrder->getStatus();

            switch ($orderStatus) {
                case 'completed':
                    $this->_redirect('checkout/onepage/success', array('_secure' => true));
                    return;
                    break;
                case 'cancelled':
                case 'expired':
                case 'error':
                    $msg = $this->__('Payment %s, please try again.', $orderStatus);
                    Mage::getSingleton('core/session')->addError($msg);
                    $this->_restoreCart();
                    $this->_redirect('checkout/cart', array('_secure' => true));
                    return;
                    break;
                default:
                    $this->_restoreCart();
                    $msg = $this->__('Unkown payment status, please try again');
                    Mage::getSingleton('core/session')->addError($msg);
                    $this->_redirect('checkout/cart', array('_secure' => true));
                    return;
                    break;
            }
        } catch (Exception $exception) {
            $this->_restoreCart();
            $this->_helper->log('returnAction', $exception);
            Mage::getSingleton('core/session')->addError($exception->getMessage());
            $this->_redirect('checkout/cart', array('_secure' => true));
        }
    }
}