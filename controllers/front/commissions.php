<?php

/**
 * Vendor Commissions controller
 */

use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;

if (!defined('_PS_VERSION_')) {
    exit;
}


class MultivendorCommissionsModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function init()
    {
        parent::init();

        // Check if customer is a vendor
        $id_customer = $this->context->customer->id;
        $vendor = VendorHelper::getVendorByCustomer($id_customer);
        $access_result = VendorHelper::validateVendorAccess($this->context->customer->id);

        if (!$access_result['has_access']) {
            if ($access_result['status'] === 'not_vendor') {
                Tools::redirect('index.php?controller=my-account');
            } else {
                // Redirect to dashboard which will show verification page
                Tools::redirect($this->context->link->getModuleLink('multivendor', 'dashboard'));
            }
        }

        $this->context->smarty->assign('id_vendor', $access_result['vendor']['id_vendor']);
    }


    public function initContent()
    {
        parent::initContent();

        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');

        // Get filters and sorting from URL - don't set defaults yet
        $filter = [
            'order_id' => Tools::getValue('order_id'),
            'product_name' => Tools::getValue('product_name'),
            'reference' => Tools::getValue('reference'),
            'amount_min' => Tools::getValue('amount_min'),
            'amount_max' => Tools::getValue('amount_max'),
            'commission_action' => Tools::getValue('commission_action'),
            'transaction_status' => Tools::getValue('transaction_status'),
            'transaction_type' => Tools::getValue('transaction_type'),
            'line_status' => Tools::getValue('line_status'),
            'line_status_not' => Tools::getValue('line_status_not'),
            'datefilter' => Tools::getValue('datefilter'),
            'payment_datefilter' => Tools::getValue('payment_datefilter'),
            'order_by' => Tools::getValue('order_by'),
            'order_way' => Tools::getValue('order_way'),
        ];

        // Remove empty filter values for cleaner URLs
        $filter = array_filter($filter, function ($value) {
            return $value !== '' && $value !== null && $value !== false;
        });
        $view = Tools::getValue('view', 'transactions');


        // Pagination
        $page = (int)Tools::getValue('page', 1);
        $per_page = 10;
        $offset = ($page - 1) * $per_page;

        $commissionSummary = Vendor::getVendorCommissionSummary($id_vendor, $filter);

        $orderDetails = VendorHelper::getVendorOrderDetailsWithTransactions($id_vendor, $per_page, $offset, $filter);
        $totalOrderDetails = VendorHelper::countVendorOrderDetails($id_vendor, $filter);
        $totalPages = ceil($totalOrderDetails / $per_page);

        // Get payments

        try {
            $vendorCommissionRate = VendorCommission::getCommissionRate($id_vendor);
            $effectiveRate = $vendorCommissionRate;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error fetching commission rate: ' . $e->getMessage(), 3, null, 'MultivendorCommissionsModuleFrontController', $id_vendor);
        }

        // Add CSS/JS files
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/orders.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/commissions.css');

        $this->context->controller->registerStylesheet('daterangepicker-css', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', ['media' => 'all', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('moment-js', 'https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js', ['position' => 'head', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('module-drift-js', 'https://unpkg.com/drift-zoom/dist/Drift.min.js', ['position' => 'head', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerStylesheet('module-drift-css', 'https://unpkg.com/drift-zoom/dist/drift-basic.min.css', ['media' => 'all', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('daterangepicker-js', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', ['position' => 'bottom', 'priority' => 201, 'server' => 'remote']);

        // Get all order line status types for filter dropdown
        $orderLineStatuses = OrderLineStatusType::getDisplayedStatusTypes();
        $orderLineStatuses = array_filter($orderLineStatuses, function ($status) {
            return in_array($status['commission_action'], ['refund', 'add']);
        });
        // Assign to template
        $this->context->smarty->assign([
            'view' => $view,
            'commission_summary' => $commissionSummary,
            'vendor_commission_rate' => $effectiveRate,
            'order_details' => $orderDetails,
            'total_order_details' => $totalOrderDetails,
            'filter' => $filter,
            'pages_nb' => $totalPages,
            'current_page' => $page,
            'order_line_statuses' => $orderLineStatuses,
            'currency_sign' => $this->context->currency->sign,
            'currency' => $this->context->currency
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/commissions.tpl');
    }

    public function displayAjax()
    {
        if (Tools::getValue('action') == 'printPayment') {
            $id_payment = (int)Tools::getValue('id_payment');

            if (!$id_payment) {
                die(json_encode(['error' => 'Invalid payment ID']));
            }

            $payment = new VendorPayment($id_payment);

            if (!Validate::isLoadedObject($payment)) {
                die(json_encode(['error' => 'Payment not found']));
            }

            $vendor = new Vendor($payment->id_vendor);
            $vendorAddress = VendorHelper::getSupplierAddressByVendor($payment->id_vendor);
            $transactionDetails = $this->getPaymentTransactionDetails($payment->id);

            $printData = [
                'payment' => $payment,
                'vendor' => $vendor,
                'transaction_details' => $transactionDetails,
                'currency' => $this->context->currency,
                'shop' => $this->context->shop,
                'date_generated' => date('Y-m-d H:i:s'),
                'vendor_address' => $vendorAddress
            ];

            $this->context->smarty->assign($printData);

            $content = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'multivendor/views/templates/pdf/payment_print.tpl'
            );

            die($content);
        }

        if (Tools::getValue('action') == 'exportCommissionsCSV') {
            $this->exportCommissionsCSV();
        }
    }

    private function exportCommissionsCSV()
    {
        $id_vendor = $this->context->smarty->getTemplateVars('id_vendor');

        // Get filters from URL
        $filter = [
            'order_id' => Tools::getValue('order_id'),
            'product_name' => Tools::getValue('product_name'),
            'reference' => Tools::getValue('reference'),
            'amount_min' => Tools::getValue('amount_min'),
            'amount_max' => Tools::getValue('amount_max'),
            'commission_action' => Tools::getValue('commission_action'),
            'transaction_status' => Tools::getValue('transaction_status'),
            'transaction_type' => Tools::getValue('transaction_type'),
            'line_status' => Tools::getValue('line_status'),
            'line_status_not' => Tools::getValue('line_status_not'),
            'datefilter' => Tools::getValue('datefilter'),
            'order_by' => Tools::getValue('order_by'),
            'order_way' => Tools::getValue('order_way'),
        ];

        // Remove empty filter values
        $filter = array_filter($filter, function ($value) {
            return $value !== '' && $value !== null && $value !== false;
        });

        // Get ALL order details without pagination (no limit)
        $orderDetails = VendorHelper::getVendorOrderDetailsWithTransactions($id_vendor, null, null, $filter);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="commissions_' . date('Y-m-d_H-i-s') . '.csv"');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Add CSV headers
        fputcsv($output, [
            'Numero de commande',
            'Reference produit',
            'Nom du produit',
            'Montant vendeur',
            'Statut',
            'Statut paiement commission',
            'Date validation',
            'Reference du paiement',
            'Date de paiement',
            'Remboursement',
            'Date demande de remboursement',
            'Reference de remboursement',
            'Date validation remboursement'
        ], ';');

        // Add data rows
        foreach ($orderDetails as $detail) {
            // Get transactions for this order detail
            $transactions = VendorTransaction::getTransactionsByOrderDetail($detail['id_order_detail']);

            $commissionStatus = '-';
            $dateValidation = '-';
            $paymentReference = '-';
            $datePayment = '-';
            $refundAmount = '-';
            $refundDate = '-';
            $refundReference = '-';
            $refundPaymentDate = '-';

            foreach ($transactions as $transaction) {
                if ($transaction['transaction_type'] == 'commission') {
                    $commissionStatus = $transaction['status'] == 'paid' ? 'Payé' : 'En cours';
                    $dateValidation = $transaction['transaction_date'] ? date('Y-m-d H:i', strtotime($transaction['transaction_date'])) : '-';

                    // Get payment reference and date if paid
                    if ($transaction['status'] == 'paid' && !empty($transaction['id_vendor_payment'])) {
                        $payment = new VendorPayment($transaction['id_vendor_payment']);
                        if (Validate::isLoadedObject($payment)) {
                            $paymentReference = $payment->reference;
                            $datePayment = date('Y-m-d H:i', strtotime($payment->date_add));
                        }
                    }
                } elseif ($transaction['transaction_type'] == 'refund') {
                    $refundAmount = number_format($transaction['vendor_amount'], 3);
                    $refundDate = $transaction['transaction_date'] ? date('Y-m-d H:i', strtotime($transaction['transaction_date'])) : '-';

                    // Get refund payment reference and date if paid
                    if ($transaction['status'] == 'paid' && !empty($transaction['id_vendor_payment'])) {
                        $payment = new VendorPayment($transaction['id_vendor_payment']);
                        if (Validate::isLoadedObject($payment)) {
                            $refundReference = $payment->reference;
                            $refundPaymentDate = date('Y-m-d H:i', strtotime($payment->date_add));
                        }
                    }
                }
            }

            fputcsv($output, [
                $detail['id_order_detail'],
                $detail['product_reference'],
                $detail['product_name'],
                number_format($detail['vendor_amount'], 3),
                $detail['line_status'],
                $commissionStatus,
                $dateValidation,
                $paymentReference,
                $datePayment,
                $refundAmount,
                $refundDate,
                $refundReference,
                $refundPaymentDate
            ], ';');
        }

        fclose($output);
        exit;
    }
    private function getPaymentTransactionDetails($id_vendor_payment)
    {
        $query = '
            SELECT 
                vt.id_vendor_transaction,
                vt.vendor_amount,
                vt.transaction_type,
                vt.status,
                vt.date_add as transaction_date,
                vod.product_name,
                vod.id_order_detail,
                vod.product_reference,
                vod.product_quantity,
                vod.id_vendor,
                o.id_order,
                o.reference as order_reference,
                o.date_add as order_date,
               olst.name as line_status,
               olst.color as status_color,
              ols.id_order_line_status_type as status_type_id
            FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vt.id_vendor_payment = ' . (int)$id_vendor_payment . '
            ORDER BY o.date_add DESC
        ';

        return Db::getInstance()->executeS($query);
    }
}
