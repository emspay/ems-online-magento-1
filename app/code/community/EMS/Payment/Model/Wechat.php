<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Wechat payment method model
 *
 * @category    EMS
 * @package     EMS_Payment
 */
class EMS_Payment_Model_Wechat extends EMS_Payment_Model_Abstract
{

    /** Payment Method Code */
    const CODE = 'ems_payment_wechat';

    /** Platform Method Code */
    const PLATFORM_CODE = 'wechat';

    protected $_code = self::CODE;
    protected $_methodCode = self::PLATFORM_CODE;
    protected $_paymentMethod = 'WeChat';
    protected $_controller = 'wechat';
    protected $_includeCustomerData = true;

}
