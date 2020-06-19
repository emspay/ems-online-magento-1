<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_ReturnController extends EMS_Payment_Controller_Action
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
        $this->_emsLib = \Ginger\Ginger::createClient(
            EMS_Payment_Model_Abstract::ENDPOINT_EMS,
            $this->_apiKey
        );
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
            $orderId = $request->getParam('order_id', null);

            if ($orderId === null) {
                $msg = $this->__('Invalid return, please try again');
                Mage::getSingleton('core/session')->addError($msg);
                return $this->_redirect('checkout/cart', array('_secure' => true));
            }

            $transaction = $this->_emsLib->getOrder($orderId);
            $transactionStatus = !empty($transaction['status']) ? $transaction['status'] : '';

            switch ($transactionStatus) {
                case 'completed':
                    $this->_redirect('checkout/onepage/success', array('_secure' => true));
                    break;
                case 'cancelled':
                case 'expired':
                case 'error':
                    $msg = $this->__('Payment %s, please try again.', $transactionStatus);
                    Mage::getSingleton('core/session')->addError($msg);
                    $this->_restoreCart();
                    $this->_redirect('checkout/cart', array('_secure' => true));
                    break;
                default:
                    $this->_restoreCart();
                    $msg = $this->__('Unkown payment status, please try again');
                    Mage::getSingleton('core/session')->addError($msg);
                    $this->_redirect('checkout/cart', array('_secure' => true));
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