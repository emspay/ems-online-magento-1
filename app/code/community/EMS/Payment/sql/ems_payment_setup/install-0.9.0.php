<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
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
    $this->getTable('sales/quote_payment'), 'ems_payment_banktransfer_reference', array(
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
