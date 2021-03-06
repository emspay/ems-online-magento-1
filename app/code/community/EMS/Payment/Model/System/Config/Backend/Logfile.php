<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_Model_System_Config_Backend_Logfile extends Mage_Core_Model_Config_Data
{

    /**
     * Config path for log file.
     */
    const LOG_FILE_NAME = 'payment/ems_payment/log_file';

    /**
     * Processing object before save data
     *
     * @return EMS_Payment_Model_System_Config_Backend_Logfile
     * @throws Mage_Core_Exception
     */
    protected function _beforeSave()
    {
        $value = $this->getValue();
        $configPath = $this->getPath();
        $value = basename($value);

        if ($configPath == self::LOG_FILE_NAME) {
            if (!Mage::helper('log')->isLogFileExtensionValid($value)) {
                throw Mage::exception(
                    'Mage_Core', Mage::helper('adminhtml')->__(
                        'Invalid file extension used for log file. Allowed file extensions: log, txt'
                    )
                );
            }
        }

        $this->setValue($value);
        return $this;
    }
}
