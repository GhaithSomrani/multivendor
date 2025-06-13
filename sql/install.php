<?php

/**
 * SQL installation file for multivendor module - PERFECT WORKING VERSION
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

// Create Order Line Status Type table FIRST (referenced by other tables)
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_order_line_status_type` (
    `id_order_line_status_type` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(64) NOT NULL,
    `color` varchar(32) DEFAULT NULL,
    `is_vendor_allowed` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `is_admin_allowed` tinyint(1) unsigned NOT NULL DEFAULT 1,
    `affects_commission` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `commission_action` varchar(32) DEFAULT "none",
    `available_status` varchar(255) DEFAULT NULL COMMENT "Comma-separated list of status IDs",
    `position` int(10) unsigned NOT NULL DEFAULT 0,
    `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_order_line_status_type`),
    UNIQUE KEY `unique_name` (`name`),
    KEY `position` (`position`),
    KEY `active` (`active`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor` (
    `id_vendor` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_customer` int(10) unsigned NOT NULL,
    `id_supplier` int(10) unsigned NOT NULL,
    `shop_name` varchar(128) NOT NULL,
    `description` text,
    `logo` varchar(255),
    `banner` varchar(255),
    `status` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_vendor`),
    UNIQUE KEY `unique_customer` (`id_customer`),
    UNIQUE KEY `unique_supplier` (`id_supplier`),
    KEY `status` (`status`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Commission table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor_commission` (
    `id_vendor_commission` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_vendor` int(10) unsigned NOT NULL,
    `commission_rate` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_commission`),
    UNIQUE KEY `unique_id_vendor` (`id_vendor`)
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
    `product_price` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `product_quantity` int(10) unsigned NOT NULL DEFAULT 1,
    `product_attribute_id` int(10) unsigned DEFAULT NULL,
    `commission_rate` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `commission_amount` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `vendor_amount` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_order_detail`),
    UNIQUE KEY `id_order_detail_vendor` (`id_order_detail`,`id_vendor`),
    KEY `id_vendor` (`id_vendor`),
    KEY `id_order` (`id_order`),
    KEY `product_id` (`product_id`),
    KEY `product_reference` (`product_reference`),
    KEY `product_attribute_id` (`product_attribute_id`)
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
    UNIQUE KEY `unique_order_detail_vendor` (`id_order_detail`, `id_vendor`),
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
    `changed_by` int(10) unsigned NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_order_line_status_log`),
    KEY `id_order_detail` (`id_order_detail`),
    KEY `id_vendor` (`id_vendor`),
    KEY `old_id_order_line_status_type` (`old_id_order_line_status_type`),
    KEY `new_id_order_line_status_type` (`new_id_order_line_status_type`),
    KEY `changed_by` (`changed_by`),
    KEY `date_add` (`date_add`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Payment table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor_payment` (
    `id_vendor_payment` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_vendor` int(10) unsigned NOT NULL,
    `amount` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `payment_method` varchar(64) NOT NULL,
    `reference` varchar(64) NOT NULL,
    `status` varchar(32) NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_payment`),
    KEY `id_vendor` (`id_vendor`),
    KEY `status` (`status`),
    KEY `date_add` (`date_add`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Transaction table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor_transaction` (
    `id_vendor_transaction` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `order_detail_id` int(10) unsigned DEFAULT NULL,
    `id_vendor_payment` int(10) unsigned DEFAULT NULL,
    `vendor_amount` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `transaction_type` varchar(32) NOT NULL,
    `status` varchar(32) NOT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_transaction`),
    KEY `order_detail_id` (`order_detail_id`),
    KEY `id_vendor_payment` (`id_vendor_payment`),
    KEY `transaction_type` (`transaction_type`),
    KEY `status` (`status`),
    KEY `date_add` (`date_add`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Create Vendor Commission Log table
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mv_vendor_commission_log` (
    `id_vendor_commission_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_vendor` int(10) unsigned NOT NULL,
    `id_category` int(10) unsigned DEFAULT NULL,
    `old_commission_rate` decimal(20,6) DEFAULT 0.000000,
    `new_commission_rate` decimal(20,6) DEFAULT 0.000000,
    `changed_by` int(10) unsigned NOT NULL,
    `comment` text,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_vendor_commission_log`),
    KEY `id_vendor` (`id_vendor`),
    KEY `changed_by` (`changed_by`),
    KEY `date_add` (`date_add`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Execute table creation queries
foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        $error = Db::getInstance()->getMsgError();
        PrestaShopLogger::addLog('MultiVendor Install Error: ' . $error . ' - Query: ' . $query, 3);
        return false;
    }
}

// Add default configuration values
$config_sql = "INSERT INTO `" . _DB_PREFIX_ . "configuration` (`name`, `value`, `date_add`, `date_upd`) 
          VALUES 
          ('MV_DEFAULT_COMMISSION', '10', NOW(), NOW()),
          ('MV_AUTO_APPROVE_VENDORS', '0', NOW(), NOW()),
          ('MV_ALLOW_VENDOR_REGISTRATION', '1', NOW(), NOW())
          ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `date_upd` = VALUES(`date_upd`)";

if (Db::getInstance()->execute($config_sql) == false) {
    $error = Db::getInstance()->getMsgError();
    PrestaShopLogger::addLog('MultiVendor Config Install Error: ' . $error, 3);
    return false;
}

// Insert default order line status types
$status_sql = "INSERT INTO `" . _DB_PREFIX_ . "mv_order_line_status_type` 
            (`name`, `is_admin_allowed`, `is_vendor_allowed`, `color`, `affects_commission`, `commission_action`, `position`, `active`, `available_status`, `date_add`, `date_upd`) 
            VALUES 
            ('en attente client', 1, 0, '#0079FF', 0, 'none', 1, 1, '2,19,22', NOW(), NOW()),
            ('à traiter', 1, 1, '#FF865D', 0, 'none', 2, 1, '3,18,19,22', NOW(), NOW()),
            ('disponible', 1, 1, '#0079FF', 0, 'none', 3, 1, '4,19,22', NOW(), NOW()),
            ('prêt pour ramassage', 1, 1, '#0079FF', 1, 'add', 4, 1, '5,19,22', NOW(), NOW()),
            ('ramassé', 1, 1, '#0079FF', 1, 'add', 5, 1, '6,22', NOW(), NOW()),
            ('réception magasin', 1, 0, '#FF865D', 1, 'add', 6, 1, '7,10,9,17,22', NOW(), NOW()),
            ('prêt pour expédition', 1, 0, '#FF865D', 1, 'add', 7, 1, '8,22', NOW(), NOW()),
            ('expédié', 1, 0, '#00DFA2', 1, 'add', 8, 1, '11,10,9,15,22', NOW(), NOW()),
            ('endommagé', 1, 0, '#FF0060', 1, 'refund', 9, 1, '20,22', NOW(), NOW()),
            ('perdu', 1, 0, '#FF0060', 1, 'add', 10, 1, '22', NOW(), NOW()),
            ('rejeté', 1, 0, '#FF0060', 1, 'refund', 11, 1, '12,22', NOW(), NOW()),
            ('retour magasin', 1, 0, '#FF0060', 1, 'refund', 12, 1, '13,22', NOW(), NOW()),
            ('retour fournisseur', 1, 0, '#FF0060', 1, 'refund', 13, 1, '14,22', NOW(), NOW()),
            ('remboursé', 1, 0, '#FF0060', 1, 'cancel', 14, 1, '22', NOW(), NOW()),
            ('livré', 1, 0, '#00DFA2', 1, 'add', 15, 1, '16', NOW(), NOW()),
            ('payé', 1, 0, '#00DFA2', 1, 'add', 16, 1, '21', NOW(), NOW()),
            ('non conforme', 1, 0, '#FF0060', 1, 'cancel', 17, 1, '13', NOW(), NOW()),
            ('rupture de stock', 1, 1, '#FF0060', 0, 'none', 18, 1, '22', NOW(), NOW()),
            ('annulé par client', 1, 0, '#000000', 0, 'none', 19, 1, '22', NOW(), NOW()),
            ('réception endommagé', 1, 0, '#FF0060', 0, 'none', 20, 1, '12', NOW(), NOW()),
            ('demande du retour', 1, 0, '#FF0060', 0, 'none', 21, 1, '12', NOW(), NOW()),
            ('Faute', 1, 0, '#FFFFFF', 0, 'none', 22, 0, '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22', NOW(), NOW())
            ON DUPLICATE KEY UPDATE `date_upd` = VALUES(`date_upd`)";

if (Db::getInstance()->execute($status_sql) == false) {
    $error = Db::getInstance()->getMsgError();
    PrestaShopLogger::addLog('MultiVendor Status Install Error: ' . $error, 3);
    return false;
}

return true;