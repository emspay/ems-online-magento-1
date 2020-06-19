<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Tikkie payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Tikkie extends EMS_Payment_Model_Abstract
{

    /** Payment Method Code */
    const CODE = 'ems_payment_tikkie';

    /** Platform Method Code */
    const PLATFORM_CODE = 'tikkie-payment-request';

    protected $_code = self::CODE;
    protected $_methodCode = self::PLATFORM_CODE;
    protected $_paymentMethod = 'Tikkie';
    protected $_controller = 'tikkie';
    protected $_includeCustomerData = true;

}
