<?php

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

        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/manifest_front.js');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/manifest_front.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/orders.css');

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
            'vendor_id' => $this->vendor['id_vendor']
        ]);
        $this->context->smarty->assign([
            'address_list' => $vendorFormattedAddresses,
            'vendor_id' => $this->vendor['id_vendor'],
            'manifest_ajax_url' => $this->context->link->getModuleLink('multivendor', 'manifestmanager'),
            'vendor_dashboard_url' => $this->context->link->getModuleLink('multivendor', 'dashboard'),
            'vendor_commissions_url' => $this->context->link->getModuleLink('multivendor', 'commissions'),
            'vendor_profile_url' => $this->context->link->getModuleLink('multivendor', 'profile'),
            'vendor_orders_url' => $this->context->link->getModuleLink('multivendor', 'orders'),
            'vendor_manage_orders_url' => $this->context->link->getModuleLink('multivendor', 'manageorders', []),
            'vendor_manifest_url' => $this->context->link->getModuleLink('multivendor', 'manifestmanager', []),
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
        }
        exit;
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
                'status' => $order['order_state_name'],
                'public_price' => $order['unit_price'],
                'price' => $order['product_price'],
                'total' => $order['vendor_amount'],
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
        $manifests = $this->getVendorManifests();

        $result = [];
        foreach ($manifests as $manifest) {
            $result[] = [
                'id' => $manifest['id_manifest'],
                'reference' => '#' . str_pad($manifest['id_manifest'], 6, '0', STR_PAD_LEFT),
                'address' => $manifest['address_alias'] ?: 'N/A',
                'date' => date('d/m/Y', strtotime($manifest['date_add'])),
                'nbre' => $manifest['item_count'],
                'qty' => $manifest['total_quantity'],
                'total' => $manifest['total_amount'],
                'status' => $manifest['status_name'],
                'editable' => $manifest['allowed_modification']
            ];
        }

        $this->ajaxResponse(['manifests' => $result]);
    }

    private function createManifest()
    {
        $orderDetails = Tools::getValue('order_details', []);
        $id_address = (int)Tools::getValue('id_address');

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
                1
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

        $manifest = new Manifest($id_manifest);
        if ($manifest->id_vendor != $this->vendor['id_vendor']) {
            $this->ajaxResponse(['error' => 'Access denied'], 403);
            return;
        }

        if (!Manifest::IsEditable($id_manifest)) {
            $this->ajaxResponse(['error' => 'Manifest cannot be modified'], 400);
            return;
        }

        try {
            // Clear existing order details
            $manifest->clearOrderDetails();

            // Add new order details
            foreach ($orderDetails as $id_order_detail) {
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

    private function getVendorManifests()
    {
        $sql = 'SELECT m.*, a.alias as address_alias, mst.name as status_name, 
                       mst.allowed_modification,
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
