<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_KlarnapaynowController extends EMS_Payment_Controller_Action
{

    /**
     * @var EMS_Payment_Model_Klarnapaynow
     */
    protected $_method;

    /**
     * Initialization controller
     */
    public function _construct()
    {
        $this->_method = Mage::getModel('ems_payment/klarnapaynow');
        parent::_construct();
    }

    /**
     * Create the order and set the redirect url to payment screen
     */
    public function paymentAction()
    {
        try {
            $this->_method->createOrder();
            $this->_redirectUrl($this->_method->getPaymentUrl());
        } catch (Mage_Core_Exception $exception) {
            $this->_restoreCart();
            $this->_helper->log('paymentAction', $exception->getMessage(), 3);
            $this->_redirect('checkout/cart', array('_secure' => true));
            Mage::getSingleton('core/session')->addError(
                $this->_method->getErrorMessage()
            );
        } catch (Exception $exception) {
            $this->_restoreCart();
            $this->_helper->log('paymentAction', $exception->getMessage(), 3);
            $this->_redirect('checkout/cart', array('_secure' => true));
            Mage::getSingleton('core/session')->addError(
                $this->__(EMS_Payment_Model_Abstract::ERROR_MESSAGE_START)
            );
        }
    }
}
