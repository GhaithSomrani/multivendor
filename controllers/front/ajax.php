<?php

/**
 * AJAX controller for multivendor module - COMPLETE FIXED VERSION
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Load VendorHelper class
require_once(_PS_MODULE_DIR_ . 'multivendor/classes/VendorHelper.php');

class MultivendorAjaxModuleFrontController extends ModuleFrontController
{
    // Disable the standard page rendering
    public $ssl = true;
    public $display_header = false;
    public $display_footer = false;
    public $display_column_left = false;
    public $display_column_right = false;

    public function initContent()
    {
        parent::initContent();

        $action = Tools::getValue('action');

        // Log the action for debugging
        error_log('MultivendorAjax: Action received: ' . $action);
        error_log('MultivendorAjax: All parameters: ' . print_r($_POST, true));

        switch ($action) {


            case 'updateVendorStatus':
                $this->processUpdateVendorStatus();
                break;

            case 'getStatusHistory':
                $this->processGetStatusHistory();
                break;

            case 'bulkUpdateVendorStatus':
                $this->processBulkUpdateVendorStatus();
                break;

            case 'exportOrdersCSV':
                $this->processExportOrdersCSV();
                break;

            case 'getAddCommissionStatus':
                $this->processGetAddCommissionStatus();
                break;

            case 'getAllManifestItems':
                $this->processGetAllManifestItems();
                break;

            case 'getVendorProducts':
                $this->processGetVendorProducts();
                break;

            case 'GetOrderDetail':
                $this->processGetOrderDetail();
                break;

            case 'searchOutOfStockProducts':
                $this->processSearchOutOfStockProducts();
                break;

            case 'getProductVariants':
                $this->processGetProductVariants();
                break;

            case 'addOutOfStockSuggestion':
                $this->processAddOutOfStockSuggestion();
                break;

            case 'getseletecdVariants':
                $this->processGetSeletecdVariants();
                break;

            case 'getGefilterDetails':
                $this->processGefilterDetails();
                break;
            default:
                error_log('MultivendorAjax: Unknown action: ' . $action);
                https: //claude.ai/recents
                die(json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]));
        }
    }



    /**
     * Process vendor status update - FIXED VERSION
     */
    private function processUpdateVendorStatus()
    {
        try {
            error_log('MultivendorAjax: processUpdateVendorStatus called');

            $id_order_detail = (int)Tools::getValue('id_order_detail');
            $id_status_type = (int)Tools::getValue('id_status_type');
            $comment = Tools::getValue('comment', '');
            $id_customer = $this->context->customer->id;

            error_log('MultivendorAjax: Vendor status update parameters: ' . print_r([
                'id_order_detail' => $id_order_detail,
                'id_status_type' => $id_status_type,
                'comment' => $comment,
                'id_customer' => $id_customer
            ], true));

            // Validate inputs
            if (!$id_order_detail || !$id_status_type) {
                die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
            }

            $result = VendorHelper::updateVendorOrderLineStatus($id_customer, $id_order_detail, $id_status_type, $comment);

            error_log('MultivendorAjax: Vendor status update result: ' . print_r($result, true));

            die(json_encode($result));
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processUpdateVendorStatus: ' . $e->getMessage());
            error_log('MultivendorAjax: Stack trace: ' . $e->getTraceAsString());
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }


    /**
     * Process get status history
     */
    private function processGetStatusHistory()
    {
        try {
            $id_order_detail = (int)Tools::getValue('id_order_detail');
            $id_customer = $this->context->customer->id;

            if (!$id_order_detail) {
                die(json_encode(['success' => false, 'message' => 'Missing order detail ID']));
            }

            $result = VendorHelper::getOrderLineStatusHistory($id_customer, $id_order_detail);
            die(json_encode($result));
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processGetStatusHistory: ' . $e->getMessage());
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    /**
     * Process bulk update vendor status
     */
    private function processBulkUpdateVendorStatus()
    {
        try {
            $order_detail_ids = Tools::getValue('order_detail_ids', []);
            $id_status_type = (int)Tools::getValue('id_status_type');
            $comment = Tools::getValue('comment', 'Bulk status update');
            $id_customer = $this->context->customer->id;

            error_log('MultivendorAjax: Bulk update parameters: ' . print_r([
                'order_detail_ids' => $order_detail_ids,
                'id_status_type' => $id_status_type,
                'comment' => $comment,
                'id_customer' => $id_customer
            ], true));

            if (empty($order_detail_ids) || !$id_status_type) {
                die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
            }

            $result = VendorHelper::bulkUpdateVendorOrderLineStatus($id_customer, $order_detail_ids, $id_status_type, $comment);

            error_log('MultivendorAjax: Bulk update result: ' . print_r($result, true));

            die(json_encode($result));
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processBulkUpdateVendorStatus: ' . $e->getMessage());
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    /**
     * Process export orders to CSV
     */
    private function processExportOrdersCSV()
    {
        try {
            $id_customer = $this->context->customer->id;

            $result = VendorHelper::exportVendorOrdersToCSV($id_customer, $this->module);

            if (is_array($result) && isset($result['success']) && !$result['success']) {
                header('Content-Type: text/plain');
                die('Error: ' . $result['message']);
            }

            exit;
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processExportOrdersCSV: ' . $e->getMessage());
            header('Content-Type: text/plain');
            die('Error: ' . $e->getMessage());
        }
    }

    /**
     * Process get add commission status
     */
    private function processGetAddCommissionStatus()
    {
        try {
            $result = VendorHelper::getAddCommissionStatus();
            die(json_encode($result));
        } catch (Exception $e) {
            error_log('MultivendorAjax: Error in processGetAddCommissionStatus: ' . $e->getMessage());
            die(json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]));
        }
    }

    public function processGetAllManifestItems()
    {
        try {
            $id_customer = $this->context->customer->id;
            $vendor = VendorHelper::getVendorByCustomer($id_customer);

            if (!$vendor) {
                die(json_encode(['success' => false, 'message' => 'Not authorized']));
            }

            // Get add commission status
            $addCommissionStatus = VendorHelper::getAddCommissionStatus();
            if (!$addCommissionStatus['success']) {
                die(json_encode(['success' => false, 'message' => 'No commission status found']));
            }

            $statusTypeId = $addCommissionStatus['status']['id_order_line_status_type'];

            // Get ALL order lines with this status (no pagination)
            $query = new DbQuery();
            $query->select('vod.id_order_detail, vod.product_name, vod.product_mpn, vod.product_quantity, o.reference as order_reference , o.id_order');
            $query->from('mv_vendor_order_detail', 'vod');
            $query->leftJoin('orders', 'o', 'o.id_order = vod.id_order');
            $query->leftJoin('mv_order_line_status', 'ols', 'ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor');
            $query->where('vod.id_vendor = ' . (int)$vendor['id_vendor']);
            $query->where('ols.id_order_line_status_type = ' . (int)$statusTypeId);

            $items = Db::getInstance()->executeS($query);

            die(json_encode(['success' => true, 'items' => $items]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }
    /**
     * Ajax function to get all products of the vendor with their attributes
     * 
     */
    public function processGetVendorProducts()
    {
        $vendor = VendorHelper::getVendorByCustomer(Context::getContext()->customer->id);
        $id_vendor = $vendor['id_vendor'];
        $products = Vendor::getProducts($id_vendor);
        $products_with_attributes = [];
        foreach ($products as $product) {
            $attributes = Vendor::getProductsAttribute($product['id_product']);
            $products_with_attributes[] = [
                'product' => $product,
                'attributes' => $attributes
            ];
        }
        die(json_encode([
            'success' => true,
            'products' => $products_with_attributes
        ]));
    }
    /**
     * Ajax function to get the current order details from vendor order detail 
     * 
     */
    private function processGetOrderDetail()
    {

        $id_order_detail = (int)Tools::getValue('id_order_detail');
        $orderDetail = VendorOrderDetail::getByIdOrderDetail($id_order_detail);
        $orderDetail['imageUrl'] = OrderHelper::getProductImageLink($orderDetail['product_id'], $orderDetail['product_attribute_id']);
        $orderDetail['brand'] = VendorOrderDetail::getBrandByProductId($orderDetail['product_id']);
        die(json_encode([
            'success' => true,
            'orderDetail' => $orderDetail
        ]));
    }

    private function processGetSeletecdVariants()
    {
        $product_id = (int)Tools::getValue('product_id');
        $attributes = Vendor::getProductsAttribute($product_id);
        die(json_encode([
            'success' => true,
            'attributes' => $attributes
        ]));
    }

    private function processSearchOutOfStockProducts()
    {
        $search = Tools::getValue('search');
        $currentOrderDetailId = (int)Tools::getValue('currentOrderDetailId');
        $orderDetailObj = new OrderDetail($currentOrderDetailId);
        $idVendor = (int)VendorHelper::getVendorByCustomer(Context::getContext()->customer->id)['id_vendor'];

        $priceFrom = $orderDetailObj->unit_price_tax_incl * 0.7;
        $priceTo = $orderDetailObj->unit_price_tax_incl * 1.3;

        $currentProductObj = new Product($orderDetailObj->product_id);
        $defaultCategory = $currentProductObj->id_category_default;

        $page = (int)Tools::getValue('page', 1);
        $limit = (int)Tools::getValue('limit', 18);
        $offset = ($page - 1) * $limit;

        $products = Vendor::getProducts(
            $idVendor,
            $defaultCategory,
            $priceFrom,
            $priceTo,
            $search,
            $search,
            $search,
            $limit,
            $offset
        );

        $totalProducts = Vendor::getProducts(
            $idVendor,
            $defaultCategory,
            $priceFrom,
            $priceTo,
            $search,
            $search,
            $search,
            0,
            0,
            true
        );

        $productsData = [];

        foreach ($products as $product) {
            $img = Image::getCover($product['id_product']);
            $imgUrl = $this->context->link->getImageLink($product['reference'], $img['id_image'], 'small_default');

            $productObj = new Product($product['id_product'], false, Context::getContext()->language->id);
            $combinations = $productObj->getAttributeCombinations(Context::getContext()->language->id);

            $attributes = [];

            foreach ($combinations as $combination) {
                $groupName = $combination['group_name'];
                $attributeId = $combination['id_attribute'];
                $attributeName = $combination['attribute_name'];

                $combinationPrice = Product::getPriceStatic(
                    $product['id_product'],
                    false,
                    $combination['id_product_attribute'],
                    6,
                    null,
                    false,
                    true,
                    0,
                    false,
                    null,
                    null,
                    null,
                    $specificPriceOutput,
                    true
                );

                if (!isset($attributes[$groupName])) {
                    $attributes[$groupName] = [];
                }

                if ($combinationPrice >= $priceFrom && $combinationPrice <= $priceTo) {
                    $attributes[$groupName][$attributeId] = [
                        'id_attribute' => $attributeId,
                        'name' => $attributeName,
                        'final_price' => Tools::displayPrice($combinationPrice, Context::getContext()->currency)
                    ];
                }
            }

           
            $allGroupsEmpty = true;
            foreach ($attributes as $groupAttributes) {
                if (!empty($groupAttributes)) {
                    $allGroupsEmpty = false;
                    break;
                }
            }

            
            $productsData[] = [
                'id_product' => $product['id_product'],
                'name' => $product['name'],
                'reference' => $product['reference'],
                'price_formatted' => Tools::displayPrice($product['price']),
                'mpn' => $product['mpn'],
                'image_url' => $imgUrl,
                'attributes' => $attributes,
                'all_groups_empty' => $allGroupsEmpty
            ];
        }

  

        $this->context->smarty->assign('products', $productsData);
        $html = $this->context->smarty->fetch('module:multivendor/views/templates/front/orders/_product_list.tpl');

        $totalPages = is_int($totalProducts) ? ceil($totalProducts / $limit) : 1;

        die(json_encode([
            'success' => true,
            'html' => $html,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_products' => $totalProducts,
                'total_pages' => $totalPages
            ]
        ]));
    }

    private function processGefilterDetails()
    {
        $currentOrderDetailId = (int)Tools::getValue('currentOrderDetailId');
        $orderDetailObj = new OrderDetail($currentOrderDetailId);
        $priceFrom = $orderDetailObj->unit_price_tax_incl * 0.7;
        $priceTo = $orderDetailObj->unit_price_tax_incl * 1.3;
        $currentProductObj = new Product($orderDetailObj->product_id);
        $defaultCategoryObj = new Category($currentProductObj->id_category_default);

        die(json_encode([
            'success' => true,
            'priceFrom' => Tools::displayPrice($priceFrom),
            'priceTo' => Tools::displayPrice($priceTo),
            'category' => $defaultCategoryObj->name[Context::getContext()->language->id]
        ]));
    }



    private function processGetProductVariants()
    {
        $idProduct = (int)Tools::getValue('id_product');

        $product = new Product($idProduct, true, Context::getContext()->language->id);
        $combinations = $product->getAttributeCombinations(Context::getContext()->language->id);

        // Group attributes by group
        $attributes = [];
        foreach ($combinations as $combination) {
            $groupName = $combination['group_name'];
            if (!isset($attributes[$groupName])) {
                $attributes[$groupName] = [];
            }
            $attributes[$groupName][$combination['id_attribute']] = [
                'id_attribute' => $combination['id_attribute'],
                'name' => $combination['attribute_name']
            ];
        }

        // Remove duplicates
        foreach ($attributes as &$group) {
            $group = array_values($group);
        }

        $img = Image::getCover($idProduct);
        $imgUrl = $this->context->link->getImageLink($product->reference, $img['id_image'], 'small_default');

        $productData = [
            'id_product' => $idProduct,
            'name' => $product->name,
            'reference' => $product->reference,
            'price_formatted' => Tools::displayPrice($product->price),
            'mpn' => $product->mpn,
            'image_url' => $imgUrl,
            'attributes' => $attributes
        ];

        $this->context->smarty->assign('product', $productData);
        $html = $this->context->smarty->fetch('module:multivendor/views/templates/front/orders/_product_item.tpl');

        die(json_encode(['success' => true, 'html' => $html]));
    }

    private function processAddOutOfStockSuggestion()
    {
        $idProduct = (int)Tools::getValue('id_product');
        $selectedAttributes = Tools::getValue('attributes');
        $idOrderDetail = (int)Tools::getValue('id_order_detail');

        $product = new Product($idProduct, true, Context::getContext()->language->id);
        $product_reference = $product->reference;
        $suggestions = $product->name . ' [' . $product_reference . ']';
        $selectedAttributesId =  $selectedAttributes ?  implode(',', array_column($selectedAttributes, 'id')) : false;

        if (!empty($selectedAttributes)) {
            foreach ($selectedAttributes as $attribute) {
                $suggestions .= ' - ' . $attribute['name'];
            }
        }

        if (!isset($_SESSION['out_of_stock_suggestions'])) {
            $_SESSION['out_of_stock_suggestions'] = [];
        }
        if (!isset($_SESSION['out_of_stock_suggestions'][$idOrderDetail])) {
            $_SESSION['out_of_stock_suggestions'][$idOrderDetail] = [];
        }

        $_SESSION['out_of_stock_suggestions'][$idOrderDetail][] = $suggestions;

        $result = [
            'success' => true,
            'suggestions' => $suggestions,
            "id_product" => $idProduct,
        ];

        if ($selectedAttributesId) {
            $result["attributes"] = $selectedAttributesId;
        }

        die(json_encode($result));
    }
}
