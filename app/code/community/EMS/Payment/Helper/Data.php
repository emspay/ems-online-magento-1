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

class EMS_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Config path for enable log configuration.
     */
    const XML_PATH_LOG_ENABLED = 'payment/ems_payment/log_enabled';

    /**
     * Allowed extensions that can be used to create a log file
     */
    protected $_allowedLogFileExtensions = array('log', 'txt');

    /**
     * Parse address for split street and house number
     *
     * @param  string $streetAddress
     *
     * @return array
     */
    public function parseAddress($streetAddress)
    {
        $address = $streetAddress;
        $houseNumber = '';

        $offset = strlen($streetAddress);

        while (($offset = $this->_rstrpos($streetAddress, ' ', $offset)) !== false) {
            if ($offset < strlen($streetAddress) - 1 && is_numeric($streetAddress[$offset + 1])) {
                $address = trim(substr($streetAddress, 0, $offset));
                $houseNumber = trim(substr($streetAddress, $offset + 1));
                break;
            }
        }

        if (empty($houseNumber) && strlen($streetAddress) > 0 && is_numeric($streetAddress[0])) {
            $pos = strpos($streetAddress, ' ');

            if ($pos !== false) {
                $houseNumber = trim(substr($streetAddress, 0, $pos), ", \t\n\r\0\x0B");
                $address = trim(substr($streetAddress, $pos + 1));
            }
        }

        return array($address, $houseNumber);
    }

    /**
     * @param string   $haystack
     * @param string   $needle
     * @param null|int $offset
     *
     * @return int
     */
    protected function _rstrpos($haystack, $needle, $offset = null)
    {
        $size = strlen($haystack);

        if (null === $offset) {
            $offset = $size;
        }

        $pos = strpos(strrev($haystack), strrev($needle), $size - $offset);

        if ($pos === false) {
            return false;
        }

        return $size - $pos - strlen($needle);
    }

    /**
     * Retrieves amount in cents
     *
     * @param string|float $amount
     *
     * @return int
     */
    public function getAmountInCents($amount)
    {
        return (int)round($amount * 100);
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     *
     * @return float
     */
    public function getItemTotalAmount(Mage_Sales_Model_Order_Item $item)
    {
        return $this->getAmountInCents(
            ($item->getRowTotal()
                - $item->getDiscountAmount()
                + $item->getTaxAmount()
                + $item->getHiddenTaxAmount()) / $item->getQtyOrdered());
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return int
     */
    public function getShippingTotalAmount(Mage_Sales_Model_Order $order)
    {
        return  $this->getAmountInCents(
            $order->getShippingAmount()
            + $order->getShippingTaxAmount()
            + $order->getShippingHiddenTaxAmount()
        );
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     *
     * @return string
     */
    public function getImageUrl($item)
    {
        /** @var Mage_Catalog_Model_Product_Media_Config $mediaConfig */
        $mediaConfig = Mage::getModel('catalog/product_media_config');
        return $mediaConfig->getMediaUrl($item->getProduct()->getThumbnail());
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return float
     */
    public function getShippingTax(Mage_Sales_Model_Order $order)
    {
        return $this->getAmountInCents(100 * $order->getShippingTaxAmount() / $order->getShippingInclTax());
    }

    /**
     * Retrieves plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->__('Magento v%s', Mage::getConfig()->getModuleConfig('EMS_Payment')->version);
    }

    /**
     * Checking if file extensions is allowed for logging. If passed then return true.
     *
     * @param $file
     *
     * @return bool
     */
    public function isLogFileExtensionValid($file)
    {
        $result = false;
        $validatedFileExtension = pathinfo($file, PATHINFO_EXTENSION);
        if ($validatedFileExtension && in_array($validatedFileExtension, $this->_allowedLogFileExtensions)) {
            $result = true;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getAutoloadPath()
    {
        return Mage::getBaseDir('lib') . DS . 'Ems' . DS . 'Services' . DS . 'ing-php' . DS . 'vendor' . DS . 'autoload.php';
    }

    /**
     * Logs data to custom log file if it needed
     *
     * @param     $type
     * @param     $message
     * @param int $level
     */
    public function log($type, $message, $level = null)
    {
        if (!Mage::getStoreConfig(self::XML_PATH_LOG_ENABLED)) {
            return;
        }

        if (is_array($message)) {
            $message = json_encode($message);
        }

        $logMessage = implode(': ', array($type, $message));
        $fileName = Mage::getStoreConfig(EMS_Payment_Model_System_Config_Backend_Logfile::LOG_FILE_NAME);

        Mage::log($logMessage, $level, $fileName);
    }
}
