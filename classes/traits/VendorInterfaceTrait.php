<?php
/**
 * Trait VendorInterfaceTrait
 * 
 * Provides shared functionality for vendor-related interfaces
 */
trait VendorInterfaceTrait
{
    /** @var int Current vendor ID */
    protected int $vendorId = 0;

    /** @var array Vendor information cache */
    protected array $vendorCache = [];

    /**
     * Initialize vendor context
     * 
     * @return bool
     */
    protected function initVendorContext(): bool
    {
        if (isset($this->context->cookie->vendor_id)) {
            $this->vendorId = (int)$this->context->cookie->vendor_id;
        } else {
            $this->vendorId = $this->getVendorIdFromCustomer();
        }

        return $this->vendorId > 0;
    }

    /**
     * Get vendor ID from current customer
     * 
     * @return int
     */
    protected function getVendorIdFromCustomer(): int
    {
        if (!$this->context->customer->isLogged()) {
            return 0;
        }

        $sql = 'SELECT id_vendor 
                FROM ' . _DB_PREFIX_ . 'mv_vendor 
                WHERE id_customer = ' . (int)$this->context->customer->id . ' 
                AND status = 1';
        
        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * Get current vendor information
     * 
     * @return array|null
     */
    protected function getCurrentVendor(): ?array
    {
        if (!$this->vendorId) {
            return null;
        }

        if (!isset($this->vendorCache[$this->vendorId])) {
            $sql = 'SELECT v.*, c.firstname, c.lastname, c.email
                    FROM ' . _DB_PREFIX_ . 'mv_vendor v
                    LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON v.id_customer = c.id_customer
                    WHERE v.id_vendor = ' . (int)$this->vendorId;
            
            $vendor = Db::getInstance()->getRow($sql);
            $this->vendorCache[$this->vendorId] = $vendor ?: null;
        }

        return $this->vendorCache[$this->vendorId];
    }

    /**
     * Check vendor permissions
     * 
     * @param string $permission
     * @return bool
     */
    protected function hasVendorPermission(string $permission): bool
    {
        $vendor = $this->getCurrentVendor();
        
        if (!$vendor || $vendor['status'] !== 1) {
            return false;
        }

        $permissions = [
            'manage_orders' => true,
            'create_manifest' => true,
            'view_manifest' => true,
            'edit_manifest' => true,
            'print_manifest' => true,
            'manage_products' => true,
            'view_analytics' => true
        ];

        return $permissions[$permission] ?? false;
    }

    /**
     * Format currency for display
     * 
     * @param float $amount
     * @param int|null $currencyId
     * @return string
     */
    protected function formatPrice(float $amount, ?int $currencyId = null): string
    {
        if (!$currencyId) {
            $currencyId = $this->context->currency->id;
        }

        return Tools::displayPrice($amount, $currencyId);
    }

    /**
     * Format date for vendor interface
     * 
     * @param string $date
     * @param bool $includeTime
     * @return string
     */
    protected function formatDate(string $date, bool $includeTime = false): string
    {
        $timestamp = strtotime($date);
        
        if ($includeTime) {
            return date('d/m/Y H:i', $timestamp);
        }
        
        return date('d/m/Y', $timestamp);
    }

    /**
     * Get vendor order line statuses
     * 
     * @param bool $vendorAllowed
     * @return array
     */
    protected function getOrderLineStatuses(bool $vendorAllowed = true): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'mv_order_line_status_type 
                WHERE active = 1';
        
        if ($vendorAllowed) {
            $sql .= ' AND is_vendor_allowed = 1';
        }
        
        $sql .= ' ORDER BY position';

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Check if vendor owns order detail
     * 
     * @param int $orderDetailId
     * @return bool
     */
    protected function vendorOwnsOrderDetail(int $orderDetailId): bool
    {
        if (!$this->vendorId) {
            return false;
        }

        $sql = 'SELECT COUNT(*) 
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail 
                WHERE id_order_detail = ' . (int)$orderDetailId . ' 
                AND id_vendor = ' . (int)$this->vendorId;

        return (bool)Db::getInstance()->getValue($sql);
    }

    /**
     * Get vendor commission rate
     * 
     * @return float
     */
    protected function getVendorCommissionRate(): float
    {
        if (!$this->vendorId) {
            return 0.0;
        }

        $sql = 'SELECT commission_rate 
                FROM ' . _DB_PREFIX_ . 'mv_vendor_commission 
                WHERE id_vendor = ' . (int)$this->vendorId;
        
        $rate = Db::getInstance()->getValue($sql);
        
        return $rate ? (float)$rate : (float)Configuration::get('MV_DEFAULT_COMMISSION', 10.0);
    }

    /**
     * Add vendor notification
     * 
     * @param string $message
     * @param string $type
     * @param string|null $link
     * @return bool
     */
    protected function addVendorNotification(string $message, string $type = 'info', ?string $link = null): bool
    {
        PrestaShopLogger::addLog(
            'Vendor Notification [' . $this->vendorId . ']: ' . $message, 
            1, 
            null, 
            'Vendor', 
            $this->vendorId
        );

        return true;
    }

    /**
     * Get vendor statistics
     * 
     * @return array
     */
    protected function getVendorStatistics(): array
    {
        if (!$this->vendorId) {
            return [];
        }

        $orderStats = $this->getVendorOrderStatistics();
        $productStats = $this->getVendorProductStatistics();
        $revenueStats = $this->getVendorRevenueStatistics();

        return [
            'orders' => $orderStats,
            'products' => $productStats,
            'revenue' => $revenueStats
        ];
    }

    /**
     * Get vendor order statistics
     * 
     * @return array
     */
    private function getVendorOrderStatistics(): array
    {
        $sql = 'SELECT 
                    COUNT(DISTINCT vod.id_order) as total_orders,
                    COUNT(vod.id_order_detail) as total_items,
                    SUM(vod.product_quantity) as total_quantity
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
                WHERE vod.id_vendor = ' . (int)$this->vendorId;

        $result = Db::getInstance()->getRow($sql);
        
        return [
            'total_orders' => (int)($result['total_orders'] ?? 0),
            'total_items' => (int)($result['total_items'] ?? 0),
            'total_quantity' => (int)($result['total_quantity'] ?? 0)
        ];
    }

    /**
     * Get vendor product statistics
     * 
     * @return array
     */
    private function getVendorProductStatistics(): array
    {
        $sql = 'SELECT COUNT(*) as total_products
                FROM ' . _DB_PREFIX_ . 'product p
                WHERE p.id_supplier = (
                    SELECT id_supplier 
                    FROM ' . _DB_PREFIX_ . 'mv_vendor 
                    WHERE id_vendor = ' . (int)$this->vendorId . '
                )';

        $result = Db::getInstance()->getValue($sql);
        
        return [
            'total_products' => (int)($result ?? 0)
        ];
    }

    /**
     * Get vendor revenue statistics
     * 
     * @return array
     */
    private function getVendorRevenueStatistics(): array
    {
        $sql = 'SELECT 
                    SUM(vod.vendor_amount) as total_revenue,
                    SUM(vod.commission_amount) as total_commission,
                    AVG(vod.vendor_amount) as average_order_value
                FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
                WHERE vod.id_vendor = ' . (int)$this->vendorId;

        $result = Db::getInstance()->getRow($sql);
        
        return [
            'total_revenue' => (float)($result['total_revenue'] ?? 0),
            'total_commission' => (float)($result['total_commission'] ?? 0),
            'average_order_value' => (float)($result['average_order_value'] ?? 0)
        ];
    }

    /**
     * Validate vendor access to resource
     * 
     * @param string $resource
     * @param int $resourceId
     * @return bool
     */
    protected function validateVendorAccess(string $resource, int $resourceId): bool
    {
        if (!$this->vendorId) {
            return false;
        }

        switch ($resource) {
            case 'manifest':
                $sql = 'SELECT COUNT(*) 
                        FROM ' . _DB_PREFIX_ . 'mv_manifest 
                        WHERE id_manifest = ' . (int)$resourceId . ' 
                        AND id_vendor = ' . (int)$this->vendorId;
                break;
                
            case 'order_detail':
                $sql = 'SELECT COUNT(*) 
                        FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail 
                        WHERE id_order_detail = ' . (int)$resourceId . ' 
                        AND id_vendor = ' . (int)$this->vendorId;
                break;
                
            case 'product':
                $sql = 'SELECT COUNT(*) 
                        FROM ' . _DB_PREFIX_ . 'product p
                        INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor v ON p.id_supplier = v.id_supplier
                        WHERE p.id_product = ' . (int)$resourceId . ' 
                        AND v.id_vendor = ' . (int)$this->vendorId;
                break;
                
            default:
                return false;
        }

        return (bool)Db::getInstance()->getValue($sql);
    }

    /**
     * Get vendor addresses for selection
     * 
     * @return array
     */
    protected function getVendorAddresses(): array
    {
        if (!$this->vendorId) {
            return [];
        }

        return Manifest::getVendorAddresses($this->vendorId);
    }

    /**
     * Get vendor manifest statistics
     * 
     * @return array
     */
    protected function getVendorManifestStatistics(): array
    {
        if (!$this->vendorId) {
            return [];
        }

        $helper = ManifestHelper::getInstance();
        return $helper->getManifestStatistics($this->vendorId);
    }

    /**
     * Check if vendor can create manifest
     * 
     * @return bool
     */
    protected function canCreateManifest(): bool
    {
        return $this->hasVendorPermission('create_manifest') && $this->vendorId > 0;
    }

    /**
     * Get available order details for vendor
     * 
     * @param int $statusTypeId
     * @return array
     */
    protected function getAvailableOrderDetails(int $statusTypeId): array
    {
        if (!$this->vendorId) {
            return [];
        }

        return Manifest::getAvailableOrderDetails($this->vendorId, $statusTypeId);
    }

    /**
     * Create vendor manifest
     * 
     * @param array $data
     * @return array
     */
    protected function createVendorManifest(array $data): array
    {
        if (!$this->canCreateManifest()) {
            return [
                'success' => false,
                'errors' => ['No permission to create manifests']
            ];
        }

        $data['id_vendor'] = $this->vendorId;
        
        $helper = ManifestHelper::getInstance();
        return $helper->createManifest($data);
    }

    /**
     * Get vendor manifests
     * 
     * @param int|null $limit
     * @param int $offset
     * @return array
     */
    protected function getVendorManifests(?int $limit = null, int $offset = 0): array
    {
        if (!$this->vendorId) {
            return [];
        }

        return Manifest::getManifestsByVendor($this->vendorId, $limit, $offset);
    }

    /**
     * Load vendor manifest
     * 
     * @param int $manifestId
     * @return array|null
     */
    protected function loadVendorManifest(int $manifestId): ?array
    {
        if (!$this->validateVendorAccess('manifest', $manifestId)) {
            return null;
        }

        $manifest = new Manifest($manifestId);
        if (!Validate::isLoadedObject($manifest)) {
            return null;
        }

        $helper = ManifestHelper::getInstance();
        return $helper->formatManifestForResponse($manifest, true);
    }

    /**
     * Generate manifest PDF URL
     * 
     * @param int $manifestId
     * @return string|null
     */
    protected function getManifestPdfUrl(int $manifestId): ?string
    {
        if (!$this->validateVendorAccess('manifest', $manifestId)) {
            return null;
        }

        $helper = ManifestHelper::getInstance();
        return $helper->generatePdfUrl($manifestId, $this->context);
    }

    /**
     * Get vendor order line status types
     * 
     * @return array
     */
    protected function getVendorOrderLineStatusTypes(): array
    {
        return $this->getOrderLineStatuses(true);
    }

    /**
     * Log vendor activity
     * 
     * @param string $action
     * @param string $details
     * @param int|null $resourceId
     * @return bool
     */
    protected function logVendorActivity(string $action, string $details = '', ?int $resourceId = null): bool
    {
        $message = "Vendor {$this->vendorId} - {$action}";
        if ($details) {
            $message .= ": {$details}";
        }

        return PrestaShopLogger::addLog(
            $message,
            1,
            null,
            'VendorActivity',
            $resourceId ?? $this->vendorId
        );
    }

    /**
     * Get localized text
     * 
     * @param string $text
     * @param string $module
     * @return string
     */
    protected function l(string $text, string $module = 'multivendor'): string
    {
        if (method_exists($this, 'getTranslator')) {
            return $this->getTranslator()->trans($text, [], 'Modules.' . ucfirst($module) . '.Admin');
        }
        
        if (isset($this->module) && method_exists($this->module, 'l')) {
            return $this->module->l($text);
        }
        
        return $text;
    }

    /**
     * Validate AJAX request
     * 
     * @return bool
     */
    protected function validateAjaxRequest(): bool
    {
        if (!Tools::isSubmit('ajax') || !Tools::getValue('ajax')) {
            return false;
        }

        if (!$this->vendorId) {
            return false;
        }

        return true;
    }

    /**
     * Send AJAX response
     * 
     * @param array $data
     * @return void
     */
    protected function ajaxResponse(array $data): void
    {
        header('Content-Type: application/json');
        die(json_encode($data));
    }

    /**
     * Send AJAX error response
     * 
     * @param string $message
     * @param int $code
     * @return void
     */
    protected function ajaxError(string $message, int $code = 400): void
    {
        http_response_code($code);
        $this->ajaxResponse([
            'success' => false,
            'error' => $message
        ]);
    }

    /**
     * Send AJAX success response
     * 
     * @param array $data
     * @param string|null $message
     * @return void
     */
    protected function ajaxSuccess(array $data = [], ?string $message = null): void
    {
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        $this->ajaxResponse($response);
    }

    /**
     * Get current page URL for vendor
     * 
     * @param array $params
     * @return string
     */
    protected function getCurrentUrl(array $params = []): string
    {
        $controller = $this->context->controller->php_self ?? 'index';
        
        $defaultParams = [
            'controller' => $controller,
            'token' => Tools::getAdminTokenLite($controller)
        ];
        
        $allParams = array_merge($defaultParams, $params);
        
        return $this->context->link->getAdminLink($controller, true, [], $allParams);
    }

    /**
     * Check if vendor has active status
     * 
     * @return bool
     */
    protected function isVendorActive(): bool
    {
        $vendor = $this->getCurrentVendor();
        return $vendor && (int)$vendor['status'] === 1;
    }

    /**
     * Get vendor shop information
     * 
     * @return array
     */
    protected function getVendorShopInfo(): array
    {
        $vendor = $this->getCurrentVendor();
        
        if (!$vendor) {
            return [];
        }

        return [
            'id_vendor' => (int)$vendor['id_vendor'],
            'shop_name' => $vendor['shop_name'] ?? '',
            'description' => $vendor['description'] ?? '',
            'logo' => $vendor['logo'] ?? '',
            'banner' => $vendor['banner'] ?? '',
            'status' => (int)$vendor['status'],
            'contact_name' => trim(($vendor['firstname'] ?? '') . ' ' . ($vendor['lastname'] ?? '')),
            'email' => $vendor['email'] ?? ''
        ];
    }
}