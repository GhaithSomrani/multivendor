<?php

use PSpell\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

class MultivendorManifestManagerModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;
    public $vendor = [];

    public function init()
    {
        parent::init();

        $id_customer = $this->context->customer->id;
        $this->vendor = VendorHelper::getVendorByCustomer($id_customer);

        if (!$this->vendor) {
            Tools::redirect('index.php?controller=my-account');
        }
    }

    public function initContent()
    {
        parent::initContent();

        // Handle AJAX requests
        if (Tools::isSubmit('ajax')) {
            $this->handleAjaxRequest();
            return;
        }
        $pickupValidationName = $this->getValidationButtonName(Manifest::TYPE_PICKUP);
        $returnsValidationName = $this->getValidationButtonName(Manifest::TYPE_RETURNS);
        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/manifest_front.js');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/manifest_front.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/commissions.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/orders.css');

        // Add daterangepicker library
        $this->context->controller->registerStylesheet('daterangepicker-css', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', ['media' => 'all', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('moment-js', 'https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js', ['position' => 'head', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('daterangepicker-js', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', ['position' => 'bottom', 'priority' => 201, 'server' => 'remote']);

        $VendorObj = new Vendor($this->vendor['id_vendor']);
        $vendorAddresses = $VendorObj->getVendorAddress();
        $vendorFormattedAddresses = [];
        foreach ($vendorAddresses as $address) {
            $vendorAddress = new Address($address['id_address']);
            $vendorFormattedAddresses[] = [
                'id_address' => $address['id_address'],
                'address' => AddressFormat::generateAddress($vendorAddress, [], ' - ', ' ')
            ];
        }
        // Pass vendor data to JavaScript
        Media::addJsDef([
            'manifest_ajax_url' => $this->context->link->getModuleLink('multivendor', 'manifestmanager'),
            'pickup_validation_name' => $pickupValidationName,
            'returns_validation_name' => $returnsValidationName,
            'vendor_id' => $this->vendor['id_vendor']
        ]);
        $this->context->smarty->assign([
            'address_list' => $vendorFormattedAddresses,
            'vendor_id' => $this->vendor['id_vendor'],
            'manifest_ajax_url' => $this->context->link->getModuleLink('multivendor', 'manifestmanager'),
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'pickup_validation_name' => $pickupValidationName,
            'returns_validation_name' => $returnsValidationName,
     
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/manifest.tpl');
    }

    private function handleAjaxRequest()
    {
        $action = Tools::getValue('action');

        switch ($action) {
            case 'getAvailableOrders':
                $this->getAvailableOrders();
                break;
            case 'getManifestOrders':
                $this->getManifestOrders();
                break;
            case 'getManifestList':
                $this->getManifestList();
                break;
            case 'createManifest':
                $this->createManifest();
                break;
            case 'updateManifest':
                $this->updateManifest();
                break;
            case 'deleteManifest':
                $this->deleteManifest();
                break;
            case 'loadManifest':
                $this->loadManifest();
                break;
            case 'printManifest':
                $this->printManifest();
                break;
            case 'createAndPrintManifest':
                $this->createAndPrintManifest();
                break;
            case 'saveManifestOnly':
                $this->saveManifestOnly();
                break;
        }
        exit;
    }

    private function getValidationButtonName($manifestType)
    {
        if ($manifestType == Manifest::TYPE_PICKUP) {
            $statusId = Configuration::get('mv_pickup');
        } else {
            $statusId = Configuration::get('mv_returns');
        }

        if ($statusId) {
            $status = new ManifestStatusType($statusId);
            return $status->name;
        }

        return 'Valider';
    }
    private function printManifest()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');

        $manifest = new Manifest($id_manifest);
        if ($manifest->id_vendor != $this->vendor['id_vendor']) {
            echo 'Access denied';
            exit;
        }

        try {
            Manifest::generatePrintablePDF($id_manifest);
            exit;
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
            exit;
        }
    }

    private function createAndPrintManifest()
    {
        $orderDetails = Tools::getValue('order_details', []);
        $id_address = (int)Tools::getValue('id_address');

        try {
            $manifest = Manifest::addNewManifest(
                $orderDetails,
                $this->vendor['id_vendor'],
                Manifest::TYPE_PICKUP,
                $id_address,
                1
            );
            $this->ajaxResponse([
                'success' => true,
                'manifest_id' => $manifest->id,
            ]);
        } catch (Exception $e) {
            $this->ajaxResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function getAvailableOrders()
    {

        $id_manifest = (int)Tools::getValue('id_manifest');
        $allowedStatusIds = ManifestStatusType::getAllowedOrderLineStatusTypes(1);
        $filters = [
            'available_for_manifest' => $allowedStatusIds,
            'id_manifest_exclude' => $id_manifest
        ];

        $orders = OrderHelper::getVendorOrderDetails($this->vendor['id_vendor'], $filters);

        $result = [];
        foreach ($orders as $order) {
            $checkboxState = Manifest::getOrderDetailCheckboxState($order['id_order_detail'], $id_manifest);

            $result[] = [
                'id_order' => $order['id_order'],
                'id' => $order['id_order_detail'],
                'name' => $order['product_name'],
                'reference' => $order['product_reference'],
                'mpn' => $order['product_mpn'],
                'quantity' => $order['product_quantity'],
                'status' => $order['line_status'],
                'status_color' => $order['status_color'],
                'public_price' => $order['unit_price'],
                'price' => $order['product_price'],
                'total' => $order['vendor_amount'],
                'add_date' => $order['order_date'],
                'disabled' => $checkboxState['disabled'],
                'checked' => $checkboxState['checked']
            ];
        }

        $this->ajaxResponse(['orders' => $result]);
    }

    private function getManifestOrders()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');
        if (!$id_manifest) {
            $this->ajaxResponse(['orders' => []]);
            return;
        }

        $manifest = new Manifest($id_manifest);
        if ($manifest->id_vendor != $this->vendor['id_vendor']) {
            $this->ajaxResponse(['error' => 'Access denied'], 403);
            return;
        }

        $filters = ['manifest' => $id_manifest];
        $orders = OrderHelper::getVendorOrderDetails($this->vendor['id_vendor'], $filters);

        $result = [];
        $total = 0;
        foreach ($orders as $order) {
            $result[] = [
                'id_order' => $order['id_order'],
                'id' => $order['id_order_detail'],
                'name' => $order['product_name'],
                'reference' => $order['product_reference'],
                'mpn' => $order['product_mpn'],
                'quantity' => $order['product_quantity'],
                'status' => $order['order_state_name'],
                'public_price' => $order['unit_price'],
                'price' => $order['product_price'],
                'total' => $order['vendor_amount'],
            ];
            $total += (float)$order['vendor_amount'];
        }

        $this->ajaxResponse(['orders' => $result, 'total' => $total]);
    }

    private function getManifestList()
    {
        // Get filters
        $filters = [
            'reference' => Tools::getValue('reference'),
            'type' => Tools::getValue('type'),
            'date_range' => Tools::getValue('date'),
            'address' => Tools::getValue('address'),
            'items_min' => Tools::getValue('items_min'),
            'items_max' => Tools::getValue('items_max'),
            'qty_min' => Tools::getValue('qty_min'),
            'qty_max' => Tools::getValue('qty_max'),
            'total_min' => Tools::getValue('total_min'),
            'total_max' => Tools::getValue('total_max'),
            'status' => Tools::getValue('status'),
        ];

        // Parse date range if provided
        $dateFrom = null;
        $dateTo = null;
        if (!empty($filters['date_range']) && strpos($filters['date_range'], ' - ') !== false) {
            list($dateFromStr, $dateToStr) = explode(' - ', $filters['date_range']);
            $dateFrom = DateTime::createFromFormat('d/m/Y', trim($dateFromStr));
            $dateTo = DateTime::createFromFormat('d/m/Y', trim($dateToStr));
        }

        // Remove empty filters
        $filters = array_filter($filters, function ($value) {
            return $value !== '' && $value !== null && $value !== false;
        });

        // Pagination
        $page = (int)Tools::getValue('page', 1);
        $per_page = 10;
        $offset = ($page - 1) * $per_page;

        // Get all manifests first
        $allManifests = $this->getVendorManifests($filters);
        $result = [];

        foreach ($allManifests as $manifest) {
            if ($manifest['id_manifest_status'] != Configuration::get('MANIFEST_RETURN_DRAFT')) {
                $address = Manifest::getManifestAddress($manifest['id_manifest']);
                $manifestTypeObj = new ManifestType($manifest['id_manifest_type']);
                $orderdetails = OrderHelper::getVendorOrderDetails($this->vendor['id_vendor'], ['manifest' => $manifest['id_manifest']]);
                $total = array_sum(array_column($orderdetails, 'vendor_amount'));

                $result[] = [
                    'id' => $manifest['id_manifest'],
                    'reference' => $manifest['reference'],
                    'address' =>  substr($address, 0, 25) . (strlen($address) > 25 ? '...' : ''),
                    'full_address' => $address,
                    'date' => date('d/m/Y', strtotime($manifest['date_add'])),
                    'type' => $manifest['id_manifest_type'],
                    'type_name' => $manifestTypeObj->name,
                    'nbre' => $manifest['item_count'],
                    'qty' => $manifest['total_quantity'],
                    'total' =>  $total,
                    'status' => $manifest['status_name'],
                    'editable' => $manifest['allowed_modification'],
                    'deletable' => $manifest['allowed_delete'],
                    'orderdetails' => $orderdetails,
                ];
            }
        }

        // Apply filters
        if (!empty($filters) || $dateFrom || $dateTo) {
            $result = array_filter($result, function ($manifest) use ($filters, $dateFrom, $dateTo) {
                // Reference filter
                if (isset($filters['reference']) && stripos($manifest['reference'], $filters['reference']) === false) {
                    return false;
                }

                // Type filter
                if (isset($filters['type']) && $manifest['type'] != $filters['type']) {
                    return false;
                }

                // Date range filter
                if ($dateFrom && $dateTo) {
                    $manifestDate = DateTime::createFromFormat('d/m/Y', $manifest['date']);
                    if (!$manifestDate) {
                        return false;
                    }
                    // Set time to start of day for comparison
                    $manifestDate->setTime(0, 0, 0);
                    $dateFrom->setTime(0, 0, 0);
                    $dateTo->setTime(23, 59, 59);

                    if ($manifestDate < $dateFrom || $manifestDate > $dateTo) {
                        return false;
                    }
                }

                // Address filter
                if (isset($filters['address']) && stripos($manifest['full_address'], $filters['address']) === false) {
                    return false;
                }

                // Items min filter
                if (isset($filters['items_min']) && $manifest['nbre'] < $filters['items_min']) {
                    return false;
                }

                // Items max filter
                if (isset($filters['items_max']) && $manifest['nbre'] > $filters['items_max']) {
                    return false;
                }

                // Qty min filter
                if (isset($filters['qty_min']) && $manifest['qty'] < $filters['qty_min']) {
                    return false;
                }

                // Qty max filter
                if (isset($filters['qty_max']) && $manifest['qty'] > $filters['qty_max']) {
                    return false;
                }

                // Total min filter
                if (isset($filters['total_min']) && $manifest['total'] < $filters['total_min']) {
                    return false;
                }

                // Total max filter
                if (isset($filters['total_max']) && $manifest['total'] > $filters['total_max']) {
                    return false;
                }

                // Status filter (exact match for dropdown)
                if (isset($filters['status']) && $filters['status'] !== '' && $manifest['status'] !== $filters['status']) {
                    return false;
                }

                return true;
            });
        }

        // Calculate pagination
        $total_count = count($result);
        $total_pages = ceil($total_count / $per_page);

        // Apply pagination
        $result = array_slice($result, $offset, $per_page);

        $this->ajaxResponse([
            'manifests' => array_values($result),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_count' => $total_count,
                'per_page' => $per_page
            ]
        ]);
    }

    private function createManifest()
    {
        $orderDetails = Tools::getValue('order_details', []);
        $id_address = (int)Tools::getValue('id_address');
        $validate = (bool)Tools::getValue('validate');
        $id_manifest_status = $validate ? Configuration::get('mv_pickup') : 1;
        if (empty($orderDetails)) {
            $this->ajaxResponse(['error' => 'No order details selected'], 400);
            return;
        }

        // Validate address belongs to vendor
        if (!$this->validateVendorAddress($id_address)) {
            $this->ajaxResponse(['error' => 'Invalid address'], 400);
            return;
        }

        try {
            $manifest = Manifest::addNewManifest(
                $orderDetails,
                $this->vendor['id_vendor'],
                Manifest::TYPE_PICKUP,
                $id_address,
                $id_manifest_status
            );
            $this->ajaxResponse([
                'success' => true,
                'id_manifest' => $manifest,
                'message' => 'Manifest created successfully'
            ]);
        } catch (Exception $e) {
            $this->ajaxResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function updateManifest()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');
        $orderDetails = Tools::getValue('order_details', []);
        $id_manifest_type = (int)Tools::getValue('id_manifest_type');
        $manifest = new Manifest($id_manifest);
        $old_ids = ManifestDetails::getOrderDetailsByManifest($id_manifest);
        $new_ids = $orderDetails;
        $ids_to_add = array_diff($new_ids, $old_ids);
        $ids_to_remove = array_diff($old_ids, $new_ids);
        if ($manifest->id_vendor != $this->vendor['id_vendor']) {
            $this->ajaxResponse(['error' => 'Access denied'], 403);
            return;
        }


        if (!Manifest::IsEditable($id_manifest)) {
            $this->ajaxResponse(['error' => 'Manifest cannot be modified'], 400);
            return;
        }

        if ($manifest->id_manifest_type != $id_manifest_type) {
            $this->ajaxResponse(['error' => 'Manifest type cannot be changed'], 400);
            return;
        }

        try {
            if ($manifest->id_manifest_type == Manifest::TYPE_PICKUP) {
                $manifest->id_manifest_status = Configuration::get('mv_pickup');
                $manifest->save();
            } elseif ($manifest->id_manifest_type == Manifest::TYPE_RETURNS) {
                $manifest->id_manifest_status = configuration::get('mv_returns');
                $manifest->save();
            }

            foreach ($ids_to_remove as $id_order_detail) {
                $manifest->removeOrderDetail($id_order_detail);
            }

            foreach ($ids_to_add as $id_order_detail) {
                $manifest->addOrderDetail($id_order_detail);
            }

            $this->ajaxResponse([
                'success' => true,
                'message' => 'Manifest updated successfully'
            ]);
        } catch (Exception $e) {
            $this->ajaxResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function deleteManifest()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');

        $manifest = new Manifest($id_manifest);
        if ($manifest->id_vendor != $this->vendor['id_vendor']) {
            $this->ajaxResponse(['error' => 'Access denied'], 403);
            return;
        }

        if (!Manifest::IsEditable($id_manifest)) {
            $this->ajaxResponse(['error' => 'Manifest cannot be deleted'], 400);
            return;
        }

        try {
            $manifest->clearOrderDetails();
            $manifest->delete();

            $this->ajaxResponse([
                'success' => true,
                'message' => 'Manifest deleted successfully'
            ]);
        } catch (Exception $e) {
            $this->ajaxResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function loadManifest()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');

        $manifest = new Manifest($id_manifest);
        if ($manifest->id_vendor != $this->vendor['id_vendor']) {
            $this->ajaxResponse(['error' => 'Access denied'], 403);
            return;
        }

        $this->ajaxResponse([
            'success' => true,
            'manifest' => [
                'id' => $manifest->id,
                'reference' => $manifest->reference,
                'id_address' => $manifest->id_address,
                'status' => $manifest->id_manifest_status,
                'editable' => Manifest::IsEditable($id_manifest)
            ]
        ]);
    }

    private function saveManifestOnly()
    {
        $id_manifest = (int)Tools::getValue('id_manifest');
        $orderDetails = Tools::getValue('order_details', []);
        $id_address = (int)Tools::getValue('id_address');

        if (empty($orderDetails)) {
            $this->ajaxResponse(['error' => 'No order details selected'], 400);
            return;
        }

        // Create or update manifest without changing status
        try {
            if ($id_manifest && $id_manifest > 0) {
                // Update existing manifest
                $manifest = new Manifest($id_manifest);
                if ($manifest->id_vendor != $this->vendor['id_vendor']) {
                    $this->ajaxResponse(['error' => 'Access denied'], 403);
                    return;
                }

                if (!Manifest::IsEditable($id_manifest)) {
                    $this->ajaxResponse(['error' => 'Manifest cannot be modified'], 400);
                    return;
                }
                $manifest = new Manifest($id_manifest);
                $old_ids = ManifestDetails::getOrderDetailsByManifest($id_manifest);
                $new_ids = $orderDetails;
                $ids_to_add = array_diff($new_ids, $old_ids);
                $ids_to_remove = array_diff($old_ids, $new_ids);
                foreach ($ids_to_remove as $id_order_detail) {
                    $manifest->removeOrderDetail($id_order_detail);
                }
                foreach ($ids_to_add as $id_order_detail) {
                    $manifest->addOrderDetail($id_order_detail);
                }
            } else {
                $manifest = Manifest::addNewManifest(
                    $orderDetails,
                    $this->vendor['id_vendor'],
                    Manifest::TYPE_PICKUP,
                    $id_address,
                    null
                );
            }

            $this->ajaxResponse([
                'success' => true,
                'message' => 'Manifest saved successfully'
            ]);
        } catch (Exception $e) {
            $this->ajaxResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getVendorManifests()
    {
        $sql = 'SELECT m.*, a.alias as address_alias, mst.name as status_name, 
                       mst.allowed_modification,
                       mst.allowed_delete,
                       COUNT(md.id_order_details) as item_count,
                       SUM(od.product_quantity) as total_quantity,
                       SUM(od.total_price_tax_incl) as total_amount
                FROM `' . _DB_PREFIX_ . 'mv_manifest` m
                LEFT JOIN `' . _DB_PREFIX_ . 'address` a ON (m.id_address = a.id_address)
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_status_type` mst ON (m.id_manifest_status = mst.id_manifest_status_type)
                LEFT JOIN `' . _DB_PREFIX_ . 'mv_manifest_details` md ON (m.id_manifest = md.id_manifest)
                LEFT JOIN `' . _DB_PREFIX_ . 'order_detail` od ON (md.id_order_details = od.id_order_detail)
                WHERE m.id_vendor = ' . (int)$this->vendor['id_vendor'] . '
                GROUP BY m.id_manifest
                ORDER BY m.date_add DESC';

        return Db::getInstance()->executeS($sql);
    }


    private function validateVendorAddress($id_address)
    {
        $vendor = new Vendor($this->vendor['id_vendor']);
        $addresses = $vendor->getVendorAddress();

        foreach ($addresses as $address) {
            if ($address['id_address'] == $id_address) {
                return true;
            }
        }
        return false;
    }

    private function ajaxResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function setMedia()
    {
        parent::setMedia();
    }
}
