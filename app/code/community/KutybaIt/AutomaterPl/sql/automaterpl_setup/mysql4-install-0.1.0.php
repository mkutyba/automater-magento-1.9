<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->addAttribute('catalog_product', 'automater_product_id',
    [
        'type' => 'varchar',
        'label' => 'Product ID in Automater',
        'user_defined' => false,
        'group' => 'General',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible' => true,
        'filterable' => false,
        'searchable' => false,
        'comparable' => false,
        'visible_on_front' => false,
        'visible_in_advanced_search' => false,
        'used_in_product_listing' => false,
        'is_html_allowed_on_front' => false,
        'required' => false,
    ]
);

$installer->getConnection()->addColumn(
    $installer->getTable('sales_flat_order'), 'automaterpl_cart_id', 'varchar(255)'
);

$installer->endSetup();