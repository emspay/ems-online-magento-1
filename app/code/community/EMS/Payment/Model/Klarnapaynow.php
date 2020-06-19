<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Klarna Pay Now payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Klarnapaynow extends EMS_Payment_Model_Abstract
{

    /** Payment Method Code */
    const CODE = 'ems_payment_klarnapaynow';

    /** Platform Method Code */
    const PLATFORM_CODE = 'klarna-pay-now';

    protected $_code = self::CODE;
    protected $_methodCode = self::PLATFORM_CODE;
    protected $_paymentMethod = 'Klarna Direct';
    protected $_controller = 'klarnapaynow';
    protected $_includeCustomerData = true;

}
