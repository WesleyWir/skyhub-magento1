<?php
/**
 * BSeller Platform | B2W - Companhia Digital
 *
 * Do not edit this file if you want to update this module for future new versions.
 *
 * @category  BSeller
 * @package   BSeller_SkyHub
 *
 * @copyright Copyright (c) 2018 B2W Digital - BSeller Platform. (http://www.bseller.com.br)
 *
 * @author    Tiago Sampaio <tiago.sampaio@e-smart.com.br>
 */

/**
 * @var BSeller_SkyHub_Model_Resource_Setup $this
 * @var Magento_Db_Adapter_Pdo_Mysql        $conn
 */

//**********************************************************************************************************************
// Update sales/order
//**********************************************************************************************************************
$tables = [
    $this->getTable('sales/order') => [
        'bseller_skyhub' => [
            'type'     => $this::TYPE_BOOLEAN,
            'nullable' => true,
            'default'  => false,
            'comment'  => 'If Order Was Created By BSeller SkyHub',
        ],
        'bseller_skyhub_code' => [
            'type'     => $this::TYPE_TEXT,
            'size'     => 255,
            'nullable' => true,
            'default'  => false,
            'after'    => 'bseller_skyhub',
            'comment'  => 'SkyHub Code',
        ],
        'bseller_skyhub_channel' => [
            'type'     => $this::TYPE_TEXT,
            'size'     => 255,
            'nullable' => true,
            'default'  => false,
            'after'    => 'bseller_skyhub_code',
            'comment'  => 'SkyHub Code',
        ],
        'bseller_skyhub_invoice_key' => [
            'type'     => $this::TYPE_TEXT,
            'size'     => 255,
            'nullable' => true,
            'default'  => null,
            'after'    => 'bseller_skyhub_invoice_key',
            'comment'  => 'SkyHub Invoice Key',
        ],
    ]
];

foreach ($tables as $tableName => $columns) {
    foreach ($columns as $name => $definition) {
        $conn->addColumn($tableName, $name, $definition);
    }
}

//**********************************************************************************************************************
// Install bseller_skyhub/product_attributes_mapping
//**********************************************************************************************************************
$tableName = (string) $this->getTable('bseller_skyhub/product_attributes_mapping');

/** @var Varien_Db_Ddl_Table $table */
$table = $this->newTable($tableName)
     ->addColumn('skyhub_code', $this::TYPE_TEXT, 255, [
         'nullable' => false,
     ])
     ->addColumn('skyhub_label', $this::TYPE_TEXT, 255, [
         'nullable' => true,
     ])
     ->addColumn('skyhub_description', $this::TYPE_TEXT, null, [
         'nullable' => true,
     ])
     ->addColumn('enabled', $this::TYPE_BOOLEAN, 1, [
         'nullable' => false,
         'default' => true,
     ])
     ->addColumn('cast_type', $this::TYPE_TEXT, 255, [
         'nullable' => false,
     ])
     ->addColumn('validation', $this::TYPE_TEXT, null, [
         'nullable' => true,
     ])
     ->addColumn('attribute_id', $this::TYPE_INTEGER, 255, [
         'nullable' => true,
         'default'  => null,
     ])
     ->addColumn('required', $this::TYPE_BOOLEAN, 1, [
         'nullable' => false,
         'default'  => true,
     ])
     ->addColumn('editable', $this::TYPE_BOOLEAN, 1, [
         'nullable' => false,
         'default'  => true,
     ])
;

$this->addTimestamps($table);
$conn->createTable($table);

$this->addIndex(['skyhub_code', 'attribute_id'], $tableName);
$this->addForeignKey(
    $tableName, 'attribute_id', 'eav/attribute', 'attribute_id', $this::FK_ACTION_SET_NULL, $this::FK_ACTION_SET_NULL
);


//**********************************************************************************************************************
// Install bseller_skyhub/entity_id
//**********************************************************************************************************************
$tableName = (string) $this->getTable('bseller_skyhub/entity_id');
$table = $this->newTable($tableName)
    ->addColumn('entity_id', $this::TYPE_INTEGER, 10, [
        'nullable' => false,
        'primary'  => true,
    ])
    ->addColumn('entity_type', $this::TYPE_TEXT, 255, [
        'nullable' => false,
    ])
    ->addColumn('store_id', $this::TYPE_INTEGER, 10, [
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true,
        'default'  => 0,
    ])
;

$this->addTimestamps($table);
$conn->createTable($table);

$this->addForeignKey($tableName, 'store_id', 'core/store', 'store_id');

$this->addIndex('entity_id',   $tableName, $this::INDEX_TYPE_INDEX);
$this->addIndex('entity_type', $tableName, $this::INDEX_TYPE_INDEX);
$this->addIndex(['entity_id', 'entity_type'], $tableName);


//**********************************************************************************************************************
// Install bseller_skyhub/queue
//**********************************************************************************************************************
$tableName = (string) $this->getTable('bseller_skyhub/queue');
$table = $this->newTable($tableName)
    ->addColumn('entity_id', $this::TYPE_INTEGER, 10, [
        'nullable' => true,
    ])
    ->addColumn('entity_type', $this::TYPE_TEXT, 255, [
        'nullable' => true,
    ])
    ->addColumn('status', $this::TYPE_INTEGER, 2, [
        'nullable' => false,
        'default'  => 0,
    ])
    ->addColumn('messages', $this::TYPE_TEXT, null, [
        'nullable' => true,
    ])
    ->addColumn('can_process', $this::TYPE_INTEGER, 1, [
        'nullable' => false,
        'default'  => 0,
    ])
    ->addColumn('store_id', $this::TYPE_INTEGER, 10, [
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true,
        'default'  => 0,
    ])
;

$this->addCustomTimestamp($table, 'process_after', [], 'Schedule the process to run after this time if needed.');
$this->addTimestamps($table);
$conn->createTable($table);

$this->addForeignKey($tableName, 'store_id', 'core/store', 'store_id');

$this->addIndex('entity_id',   $tableName, $this::INDEX_TYPE_INDEX);
$this->addIndex('entity_type', $tableName, $this::INDEX_TYPE_INDEX);
$this->addIndex(['entity_id', 'entity_type', 'store_id'], $tableName);


//**********************************************************************************************************************
// Install bseller_skyhub/queue_result
//**********************************************************************************************************************
// $tableName = (string) $this->getTable('bseller_skyhub/queue_result');
