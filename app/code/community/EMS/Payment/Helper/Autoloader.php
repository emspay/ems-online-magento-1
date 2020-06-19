<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * PSR-4 Autoloader, based on
 * https://www.integer-net.com/magento-1-magento-2-using-advanced-autoloading/
 * https://github.com/integer-net/solr-magento1/blob/master/src/app/code/community/IntegerNet/Solr/Helper/Autoloader.php
 */
class EMS_Payment_Helper_Autoloader
{

    /**
     * @var bool
     */
    static $registered = false;
    /**
     * @var bool
     */
    static $configLoaded = false;
    /**
     * @var array
     */
    protected $prefixes = array();

    /**
     *
     */
    public function createAndRegister()
    {
        if ($this->_getStoreConfig('payment/ems_payment/register_autoloader')) {
            $libBaseDir = Mage::getBaseDir() . DS . 'lib' . DS . 'Ems';
            $this->loadComposerAutoLoad($libBaseDir);
        }
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    protected function _getStoreConfig($path)
    {
        if (!self::$configLoaded && Mage::app()->getUpdateMode()) {
            Mage::getConfig()->loadDb();
            self::$configLoaded = true;
        }

        return Mage::getStoreConfig($path);
    }

    /**
     * @param $libBaseDir
     */
    protected function loadComposerAutoLoad($libBaseDir)
    {
        if (!self::$registered) {
            $autoload = $libBaseDir . DS . 'vendor' . DS . 'autoload.php';
            $this->requireFile($autoload);

            $autoloader = new self;
            $autoloader->addNamespace('Ginger', $libBaseDir . DS . 'gingerpayments' . DS . 'ginger-php' . DS . 'src');
            $autoloader->register();
            self::$registered = true;
        }
    }

    /**
     * @param $file
     *
     * @return bool
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            require_once $file;
            return true;
        }

        return false;
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix   The namespace prefix.
     * @param string $baseDir  A base directory for class files in the
     *                         namespace.
     * @param bool   $prepend  If true, prepend the base directory to the stack
     *                         instead of appending it; this causes it to be searched first rather
     *                         than last.
     *
     * @return void
     */
    public function addNamespace($prefix, $baseDir, $prepend = false)
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            $this->prefixes[$prefix][] = $baseDir;
        }
    }

    /**
     * Register loader with SPL auto-loader stack.
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'), true, true);
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     *
     * @return bool|string The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass($class)
    {
        $prefix = $class;
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }

            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix        The namespace prefix.
     * @param string $relativeClass The relative class name.
     *
     * @return bool|string Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relativeClass)
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if ($this->requireFile($file)) {
                return $file;
            }
        }

        return false;
    }

}