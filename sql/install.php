<?php

/**
 * SQL installation file for multivendor module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

// Create Vendor table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor` (
    `id_vendor` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_customer` int(10) unsigned NOT NULL,
    `id_supplier` int(10) unsigned NOT NULL,
    `shop_name` varchar(128) NOT NULL,
    `description` text,
    `logo` varchar(255),
    `banner` varchar(255),
    `status` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_vendor`),
    KEY `id_customer` (`id_customer`),
    KEY `id_supplier` (`id_supplier`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Commission table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor_commission` (
    `id_vendor_commission` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_vendor` int(10) unsigned NOT NULL,
    `commission_rate` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_commission`),
    UNIQUE KEY `unique_id_vendor` (`id_vendor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Category Commission table
// $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'category_commission` (
//     `id_category_commission` int(10) unsigned NOT NULL AUTO_INCREMENT,
//     `id_category` int(10) unsigned NOT NULL,
//     `id_vendor` int(10) unsigned NOT NULL,
//     `commission_rate` decimal(20,6) NOT NULL DEFAULT "0.000000",
//     `date_add` datetime NOT NULL,
//     `date_upd` datetime NOT NULL,
//     PRIMARY KEY (`id_category_commission`),
//     UNIQUE KEY `id_category_vendor` (`id_category`,`id_vendor`),
//     KEY `id_vendor` (`id_vendor`)
// ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Transaction table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor_transaction` (
    `id_vendor_transaction` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `order_detail_id` int(10) unsigned DEFAULT NULL,
    `id_vendor_payment` int(10) unsigned DEFAULT NULL,
    `vendor_amount` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `transaction_type` varchar(32) NOT NULL,
    `status` varchar(32) NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_transaction`),
    KEY `order_detail_id` (`order_detail_id`),
    KEY `id_vendor_payment` (`id_vendor_payment`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Payment table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor_payment` (
    `id_vendor_payment` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_vendor` int(10) unsigned NOT NULL,
    `amount` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `payment_method` varchar(64) NOT NULL,
    `reference` varchar(64) NOT NULL,
    `status` varchar(32) NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_payment`),
    KEY `id_vendor` (`id_vendor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Commission Log table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor_commission_log` (
    `id_vendor_commission_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_vendor` int(10) unsigned NOT NULL,
    `id_category` int(10) unsigned DEFAULT NULL,
    `old_commission_rate` decimal(20,6) DEFAULT "0.000000",
    `new_commission_rate` decimal(20,6) DEFAULT "0.000000",
    `changed_by` VARCHAR(256) NULL,
    `comment` text,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_commission_log`),
    KEY `id_vendor` (`id_vendor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
// Create Order Line Status table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_order_line_status` (
    `id_order_line_status` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_order_detail` int(10) unsigned NOT NULL,
    `id_vendor` int(10) unsigned NOT NULL,
    `id_order_line_status_type` int(10) unsigned NOT NULL,
    `comment` text,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_order_line_status`),
    KEY `id_order_detail` (`id_order_detail`),
    KEY `id_vendor` (`id_vendor`),
    KEY `id_order_line_status_type` (`id_order_line_status_type`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
// Create Order Line Status Log table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_order_line_status_log` (
    `id_order_line_status_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_order_detail` int(10) unsigned NOT NULL,
    `id_vendor` int(10) unsigned NOT NULL,
    `old_id_order_line_status_type` int(10) unsigned DEFAULT NULL,
    `new_id_order_line_status_type` int(10) unsigned NOT NULL,
    `comment` text,
    `changed_by` VARCHAR(256) NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_order_line_status_log`),
    KEY `id_order_detail` (`id_order_detail`),
    KEY `id_vendor` (`id_vendor`),
    KEY `old_id_order_line_status_type` (`old_id_order_line_status_type`),
    KEY `new_id_order_line_status_type` (`new_id_order_line_status_type`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Order Detail table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor_order_detail` (
    `id_vendor_order_detail` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_order_detail` int(10) unsigned NOT NULL,
    `id_vendor` int(10) unsigned NOT NULL,
    `id_order` int(10) unsigned NOT NULL,
    `product_id` int(10) unsigned NOT NULL,
    `product_name` varchar(255) NOT NULL,
    `product_reference` varchar(128) DEFAULT NULL,
    `product_mpn` varchar(128) DEFAULT NULL,
    `product_price` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `product_quantity` int(10) unsigned NOT NULL DEFAULT "1",
    `product_attribute_id` int(10) unsigned DEFAULT NULL,
    `commission_rate` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `commission_amount` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `vendor_amount` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_order_detail`),
    UNIQUE KEY `id_order_detail_vendor` (`id_order_detail`,`id_vendor`),
    KEY `id_vendor` (`id_vendor`),
    KEY `id_order` (`id_order`),
    KEY `product_id` (`product_id`),
    KEY `product_reference` (`product_reference`),
    KEY `product_attribute_id` (`product_attribute_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';



// Add default configuration values
// $sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'configuration` (`name`, `value`, `date_add`, `date_upd`) 
//           VALUES 
//           ("MV_DEFAULT_COMMISSION", "10", NOW(), NOW()),
//           ("MV_AUTO_APPROVE_VENDORS", "0", NOW(), NOW()),
//           ("MV_HIDE_FROM_VENDOR", "", NOW(), NOW())
//           ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `date_upd` = VALUES(`date_upd`)';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_order_line_status_type` (
            `id_order_line_status_type` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(64) NOT NULL,
            `color` varchar(32) DEFAULT NULL,
            `is_vendor_allowed` tinyint(1) unsigned NOT NULL DEFAULT "0",
            `is_admin_allowed` tinyint(1) unsigned NOT NULL DEFAULT "1",
            `affects_commission` tinyint(1) unsigned NOT NULL DEFAULT "0",
            `commission_action` varchar(32) DEFAULT "none",
            `available_status` VARCHAR(255) NULL COMMENT "Comma-separated list of status IDs",
            `position` int(10) unsigned NOT NULL DEFAULT "0",
            `active` tinyint(1) unsigned NOT NULL DEFAULT "1",
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_order_line_status_type`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_manifest` (
    `id_manifest` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `reference` varchar(128) NOT NULL,
    `id_vendor` int(10) unsigned NOT NULL,
    `id_address` int(10) unsigned NULL,
    `id_manifest_status` int(10) unsigned NOT NULL DEFAULT "1",
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    `id_manifest_type`  int(10) unsigned NOT NULL,
    PRIMARY KEY (`id_manifest`),
    UNIQUE KEY `reference` (`reference`),
    KEY `id_address` (`id_address`),
    KEY `id_manifest_status` (`id_manifest_status`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Manifest Details table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_manifest_details` (
    `id_manifest_details` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_manifest` int(10) unsigned NOT NULL,
    `id_order_details` int(10) unsigned NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_manifest_details`),
    UNIQUE KEY `id_manifest_order_details` (`id_manifest`, `id_order_details`),
    KEY `id_manifest` (`id_manifest`),
    KEY `id_order_details` (`id_order_details`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_manifest_status_type` (
        `id_manifest_status_type` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `allowed_manifest_status_type_ids` TEXT,
        `allowed_order_line_status_type_ids` TEXT,
        `next_order_line_status_type_ids` INT(11),
        `id_manifest_type` INT(11) NOT NULL,
        `allowed_modification` TINYINT(1) DEFAULT 0,
        `allowed_delete` TINYINT(1) DEFAULT 0,
        `position` INT(11) DEFAULT 1,
        `active` TINYINT(1) DEFAULT 1,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_manifest_status_type`),
        INDEX `idx_active` (`active`),
        INDEX `idx_position` (`position`),
        INDEX `idx_manifest_type` (`id_manifest_type`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_manifest_type` (
    `id_manifest_type` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_manifest_type`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] =  'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_product_commission` (
    `id_product_commission` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT(11) UNSIGNED NOT NULL,
    `id_attribute` INT(11) UNSIGNED ,
    `commission_rate` DECIMAL(10,3) NOT NULL,
    `expires_at` DATETIME NULL,
    `changed_by` VARCHAR(256) NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_product_commission`),
    UNIQUE KEY `product_attribute_unique` (`id_product`, `id_attribute`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] =  'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_product_commission_log` (
    `id_product_commission_log` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product_commission` INT(11) UNSIGNED NOT NULL,
    `old_commission_rate` DECIMAL(10,3) NOT NULL,
    `new_commission_rate` DECIMAL(10,3) NOT NULL,
    `changed_by` VARCHAR(256) NULL,
    `comment` TEXT,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_product_commission_log`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


// Execute all SQL queries

Configuration::updateValue('mv_pickup', 0);
Configuration::updateValue('mv_returns', 0);
foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
