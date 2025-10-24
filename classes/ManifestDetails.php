<?php

/**
 * ManifestDetails model class
 * Handles the relationship between manifests and order details
 */

use function PHPSTORM_META\map;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'multivendor/classes/AuditLogTrait.php';
require_once _PS_MODULE_DIR_ . 'multivendor/classes/AuditLog.php';

class ManifestDetails extends ObjectModel
{
    use AuditLogTrait;
    /** @var int ManifestDetails ID */
    public $id;

    /** @var int Manifest ID */
    public $id_manifest;

    /** @var int Order detail ID */
    public $id_order_details;

    /** @var string Creation date */
    public $date_add;

    /** @var string Update date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'mv_manifest_details',
        'primary' => 'id_manifest_details',
        'fields' => [
            'id_manifest' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order_details' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * Constructor
     *
     * @param int|null $id ManifestDetails ID
     * @param int|null $id_lang Language ID
     */
    public function __construct($id = null, $id_lang = null)
    {

        parent::__construct($id, $id_lang);
    }



    public static function getOrderDetailsByManifest($id_manifest)
    {
        $query = new DbQuery();
        $query->select('id_order_details');
        $query->from('mv_manifest_details');
        $query->where('id_manifest = ' . (int)$id_manifest);
        return array_column(Db::getInstance()->executeS($query), 'id_order_details');;
    }

    // function that return array of a manifest details based on the id_manifest
    public static function getByManifestAndOrderDetails($id_manifest, $id_order_details)
    {
        $query = new DbQuery();
        $query->select('id_manifest_details');
        $query->from('mv_manifest_details');
        $query->where('id_manifest = ' . (int)$id_manifest);
        $query->where('id_order_details = ' . (int)$id_order_details);
        return Db::getInstance()->getValue($query);
    }
}
