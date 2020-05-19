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

class EMS_Payment_HomepayController extends EMS_Payment_Controller_Action
{
    /**#@+
     * Constants
     */
    const XML_PATH_PAYMENT_GROUP = 'ems_payment_homepay';
    /**#@-*/

    /** @var EMS_Payment_Model_Homepay */
    protected $_method;

    /**
     * Initialization controller
     */
    public function _construct()
    {
        $this->_method = Mage::getModel('ems_payment/homepay');
        parent::_construct();
    }

    /**
     * Creates the order and sets the redirect urls
     */
    public function paymentAction()
    {
        try {
            $this->_method->createOrder();
            $this->_redirectUrl($this->_method->getPaymentUrl());
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
