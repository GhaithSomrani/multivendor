<?php

/**
 * SQL installation file for multivendor module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

// Create Vendor table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vendor` (
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
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vendor_commission` (
    `id_vendor_commission` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_vendor` int(10) unsigned NOT NULL,
    `commission_rate` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_commission`),
    KEY `id_vendor` (`id_vendor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Category Commission table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'category_commission` (
    `id_category_commission` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_category` int(10) unsigned NOT NULL,
    `id_vendor` int(10) unsigned NOT NULL,
    `commission_rate` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_category_commission`),
    UNIQUE KEY `id_category_vendor` (`id_category`,`id_vendor`),
    KEY `id_vendor` (`id_vendor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Transaction table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vendor_transaction` (
    `id_vendor_transaction` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_vendor` int(10) unsigned NOT NULL,
    `id_order` int(10) unsigned NOT NULL,
    `order_detail_id` int(10) unsigned DEFAULT NULL,
    `id_vendor_payment` int(10) unsigned DEFAULT NULL,
    `commission_amount` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `vendor_amount` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `transaction_type` varchar(32) NOT NULL,
    `status` varchar(32) NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_transaction`),
    KEY `id_vendor` (`id_vendor`),
    KEY `id_order` (`id_order`),
    KEY `order_detail_id` (`order_detail_id`),
    KEY `id_vendor_payment` (`id_vendor_payment`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Payment table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vendor_payment` (
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
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vendor_commission_log` (
    `id_vendor_commission_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_vendor` int(10) unsigned NOT NULL,
    `id_category` int(10) unsigned DEFAULT NULL,
    `old_commission_rate` decimal(20,6) DEFAULT "0.000000",
    `new_commission_rate` decimal(20,6) DEFAULT "0.000000",
    `changed_by` int(10) unsigned NOT NULL,
    `comment` text,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_commission_log`),
    KEY `id_vendor` (`id_vendor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Order Line Status table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_line_status` (
    `id_order_line_status` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_order_detail` int(10) unsigned NOT NULL,
    `id_vendor` int(10) unsigned NOT NULL,
    `status` varchar(32) NOT NULL,
    `comment` text,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_order_line_status`),
    KEY `id_order_detail` (`id_order_detail`),
    KEY `id_vendor` (`id_vendor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Order Line Status Log table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_line_status_log` (
    `id_order_line_status_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_order_detail` int(10) unsigned NOT NULL,
    `id_vendor` int(10) unsigned NOT NULL,
    `old_status` varchar(32),
    `new_status` varchar(32) NOT NULL,
    `comment` text,
    `changed_by` int(10) unsigned NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_order_line_status_log`),
    KEY `id_order_detail` (`id_order_detail`),
    KEY `id_vendor` (`id_vendor`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Order Detail table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'vendor_order_detail` (
    `id_vendor_order_detail` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_order_detail` int(10) unsigned NOT NULL,
    `id_vendor` int(10) unsigned NOT NULL,
    `id_order` int(10) unsigned NOT NULL,
    `commission_rate` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `commission_amount` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `vendor_amount` decimal(20,6) NOT NULL DEFAULT "0.000000",
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_order_detail`),
    UNIQUE KEY `id_order_detail_vendor` (`id_order_detail`,`id_vendor`),
    KEY `id_vendor` (`id_vendor`),
    KEY `id_order` (`id_order`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Order Status Permission table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_status_permission` (
    `id_order_status_permission` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_order_status` int(10) unsigned NOT NULL,
    `is_vendor_allowed` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `is_admin_allowed` tinyint(1) unsigned NOT NULL DEFAULT "1",
    `affects_commission` tinyint(1) unsigned NOT NULL DEFAULT "0",
    `commission_action` varchar(32) DEFAULT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_order_status_permission`),
    KEY `id_order_status` (`id_order_status`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Add default configuration values
$sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'configuration` (`name`, `value`, `date_add`, `date_upd`) 
          VALUES 
          ("MV_DEFAULT_COMMISSION", "10", NOW(), NOW()),
          ("MV_AUTO_APPROVE_VENDORS", "0", NOW(), NOW()),
          ("MV_ALLOW_VENDOR_REGISTRATION", "1", NOW(), NOW())
          ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `date_upd` = VALUES(`date_upd`)';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'order_line_status_type` (
            `id_order_line_status_type` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(64) NOT NULL,
            `color` varchar(32) DEFAULT NULL,
            `is_vendor_allowed` tinyint(1) unsigned NOT NULL DEFAULT "0",
            `is_admin_allowed` tinyint(1) unsigned NOT NULL DEFAULT "1",
            `affects_commission` tinyint(1) unsigned NOT NULL DEFAULT "0",
            `commission_action` varchar(32) DEFAULT "none",
            `position` int(10) unsigned NOT NULL DEFAULT "0",
            `active` tinyint(1) unsigned NOT NULL DEFAULT "1",
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_order_line_status_type`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Add default order line status types
$sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'order_line_status_type` 
            (`name`, `color`, `is_vendor_allowed`, `is_admin_allowed`, `affects_commission`, `commission_action`, `position`, `active`, `date_add`, `date_upd`) 
            VALUES 
            ("Pending", "#FFDD99", 1, 1, 0, "none", 1, 1, NOW(), NOW()),
            ("Processing", "#8AAAE5", 1, 1, 0, "none", 2, 1, NOW(), NOW()),
            ("Shipped", "#32CD32", 1, 1, 1, "add", 3, 1, NOW(), NOW()),
            ("Delivered", "#228B22", 0, 1, 0, "none", 4, 1, NOW(), NOW()),
            ("Cancelled", "#DC143C", 1, 1, 1, "cancel", 5, 1, NOW(), NOW()),
            ("Refunded", "#B22222", 0, 1, 1, "refund", 6, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE `date_upd` = VALUES(`date_upd`)';



// Execute all SQL queries
foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
