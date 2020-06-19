<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Apple Pay payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Applepay extends EMS_Payment_Model_Abstract
{

    /** Payment Method Code */
    const CODE = 'ems_payment_applepay';

    /** Platform Method Code */
    const PLATFORM_CODE = 'apple-pay';

    protected $_code = self::CODE;
    protected $_methodCode = self::PLATFORM_CODE;
    protected $_paymentMethod = 'Apple Pay';
    protected $_controller = 'applepay';

}
