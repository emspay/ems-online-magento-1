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
 * @package     EMS_PAYMENT_Payment
 * @author      Ginger Payments B.V. (info@gingerpayments.com)
 * @copyright   COPYRIGHT (C) 2019 GINGER PAYMENTS B.V. (https://www.gingerpayments.com)
 * @license     The MIT License (MIT)
 */

/** @var $this Mage_Catalog_Model_Resource_Setup */
$this->startSetup();
$connection = $this->getConnection();

$connection->addColumn(
    $this->getTable('sales/quote_payment'), 'ems_payment_order_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS Order Id',
    )
);

$connection->addColumn(
    $this->getTable('sales/quote_payment'), 'ems_paymentg_banktransfer_reference', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS Banktransfer Reference',
    )
);

$connection->addColumn(
    $this->getTable('sales/quote_payment'), 'ems_payment_ideal_issuer_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS iDeal Issuer ID',
    )
);

$connection->addColumn(
    $this->getTable('sales/order_payment'), 'ems_payment_order_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS Order Id',
    )
);

$connection->addColumn(
    $this->getTable('sales/order_payment'), 'ems_payment_banktransfer_reference', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS Banktransfer Reference',
    )
);

$connection->addColumn(
    $this->getTable('sales/order_payment'), 'ems_payment_ideal_issuer_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS iDeal Issuer Id',
    )
);

$connection->addColumn(
    $this->getTable('sales/quote'), 'ems_payment_order_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS Order Id',
    )
);

$connection->addColumn(
    $this->getTable('sales/quote'), 'ems_payment_banktransfer_reference', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS Banktransfer Reference',
    )
);

$connection->addColumn(
    $this->getTable('sales/quote'), 'ems_payment_ideal_issuer_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS iDEAL Issuer Id',
    )
);

$connection->addColumn(
    $this->getTable('sales/order'), 'ems_payment_order_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS Order Id',
    )
);

$connection->addColumn(
    $this->getTable('sales/order'), 'ems_payment_banktransfer_reference', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS Banktransfer Reference',
    )
);

$connection->addColumn(
    $this->getTable('sales/order'), 'ems_payment_ideal_issuer_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'default'   => NULL,
        'comment'   => 'EMS iDEAL Issuer Id',
    )
);

$this->endSetup();
