<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_BanktransferController extends EMS_Payment_Controller_Action
{

    /**
     * @var EMS_Payment_Model_Banktransfer
     */
    protected $_method;

    /**
     * Initialization controller
     */
    public function _construct()
    {
        $this->_method = Mage::getModel('ems_payment/banktransfer');
        parent::_construct();
    }

    /**
     * Create the order and set the redirect url to checkout success
     *
     * @return void
     */
    public function paymentAction()
    {
        try {
            $this->_method->createOrder();
            $this->_redirect('checkout/onepage/success/reference/banktransfer', array('_secure' => true));
        } catch (Mage_Core_Exception $exception) {
            $this->_restoreCart();
            $this->_helper->log('paymentAction', $exception->getMessage(), 3);
            $this->_redirect('checkout/cart', array('_secure' => true));
            Mage::getSingleton('core/session')->addError(
                $this->_method->getErrorMessage()
            );
        } catch (\Exception $exception) {
            $this->_restoreCart();
            $this->_helper->log('paymentAction', $exception->getMessage(), 3);
            $this->_redirect('checkout/cart', array('_secure' => true));
            Mage::getSingleton('core/session')->addError(
                $this->__(EMS_Payment_Model_Abstract::ERROR_MESSAGE_START)
            );
        }
    }
}
