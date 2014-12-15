<?php

$this->startSetup();

$table = $this->getConnection()->newTable($this->getTable('heystaks/user'));

$table->addColumn('heystaks_id',
    Varien_Db_Ddl_Table::TYPE_INTEGER,
    11,
    array('identity' => true, 'primary' => true));
$table->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array('nullable' => false, 'default' => 0));
$table->addColumn('user_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => false, 'default' => ''));

$table->addForeignKey($this->getFkName('heystaks/user', 'customer_id', 'customer/entity', 'entity_id'),
    'customer_id',
    $this->getTable('customer/entity'),
    'entity_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE,
    Varien_Db_Ddl_Table::ACTION_CASCADE);

$this->getConnection()->createTable($table);

$this->endSetup();