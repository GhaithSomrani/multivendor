<?php

/**
 * Vendor Facturation (Payments/Invoicing) controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}


class MultivendorFacturationModuleFrontController extends ModuleFrontController
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

        // Payment filters
        $payment_filter = [
            'reference' => Tools::getValue('payment_reference'),
            'payment_method' => Tools::getValue('payment_method'),
            'status' => Tools::getValue('payment_status'),
            'datefilter' => Tools::getValue('payment_date_filter'),
            'amount_min' => Tools::getValue('payment_amount_min'),
            'amount_max' => Tools::getValue('payment_amount_max'),
        ];

        $payment_filter = array_filter($payment_filter, function ($value) {
            return $value !== '' && $value !== null && $value !== false;
        });

        // Pagination
        $payment_page = (int)Tools::getValue('payment_page', 1);
        $payment_per_page = 10;
        $payment_offset = ($payment_page - 1) * $payment_per_page;

        // Get payments
        $payments = VendorPayment::getVendorPaymentsWithDetails($id_vendor, $payment_per_page, $payment_offset, $payment_filter);
        $totalPayments = VendorPayment::countTotalPayments($id_vendor, $payment_filter);
        $paymentTotalPages = ceil($totalPayments / $payment_per_page);

        // Add CSS/JS files
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/dashboard.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/orders.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/commissions.css');
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/facturation.css');

        $this->context->controller->registerStylesheet('daterangepicker-css', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', ['media' => 'all', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('moment-js', 'https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js', ['position' => 'head', 'priority' => 200, 'server' => 'remote']);
        $this->context->controller->registerJavascript('daterangepicker-js', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', ['position' => 'bottom', 'priority' => 201, 'server' => 'remote']);

        // Assign to template
        $this->context->smarty->assign([
            'payments' => $payments,
            'total_payments' => $totalPayments,
            'payment_filter' => $payment_filter,
            'payment_pages_nb' => $paymentTotalPages,
            'payment_current_page' => $payment_page,
            'currency_sign' => $this->context->currency->sign,
            'currency' => $this->context->currency
        ]);

        $this->setTemplate('module:multivendor/views/templates/front/facturation.tpl');
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
