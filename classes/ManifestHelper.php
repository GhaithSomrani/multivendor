<?php
/**
 * Class ManifestHelper
 * 
 * Provides utility methods for manifest operations
 * Uses Singleton pattern for consistent access
 */
class ManifestHelper
{
    /** @var ManifestHelper|null */
    private static ?ManifestHelper $instance = null;

    /** @var array Status configuration */
    private array $statusConfig = [
        'draft' => [
            'label' => 'Draft',
            'color' => 'secondary',
            'can_edit' => true,
            'can_print' => true
        ],
        'verified' => [
            'label' => 'Verified',
            'color' => 'info', 
            'can_edit' => true,
            'can_print' => true
        ],
        'printed' => [
            'label' => 'Printed',
            'color' => 'primary',
            'can_edit' => false,
            'can_print' => true
        ],
        'shipped' => [
            'label' => 'Shipped',
            'color' => 'success',
            'can_edit' => false,
            'can_print' => true
        ]
    ];

    /**
     * Private constructor for Singleton
     */
    private function __construct()
    {
        // Initialize if needed
    }

    /**
     * Get singleton instance
     * 
     * @return ManifestHelper
     */
    public static function getInstance(): ManifestHelper
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get status configuration
     * 
     * @param string|null $status
     * @return array
     */
    public function getStatusConfig(?string $status = null): array
    {
        if ($status) {
            return $this->statusConfig[$status] ?? [];
        }
        return $this->statusConfig;
    }

