<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * American Express payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Amex extends EMS_Payment_Model_Abstract
{

    /** Payment Method Code */
    const CODE = 'ems_payment_amex';

    /** Platform Method Code */
    const PLATFORM_CODE = 'amex';

    protected $_code = self::CODE;
    protected $_methodCode = self::PLATFORM_CODE;
    protected $_paymentMethod = 'American Express';
    protected $_controller = 'amex';
    protected $_includeCustomerData = true;

}
