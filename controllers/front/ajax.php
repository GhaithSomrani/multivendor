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

            case 'GetOrderDetail':
                $this->processGetOrderDetail();
                break;

            case 'searchOutOfStockProducts':
                $this->processSearchOutOfStockProducts();
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
        $priceFrom = (float)Tools::getValue('priceFrom');
        $priceTo = (float)Tools::getValue('priceTo');

        // Store current product attribute ID (the out-of-stock combination)
        $currentProductAttributeId = $orderDetailObj->product_attribute_id;

        // $priceFrom =  $orderDetailObj->unit_price_tax_incl * 0.7;
        // $priceTo = $orderDetailObj->unit_price_tax_incl * 1.3;

        $currentProductObj = new Product($orderDetailObj->product_id);
        $defaultCategory = $currentProductObj->id_category_default;

        $page = (int)Tools::getValue('page', 1);
        $limit = (int)Tools::getValue('limit', 18);
        $offset = ($page - 1) * $limit;

        $products = Vendor::getOutOfStockProducts(
            $idVendor,
            $defaultCategory,
            $priceFrom,
            $priceTo,
            $search,
            $search,
            $search,
            18,
            $offset,
            false,
            $priceTo = $orderDetailObj->unit_price_tax_incl
        );

        // On page 1, ensure current product is always first
        if ($page == 1) {
            // Check if current product is in results and at what position
            $currentProductInResults = false;
            $currentProductIndex = -1;

            foreach ($products as $index => $product) {
                if ($product['id_product'] == $orderDetailObj->product_id) {
                    $currentProductInResults = true;
                    $currentProductIndex = $index;
                    break;
                }
            }

            if ($currentProductInResults) {
                // If found but not at position 0, move it to the beginning
                if ($currentProductIndex > 0) {
                    $currentProductData = $products[$currentProductIndex];
                    array_splice($products, $currentProductIndex, 1); // Remove from current position
                    array_unshift($products, $currentProductData); // Add to beginning
                }
            } else {
                // If not in results, fetch it separately
                $currentProduct = Vendor::getOutOfStockProducts(
                    $idVendor,
                    $defaultCategory,
                    0, // No price filter for current product
                    999999,
                    $currentProductObj->reference, // Search by reference to find exact product
                    $currentProductObj->name,
                    '',
                    1,
                    0,
                    false,
                    $orderDetailObj->unit_price_tax_incl
                );

                if (!empty($currentProduct)) {
                    // Add current product at the beginning
                    array_unshift($products, $currentProduct[0]);
                }
            }

            // Ensure we maintain the 18-item limit
            if (count($products) > 18) {
                $products = array_slice($products, 0, 18);
            }
        }

        $totalProducts = Vendor::getOutOfStockProducts(
            $idVendor,
            $defaultCategory,
            $priceFrom,
            $priceTo,
            $search,
            $search,
            $search,
            false,
            false,
            true,
            false
        );


        // Get the attributes of the current out-of-stock combination
        $currentCombinationAttributes = [];
        if ($currentProductAttributeId > 0) {
            $sql = 'SELECT id_attribute FROM ' . _DB_PREFIX_ . 'product_attribute_combination
                    WHERE id_product_attribute = ' . (int)$currentProductAttributeId;
            $currentCombinationAttributes = Db::getInstance()->executeS($sql);
            if ($currentCombinationAttributes) {
                $currentCombinationAttributes = array_column($currentCombinationAttributes, 'id_attribute');
                sort($currentCombinationAttributes);
            }
        }

        $productsData = [];

        // On page 1, always add current product first to $productsData
        $currentProductAddedToData = false;
        if ($page == 1) {
            // Check if current product exists in the products array
            $currentProductFound = false;
            foreach ($products as $product) {
                if ($product['id_product'] == $orderDetailObj->product_id) {
                    $currentProductFound = true;
                    break;
                }
            }

            // If not found in products array, we need to add it manually
            if (!$currentProductFound) {
                // Fetch the current product data
                $currentProductData = new Product($orderDetailObj->product_id, false, Context::getContext()->language->id);

                $img = Image::getCover($orderDetailObj->product_id);
                $imgUrl = $this->context->link->getImageLink($currentProductData->reference, $img['id_image'], 'small_default');

                $combinations = $currentProductData->getAttributeCombinations(Context::getContext()->language->id);

                $attributes = [];
                $combinationsByPA = [];
                $productAttributeIds = [];

                foreach ($combinations as $combination) {
                    $productAttributeIds[$combination['id_product_attribute']] = true;
                }

                if (!empty($productAttributeIds)) {
                    $paIds = array_keys($productAttributeIds);
                    $sql = 'SELECT id_product_attribute, mpn FROM ' . _DB_PREFIX_ . 'product_attribute
                            WHERE id_product_attribute IN (' . implode(',', array_map('intval', $paIds)) . ')';
                    $paData = Db::getInstance()->executeS($sql);
                    $mpnData = [];
                    foreach ($paData as $row) {
                        $mpnData[$row['id_product_attribute']] = $row['mpn'];
                    }
                }

                foreach ($combinations as $combination) {
                    $idPA = $combination['id_product_attribute'];
                    if (!isset($combinationsByPA[$idPA])) {
                        $combinationsByPA[$idPA] = [
                            'attributes' => [],
                            'quantity' => $combination['quantity'],
                            'mpn' => isset($mpnData[$idPA]) ? $mpnData[$idPA] : ''
                        ];
                    }
                    $combinationsByPA[$idPA]['attributes'][] = $combination['id_attribute'];
                }

                foreach ($combinations as $combination) {
                    $groupName = $combination['group_name'];
                    $attributeId = $combination['id_attribute'];
                    $attributeName = $combination['attribute_name'];
                    $idPA = $combination['id_product_attribute'];

                    $combinationPrice = Product::getPriceStatic(
                        $orderDetailObj->product_id,
                        true,
                        $idPA,
                        6,
                        null,
                        false,
                        true,
                        0,
                        false,
                        null,
                        null,
                        null
                    );

                    if (!isset($attributes[$groupName])) {
                        $attributes[$groupName] = [];
                    }

                    $attributes[$groupName][$attributeId] = [
                        'id_attribute' => $attributeId,
                        'name' => $attributeName,
                        'final_price' => Tools::displayPrice($combinationPrice, Context::getContext()->currency),
                        'quantity' => $combination['quantity'],
                        'mpn' => isset($mpnData[$idPA]) ? $mpnData[$idPA] : ''
                    ];
                }

                // Build combination data for JavaScript
                $combinationsData = [];
                foreach ($combinationsByPA as $idPA => $paData) {
                    sort($paData['attributes']);
                    $attributeKey = implode('-', $paData['attributes']);
                    $combinationPrice = Product::getPriceStatic(
                        $orderDetailObj->product_id,
                        true,
                        $idPA,
                        6,
                        null,
                        false,
                        true,
                        0,
                        false,
                        null,
                        null,
                        null
                    );
                    $combinationsData[$attributeKey] = [
                        'price' => Tools::displayPrice($combinationPrice, Context::getContext()->currency),
                        'quantity' => $paData['quantity'],
                        'mpn' => !empty($paData['mpn']) ? $paData['mpn'] : ''
                    ];
                }

                $outOfStockCombination = null;
                if (!empty($currentCombinationAttributes)) {
                    $outOfStockCombination = implode('-', $currentCombinationAttributes);
                }

                // Get base product price without any combination
                $baseProductPrice = Product::getPriceStatic(
                    $orderDetailObj->product_id,
                    true,
                    null,
                    6,
                    null,
                    false,
                    true,
                    1,
                    false,
                    null,
                    null,
                    null
                );
                $baseShiftPrice = $baseProductPrice - $orderDetailObj->unit_price_tax_incl;

                // Add current product to productsData
                $productsData[] = [
                    'current_product_id' => $orderDetailObj->product_id,
                    'id_product' => $orderDetailObj->product_id,
                    'name' => $currentProductData->name,
                    'reference' => $currentProductData->reference,
                    'price_formatted' => Tools::displayPrice($baseProductPrice),
                    'shift_price' => number_format($baseShiftPrice, 2),
                    'mpn' => $currentProductData->mpn,
                    'image_url' => $imgUrl,
                    'attributes' => $attributes,
                    'combinations' => $combinationsData,
                    'out_of_stock_combination' => $outOfStockCombination,
                ];

                $currentProductAddedToData = true;
            }
        }

        foreach ($products as $product) {
            // Skip current product if already added to productsData
            if ($currentProductAddedToData && $product['id_product'] == $orderDetailObj->product_id) {
                continue;
            }
            $img = Image::getCover($product['id_product']);
            $imgUrl = $this->context->link->getImageLink($product['reference'], $img['id_image'], 'small_default');

            $productObj = new Product($product['id_product'], false, Context::getContext()->language->id);
            $combinations = $productObj->getAttributeCombinations(Context::getContext()->language->id);

            $attributes = [];

            $combinationsByPA = [];

            $productAttributeIds = [];
            foreach ($combinations as $combination) {
                $productAttributeIds[$combination['id_product_attribute']] = true;
            }

            if (!empty($productAttributeIds)) {
                $paIds = array_keys($productAttributeIds);
                $sql = 'SELECT id_product_attribute, mpn FROM ' . _DB_PREFIX_ . 'product_attribute
                        WHERE id_product_attribute IN (' . implode(',', array_map('intval', $paIds)) . ')';
                $paData = Db::getInstance()->executeS($sql);
                $mpnData = [];
                foreach ($paData as $row) {
                    $mpnData[$row['id_product_attribute']] = $row['mpn'];
                }
            }

            foreach ($combinations as $combination) {
                $idPA = $combination['id_product_attribute'];
                if (!isset($combinationsByPA[$idPA])) {
                    $combinationsByPA[$idPA] = [
                        'attributes' => [],
                        'quantity' => $combination['quantity'],
                        'mpn' => isset($mpnData[$idPA]) ? $mpnData[$idPA] : ''
                    ];
                }
                $combinationsByPA[$idPA]['attributes'][] = $combination['id_attribute'];
            }

            foreach ($combinations as $combination) {
                $groupName = $combination['group_name'];
                $attributeId = $combination['id_attribute'];
                $attributeName = $combination['attribute_name'];
                $idPA = $combination['id_product_attribute'];

                $combinationPrice =  Product::getPriceStatic(
                    $product['id_product'],
                    true,
                    $idPA,
                    6,
                    null,
                    false,
                    true,
                    0,
                    false,
                    null,
                    null,
                    null
                );

                if (!isset($attributes[$groupName])) {
                    $attributes[$groupName] = [];
                }

                $attributes[$groupName][$attributeId] = [
                    'id_attribute' => $attributeId,
                    'name' => $attributeName,
                    'final_price' => Tools::displayPrice($combinationPrice, Context::getContext()->currency),
                    'quantity' => $combination['quantity'],
                    'mpn' => isset($mpnData[$idPA]) ? $mpnData[$idPA] : ''
                ];
            }

            // Build combination data for JavaScript
            $combinationsData = [];
            foreach ($combinationsByPA as $idPA => $paData) {
                // Sort attributes to ensure consistent key
                sort($paData['attributes']);
                $attributeKey = implode('-', $paData['attributes']);
                $combinationPrice = Product::getPriceStatic(
                    $product['id_product'],
                    true,
                    $idPA,
                    6,
                    null,
                    false,
                    true,
                    0,
                    false,
                    null,
                    null,
                    null
                );
                $combinationsData[$attributeKey] = [
                    'price' => Tools::displayPrice($combinationPrice, Context::getContext()->currency),
                    'quantity' => $paData['quantity'],
                    'mpn' => !empty($paData['mpn']) ? $paData['mpn'] : ''
                ];
            }





            // Determine if this is the current product and pass the out-of-stock combination
            $outOfStockCombination = null;
            if ($product['id_product'] == $orderDetailObj->product_id && !empty($currentCombinationAttributes)) {
                $outOfStockCombination = implode('-', $currentCombinationAttributes);
            }

            // Recalculate shift_price based on base product price (without combination)
            // Get base product price without any combination
            $baseProductPrice = Product::getPriceStatic(
                $product['id_product'],
                true,
                null, // No combination
                6,
                null,
                false,
                true,
                1,
                false,
                null,
                null,
                null
            );
            $baseShiftPrice = $baseProductPrice - $orderDetailObj->unit_price_tax_incl;

            $productsData[] = [
                'current_product_id' => $orderDetailObj->product_id,
                'id_product' => $product['id_product'],
                'name' => $product['name'],
                'reference' => $product['reference'],
                'price_formatted' => Tools::displayPrice($baseProductPrice),
                'shift_price' => number_format($baseShiftPrice, 2),
                'mpn' => $product['mpn'],
                'image_url' => $imgUrl,
                'attributes' => $attributes,
                'combinations' => $combinationsData,
                'out_of_stock_combination' => $outOfStockCombination,
            ];
        }

        usort($productsData, function ($a, $b) {
            return  abs($a['shift_price'])  <=>  abs($b['shift_price']);
        });

        $this->context->smarty->assign('products', $productsData);
        $this->context->smarty->assign('original_price', $orderDetailObj->unit_price_tax_incl);
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




    private function processAddOutOfStockSuggestion()
    {
        $idProduct = (int)Tools::getValue('id_product');
        $selectedAttributes = Tools::getValue('attributes');
        $idOrderDetail = (int)Tools::getValue('id_order_detail');
        $product = new Product($idProduct, true, Context::getContext()->language->id);
        $product_reference = $product->reference;
        $suggestions = $product->name . ' [' . $product_reference . ']';
        $attributeIdArray =   $selectedAttributes  ? array_column($selectedAttributes, 'id') : [];
        $selectedAttributesId =  $selectedAttributes ?  implode(',', $attributeIdArray) : ' ';
        $final_price =  Vendor::getProductPriceByAttributes($idProduct, $attributeIdArray, true);

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
            'suggestions' => $suggestions . ' (' . Tools::displayPrice($final_price, Context::getContext()->currency) . ')',
            "id_product" => $idProduct,
        ];

        if ($selectedAttributesId) {
            $result["attributes"] = $selectedAttributesId;
        }

        die(json_encode($result));
    }
}
