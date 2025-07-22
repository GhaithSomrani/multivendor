<?php

/**
 * Class Manifest
 * 
 * Manages manifest data and operations for vendor order grouping
 */
class Manifest extends ObjectModel
{
    /** @var int Vendor ID */
    public $id_vendor;

    /** @var int Order line status type ID */
    public $id_order_line_status_type;

    /** @var string Manifest reference */
    public $reference;

    /** @var string Manifest status */
    public $status;

    /** @var int Total items in manifest */
    public $total_items;

    /** @var string Shipping address JSON */
    public $shipping_address;

    /** @var string Date added */
    public $date_add;

    /** @var string Date updated */
    public $date_upd;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table' => 'mv_manifest',
        'primary' => 'id_manifest',
        'fields' => [
            'id_vendor' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'id_order_line_status_type' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true
            ],
            'reference' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 64
            ],
            'status' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'values' => ['draft', 'verified', 'printed', 'shipped'],
                'default' => 'draft',
                'size' => 32
            ],
            'total_items' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'default' => 0
            ],
            'shipping_address' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isCleanHtml',
                'allow_null' => true
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ]
        ]
    ];

    /**
     * Generate unique manifest reference
     * 
     * @param int $id_vendor
     * @return string
     */
    public static function generateReference(int $id_vendor): string
    {
        $date = date('ymd');
        $count = self::getManifestCountByVendorAndDate($id_vendor, $date);
        return sprintf('%06d-%s-%03d', $id_vendor, $date, $count + 1);
    }

    /**
     * Get manifest count by vendor and date
     * 
     * @param int $id_vendor
     * @param string $date
     * @return int
     */
    private static function getManifestCountByVendorAndDate(int $id_vendor, string $date): int
    {
        $sql = 'SELECT COUNT(*) 
                FROM ' . _DB_PREFIX_ . 'mv_manifest 
                WHERE id_vendor = ' . (int)$id_vendor . ' 
                AND DATE(date_add) = "' . pSQL($date) . '"';

        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * Get all manifests by vendor
     * 
     * @param int $id_vendor
     * @param int|null $limit
     * @param int $offset
     * @return array
     */
    public static function getManifestsByVendor(int $id_vendor, ?int $limit = null, int $offset = 0): array
    {
        $sql = 'SELECT m.*, ols.name as status_type_name
                FROM ' . _DB_PREFIX_ . 'mv_manifest m
                LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type ols 
                ON m.id_order_line_status_type = ols.id_order_line_status_type
                WHERE m.id_vendor = ' . (int)$id_vendor . '
                ORDER BY m.date_add DESC';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$offset . ', ' . (int)$limit;
        }

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Add order details to manifest
     * 
     * @param array $orderDetailIds
     * @return bool
     */
    public function addOrderDetails(array $orderDetailIds): bool
    {
        if (empty($orderDetailIds)) {
            return false;
        }

        $values = [];
        foreach ($orderDetailIds as $id_order_detail) {
            $values[] = '(' . (int)$this->id . ', ' . (int)$id_order_detail . ', NOW())';
        }

        $sql = 'INSERT IGNORE INTO ' . _DB_PREFIX_ . 'mv_manifest_details 
                (id_manifest, id_order_detail, date_add) 
                VALUES ' . implode(', ', $values);

        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->updateTotalItems();
        }

        return $result;
    }

    /**
     * Remove order detail from manifest
     * 
     * @param int $id_order_detail
     * @return bool
     */
    public function removeOrderDetail(int $id_order_detail): bool
    {
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'mv_manifest_details 
                WHERE id_manifest = ' . (int)$this->id . ' 
                AND id_order_detail = ' . (int)$id_order_detail;

        $result = Db::getInstance()->execute($sql);

        if ($result) {
            $this->updateTotalItems();
        }

        return $result;
    }

    /**
     * Get manifest order details
     * 
     * @return array
     */
    public function getOrderDetails(): array
    {
        $sql = 'SELECT md.*, vod.*, od.product_name, od.product_reference,
                       o.reference as order_reference, o.date_add as order_date,
                       c.firstname, c.lastname, addr.address1, addr.address2,
                       addr.city, addr.postcode, cl.name as country_name
                FROM ' . _DB_PREFIX_ . 'mv_manifest_details md
                INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod 
                ON md.id_order_detail = vod.id_order_detail
                INNER JOIN ' . _DB_PREFIX_ . 'order_detail od 
                ON vod.id_order_detail = od.id_order_detail
                INNER JOIN ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
                INNER JOIN ' . _DB_PREFIX_ . 'customer c ON o.id_customer = c.id_customer
                INNER JOIN ' . _DB_PREFIX_ . 'address addr ON o.id_address_delivery = addr.id_address
                INNER JOIN ' . _DB_PREFIX_ . 'country_lang cl 
                ON addr.id_country = cl.id_country AND cl.id_lang = ' . (int)Context::getContext()->language->id . '
                WHERE md.id_manifest = ' . (int)$this->id . '
                ORDER BY o.date_add DESC';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Update total items count
     * 
     * @return bool
     */
    private function updateTotalItems(): bool
    {
        $sql = 'SELECT COUNT(*) 
                FROM ' . _DB_PREFIX_ . 'mv_manifest_details 
                WHERE id_manifest = ' . (int)$this->id;

        $this->total_items = (int)Db::getInstance()->getValue($sql);
        return $this->update();
    }

    /**
     * Check if order detail is already in a manifest
     * 
     * @param int $id_order_detail
     * @param int $id_vendor
     * @return bool
     */
    public static function isOrderDetailInManifest(int $id_order_detail, int $id_vendor): bool
    {
        $sql = 'SELECT COUNT(*) 
                FROM ' . _DB_PREFIX_ . 'mv_manifest_details md
                INNER JOIN ' . _DB_PREFIX_ . 'mv_manifest m ON md.id_manifest = m.id_manifest
                WHERE md.id_order_detail = ' . (int)$id_order_detail . '
                AND m.id_vendor = ' . (int)$id_vendor . '
                AND m.status != "draft"';

        return (bool)Db::getInstance()->getValue($sql);
    }

    /**
     * Get available order details for manifest
     * 
     * @param int $id_vendor
     * @param int $id_status_type
     * @return array
     */
    public static function getAvailableOrderDetails(int $id_vendor, int $id_status_type): array
    {
        $sql = 'SELECT vod.*, od.product_name, od.product_reference,
                       o.reference as order_reference, o.date_add as order_date,
                       c.firstname, c.lastname
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
                INNER JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols 
                ON vod.id_order_detail = ols.id_order_detail AND vod.id_vendor = ols.id_vendor
                INNER JOIN ' . _DB_PREFIX_ . 'order_detail od 
                ON vod.id_order_detail = od.id_order_detail
                INNER JOIN ' . _DB_PREFIX_ . 'orders o ON od.id_order = o.id_order
                INNER JOIN ' . _DB_PREFIX_ . 'customer c ON o.id_customer = c.id_customer
                WHERE vod.id_vendor = ' . (int)$id_vendor . '
                AND ols.id_order_line_status_type = ' . (int)$id_status_type . '
                AND vod.id_order_detail NOT IN (
                    SELECT md.id_order_detail 
                    FROM ' . _DB_PREFIX_ . 'mv_manifest_details md
                    INNER JOIN ' . _DB_PREFIX_ . 'mv_manifest m ON md.id_manifest = m.id_manifest
                    WHERE m.id_vendor = ' . (int)$id_vendor . ' AND m.status != "draft"
                )
                ORDER BY o.date_add DESC';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Get vendor addresses for manifest
     * 
     * @param int $id_vendor
     * @return array
     */
    public static function getVendorAddresses(int $id_vendor): array
    {
        $sql = 'SELECT a.*, cl.name as country_name
                FROM ' . _DB_PREFIX_ . 'mv_vendor v
                INNER JOIN ' . _DB_PREFIX_ . 'address a ON v.id_customer = a.id_customer
                INNER JOIN ' . _DB_PREFIX_ . 'country_lang cl 
                ON a.id_country = cl.id_country AND cl.id_lang = ' . (int)Context::getContext()->language->id . '
                WHERE v.id_vendor = ' . (int)$id_vendor . '
                AND a.active = 1
                ORDER BY a.date_add DESC';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Get manifest statistics for vendor
     * 
     * @param int $id_vendor
     * @return array
     */
    public static function getManifestStatistics(int $id_vendor): array
    {
        $sql = 'SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(total_items) as total_items
                FROM ' . _DB_PREFIX_ . 'mv_manifest 
                WHERE id_vendor = ' . (int)$id_vendor . '
                GROUP BY status';

        $results = Db::getInstance()->executeS($sql) ?: [];

        $statistics = [
            'total_manifests' => 0,
            'total_items' => 0,
            'by_status' => []
        ];

        foreach ($results as $row) {
            $statistics['total_manifests'] += (int)$row['count'];
            $statistics['total_items'] += (int)$row['total_items'];
            $statistics['by_status'][$row['status']] = [
                'count' => (int)$row['count'],
                'total_items' => (int)$row['total_items']
            ];
        }

        return $statistics;
    }

    /**
     * Delete manifest and its details
     * 
     * @return bool
     */
    public function delete(): bool
    {
        // Delete manifest details first
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'mv_manifest_details 
                WHERE id_manifest = ' . (int)$this->id;

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        // Delete manifest
        return parent::delete();
    }

    /**
     * Validate manifest before save
     * 
     * @param bool $die
     * @param bool $error_return
     * @return bool|string
     */
    public function validateFields($die = true, $error_return = false)
    {
        // Custom validation
        if (!$this->id_vendor || !$this->id_order_line_status_type) {
            if ($error_return) {
                return 'Vendor ID and Order Line Status Type are required';
            }
            if ($die) {
                die('Vendor ID and Order Line Status Type are required');
            }
            return false;
        }

        if (!in_array($this->status, ['draft', 'verified', 'printed', 'shipped'])) {
            if ($error_return) {
                return 'Invalid status value';
            }
            if ($die) {
                die('Invalid status value');
            }
            return false;
        }

        return parent::validateFields($die, $error_return);
    }

    /**
     * Get manifest by reference
     * 
     * @param string $reference
     * @param int $id_vendor
     * @return Manifest|null
     */
    public static function getByReference(string $reference, int $id_vendor): ?Manifest
    {
        $sql = 'SELECT id_manifest 
                FROM ' . _DB_PREFIX_ . 'mv_manifest 
                WHERE reference = "' . pSQL($reference) . '" 
                AND id_vendor = ' . (int)$id_vendor;

        $id = Db::getInstance()->getValue($sql);

        if ($id) {
            $manifest = new Manifest((int)$id);
            return Validate::isLoadedObject($manifest) ? $manifest : null;
        }

        return null;
    }

    /**
     * Get total value of manifest
     * 
     * @return float
     */
    public function getTotalValue(): float
    {
        $sql = 'SELECT SUM(vod.vendor_amount) 
                FROM ' . _DB_PREFIX_ . 'mv_manifest_details md
                INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod 
                ON md.id_order_detail = vod.id_order_detail
                WHERE md.id_manifest = ' . (int)$this->id;

        return (float)Db::getInstance()->getValue($sql);
    }

    /**
     * Get total quantity of manifest
     * 
     * @return int
     */
    public function getTotalQuantity(): int
    {
        $sql = 'SELECT SUM(vod.product_quantity) 
                FROM ' . _DB_PREFIX_ . 'mv_manifest_details md
                INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod 
                ON md.id_order_detail = vod.id_order_detail
                WHERE md.id_manifest = ' . (int)$this->id;

        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * Check if manifest can be edited
     * 
     * @return bool
     */
    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'verified']);
    }

    /**
     * Check if manifest can be printed
     * 
     * @return bool
     */
    public function canPrint(): bool
    {
        return $this->total_items > 0;
    }

    /**
     * Update manifest status
     * 
     * @param string $newStatus
     * @return bool
     */
    public function updateStatus(string $newStatus): bool
    {
        if (!in_array($newStatus, ['draft', 'verified', 'printed', 'shipped'])) {
            return false;
        }

        $this->status = $newStatus;
        return $this->update();
    }
}
