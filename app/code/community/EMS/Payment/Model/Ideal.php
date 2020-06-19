<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * iDEAL payment method model
 *
 * @method string getIssuerId()
 * @method EMS_Payment_Model_Ideal setIssuerId(string $issuerId)
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Ideal extends EMS_Payment_Model_Abstract
{

    /** Payment Method Code */
    const CODE = 'ems_payment_ideal';

    /** Platform Method Code */
    const PLATFORM_CODE = 'ideal';

    protected $_code = self::CODE;
    protected $_methodCode = self::PLATFORM_CODE;
    protected $_formBlockType = 'ems_payment/payment_ideal_form';
    protected $_infoBlockType = 'ems_payment/payment_ideal_info';
    protected $_paymentMethod = 'iDEAL';
    protected $_controller = 'ideal';

    /**
     * On click payment button, this function is called to assign data
     *
     * @param $data
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        if (Mage::registry('issuer_id')) {
            Mage::unregister('issuer_id');
        }

        Mage::register('issuer_id', Mage::app()->getRequest()->getParam('issuer_id'));

        return $this;
    }

    /**
     * Retrieves redirect url for client by click 'Place Order' to selected iDEAL method
     *
     * @param array $array
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl($array = array())
    {
        return parent::getOrderPlaceRedirectUrl(
            array(
                'issuer_id' => Mage::registry('issuer_id')
            )
        );
    }

    /**
     * Fetch the list of issuers
     *
     * @return null|array
     */
    public function getIssuers()
    {
        return $this->_emsLib->getIdealIssuers();
    }

    /**
     * {@inheritDoc}
     */
    protected function _createOrderBefore()
    {
        parent::_createOrderBefore();
        $issuerId = Mage::app()->getRequest()->getParam('issuer_id');

        if (!$this->setIssuerId($issuerId)->hasIssuerId()) {
            $this->_errorMessage = $this->_helper->__('Error in the given payment data');
            Mage::throwException(
                $this->getErrorMessage()
            );
        }
    }

    /**
     * Retrieves true if model has Issuer ID
     *
     * @return bool
     */
    protected function hasIssuerId()
    {
        return (bool)$this->getIssuerId();
    }
}
