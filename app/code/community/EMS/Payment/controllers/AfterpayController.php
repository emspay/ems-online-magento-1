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

class EMS_Payment_AfterpayController extends EMS_Payment_Controller_Action
{
    /**#@+
     * Constants
     */
    const REQUEST_PARAM_KEY_DOB = 'afterpay_dob';
    const REQUEST_PARAM_KEY_GENDER = 'afterpay_gender';
    /**#@-*/

    /** @var EMS_Payment_Model_Afterpay */
    protected $_method;

    /**
     * Initialization controller
     */
    public function _construct()
    {
        $this->_method = Mage::getModel('ems_payment/afterpay');
        parent::_construct();
    }

    /**
     * Create the order and sets the redirect url
     *
     * @return void
     */
    public function paymentAction()
    {
        try {
            $this->_method->createOrder();
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } catch (Mage_Core_Exception $exception) {
            $this->_restoreCart();
            $this->_helper->log('paymentAction', $exception, 3);
            $this->_redirect('checkout/cart', array('_secure' => true));
            Mage::getSingleton('core/session')->addError(
                $this->_method->getErrorMessage()
            );
        } catch (Exception $exception) {
            $this->_restoreCart();
            $this->_helper->log('paymentAction', $exception, 3);
            $this->_redirect('checkout/cart', array('_secure' => true));
            Mage::getSingleton('core/session')->addError(
                $this->__(EMS_Payment_Model_Abstract::ERROR_MESSAGE_START)
            );
        }
    }
}