    /**
     * Validate manifest data
     * 
     * @param array $data
     * @return array Validation result
     */
    public function validateManifestData(array $data): array
    {
        $errors = [];

        if (empty($data['id_vendor'])) {
            $errors[] = 'Vendor ID is required';
        }

        if (empty($data['id_order_line_status_type'])) {
            $errors[] = 'Order line status type is required';
        }

        if (empty($data['order_detail_ids']) || !is_array($data['order_detail_ids'])) {
            $errors[] = 'Order detail IDs are required';
        }

        if (isset($data['status']) && !array_key_exists($data['status'], $this->statusConfig)) {
            $errors[] = 'Invalid status provided';
        }

        if (!empty($data['order_detail_ids']) && !empty($data['id_vendor'])) {
            $invalidIds = $this->validateOrderDetailsForVendor(
                $data['order_detail_ids'], 
                (int)$data['id_vendor']
            );
            
            if (!empty($invalidIds)) {
                $errors[] = 'Invalid order detail IDs: ' . implode(', ', $invalidIds);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate order details belong to vendor
     * 
     * @param array $orderDetailIds
     * @param int $idVendor
     * @return array Invalid IDs
     */
    private function validateOrderDetailsForVendor(array $orderDetailIds, int $idVendor): array
    {
        if (empty($orderDetailIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderDetailIds), '?'));
        $params = array_merge([$idVendor], array_map('intval', $orderDetailIds));

        $sql = 'SELECT id_order_detail 
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail 
                WHERE id_vendor = ? 
                AND id_order_detail IN (' . $placeholders . ')';

        $validIds = Db::getInstance()->executeS($sql, $params);
        $validIdArray = array_column($validIds ?: [], 'id_order_detail');

        return array_diff($orderDetailIds, $validIdArray);
    }

    /**
     * Format manifest for API response
     * 
     * @param Manifest $manifest
     * @param bool $includeDetails
     * @return array
     */
    public function formatManifestForResponse(Manifest $manifest, bool $includeDetails = false): array
    {
        $statusConfig = $this->getStatusConfig($manifest->status);
        
        $data = [
            'id' => (int)$manifest->id,
            'reference' => $manifest->reference,
            'status' => [
                'code' => $manifest->status,
                'label' => $statusConfig['label'] ?? $manifest->status,
                'color' => $statusConfig['color'] ?? 'secondary'
            ],
            'id_vendor' => (int)$manifest->id_vendor,
            'id_order_line_status_type' => (int)$manifest->id_order_line_status_type,
            'total_items' => (int)$manifest->total_items,
            'shipping_address' => $manifest->shipping_address ? json_decode($manifest->shipping_address, true) : null,
            'date_add' => $manifest->date_add,
            'date_upd' => $manifest->date_upd,
            'permissions' => [
                'can_edit' => $statusConfig['can_edit'] ?? false,
                'can_print' => $statusConfig['can_print'] ?? false
            ]
        ];

        if ($includeDetails) {
            $data['order_details'] = $manifest->getOrderDetails();
        }

        return $data;
    }

    /**
     * Get manifest statistics for vendor
     * 
     * @param int $idVendor
     * @return array
     */
    public function getManifestStatistics(int $idVendor): array
    {
        $sql = 'SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(total_items) as total_items
                FROM ' . _DB_PREFIX_ . 'mv_manifest 
                WHERE id_vendor = ' . (int)$idVendor . '
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
            
            $statusConfig = $this->getStatusConfig($row['status']);
            $statistics['by_status'][$row['status']] = [
                'count' => (int)$row['count'],
                'total_items' => (int)$row['total_items'],
                'label' => $statusConfig['label'] ?? $row['status'],
                'color' => $statusConfig['color'] ?? 'secondary'
            ];
        }

        return $statistics;
    }

    /**
     * Check if order details can be added to new manifest
     * 
     * @param array $orderDetailIds
     * @param int $idVendor
     * @return array
     */
    public function checkOrderDetailsAvailability(array $orderDetailIds, int $idVendor): array
    {
        if (empty($orderDetailIds)) {
            return ['available' => [], 'unavailable' => []];
        }

        $placeholders = implode(',', array_fill(0, count($orderDetailIds), '?'));
        $params = array_merge([$idVendor], array_map('intval', $orderDetailIds), [$idVendor]);

        $sql = 'SELECT md.id_order_detail
                FROM ' . _DB_PREFIX_ . 'mv_manifest_details md
                INNER JOIN ' . _DB_PREFIX_ . 'mv_manifest m ON md.id_manifest = m.id_manifest
                WHERE m.id_vendor = ? 
                AND md.id_order_detail IN (' . $placeholders . ')
                AND m.status != "draft"
                AND m.id_vendor = ?';

        $unavailableIds = Db::getInstance()->executeS($sql, $params);
        $unavailableIdArray = array_column($unavailableIds ?: [], 'id_order_detail');
        
        $availableIds = array_diff($orderDetailIds, $unavailableIdArray);

        return [
            'available' => array_values($availableIds),
            'unavailable' => $unavailableIdArray
        ];
    }

    /**
     * Get vendor addresses formatted for selection
     * 
     * @param int $idVendor
     * @return array
     */
    public function getFormattedVendorAddresses(int $idVendor): array
    {
        $addresses = Manifest::getVendorAddresses($idVendor);
        
        return array_map(function($address) {
            return [
                'id' => (int)$address['id_address'],
                'company' => $address['company'] ?? '',
                'firstname' => $address['firstname'] ?? '',
                'lastname' => $address['lastname'] ?? '',
                'address1' => $address['address1'],
                'address2' => $address['address2'] ?? '',
                'city' => $address['city'],
                'postcode' => $address['postcode'],
                'phone' => $address['phone'] ?? '',
                'country_name' => $address['country_name'] ?? '',
                'display_name' => $this->formatAddressDisplay($address),
                'is_default' => isset($address['is_default']) ? (bool)$address['is_default'] : false
            ];
        }, $addresses);
    }

    /**
     * Format address for display
     * 
     * @param array $address
     * @return string
     */
    private function formatAddressDisplay(array $address): string
    {
        $parts = [];
        
        if (!empty($address['company'])) {
            $parts[] = $address['company'];
        } elseif (!empty($address['firstname']) && !empty($address['lastname'])) {
            $parts[] = $address['firstname'] . ' ' . $address['lastname'];
        }
        
        $parts[] = $address['address1'];
        
        if (!empty($address['address2'])) {
            $parts[] = $address['address2'];
        }
        
        $parts[] = $address['city'] . ', ' . $address['postcode'];
        
        if (!empty($address['country_name'])) {
            $parts[] = $address['country_name'];
        }
        
        return implode(', ', $parts);
    }

    /**
     * Create manifest from data
     * 
     * @param array $data
     * @return array Result with success/error
     */
    public function createManifest(array $data): array
    {
        try {
            $validation = $this->validateManifestData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            $availability = $this->checkOrderDetailsAvailability(
                $data['order_detail_ids'], 
                (int)$data['id_vendor']
            );

            if (!empty($availability['unavailable'])) {
                return [
                    'success' => false,
                    'errors' => ['Some order details are already in other manifests']
                ];
            }

            $manifest = new Manifest();
            $manifest->id_vendor = (int)$data['id_vendor'];
            $manifest->id_order_line_status_type = (int)$data['id_order_line_status_type'];
            $manifest->reference = Manifest::generateReference((int)$data['id_vendor']);
            $manifest->status = $data['status'] ?? 'draft';
            $manifest->shipping_address = isset($data['address_data']) ? json_encode($data['address_data']) : '';
            
            if (!$manifest->add()) {
                return [
                    'success' => false,
                    'errors' => ['Failed to create manifest']
                ];
            }

            if (!$manifest->addOrderDetails($data['order_detail_ids'])) {
                $manifest->delete();
                return [
                    'success' => false,
                    'errors' => ['Failed to add order details to manifest']
                ];
            }

            return [
                'success' => true,
                'manifest' => $this->formatManifestForResponse($manifest, true)
            ];

        } catch (Exception $e) {
            PrestaShopLogger::addLog('Manifest creation error: ' . $e->getMessage(), 3);
            return [
                'success' => false,
                'errors' => ['An unexpected error occurred']
            ];
        }
    }

    /**
     * Generate PDF URL for manifest
     * 
     * @param int $manifestId
     * @param Context $context
     * @return string
     */
    public function generatePdfUrl(int $manifestId, Context $context): string
    {
        return $context->link->getModuleLink(
            'multivendor',
            'manifestpdf',
            [
                'id_manifest' => $manifestId,
                'token' => Tools::getToken(false)
            ]
        );
    }

    /**
     * Clean up old draft manifests
     * 
     * @param int $daysOld
     * @return int Number of cleaned manifests
     */
    public function cleanupOldDrafts(int $daysOld = 7): int
    {
        try {
            $sql = 'SELECT id_manifest 
                    FROM ' . _DB_PREFIX_ . 'mv_manifest 
                    WHERE status = "draft" 
                    AND DATE_ADD(date_add, INTERVAL ' . (int)$daysOld . ' DAY) < NOW()';
            
            $oldManifests = Db::getInstance()->executeS($sql);
            
            if (empty($oldManifests)) {
                return 0;
            }

            $manifestIds = array_column($oldManifests, 'id_manifest');
            $placeholders = implode(',', array_fill(0, count($manifestIds), '?'));

            $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'mv_manifest_details 
                    WHERE id_manifest IN (' . $placeholders . ')';
            Db::getInstance()->execute($sql, $manifestIds);

            $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'mv_manifest 
                    WHERE id_manifest IN (' . $placeholders . ')';
            Db::getInstance()->execute($sql, $manifestIds);

            return count($manifestIds);

        } catch (Exception $e) {
            PrestaShopLogger::addLog('Manifest cleanup error: ' . $e->getMessage(), 3);
            return 0;
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}