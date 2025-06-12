<?php

/**
 * SQL uninstallation file for multivendor module
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

// Drop all tables created by the module
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mv_vendor`';
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mv_vendor_commission`';
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mv_vendor_transaction`';
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mv_vendor_payment`';
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mv_vendor_commission_log`';
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mv_order_line_status`';
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mv_order_line_status_log`';
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mv_vendor_order_detail`';
// $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mv_order_line_status_type`';


// Remove configuration values
$sql[] = 'DELETE FROM `' . _DB_PREFIX_ . 'configuration` WHERE `name` LIKE "MV_%"';

// Execute all SQL queries
foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
