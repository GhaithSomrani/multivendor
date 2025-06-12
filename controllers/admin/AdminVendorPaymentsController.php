<?php

/**
 * Admin Vendor Payments Controller
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminVendorPaymentsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'mv_vendor_payment';
        $this->className = 'VendorPayment';
        $this->lang = false;
        $this->identifier = 'id_vendor_payment';
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $this->fields_list = [
            'id_vendor_payment' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'vendor_name' => [
                'title' => $this->l('Vendor'),
                'filter_key' => 'vendor!shop_name',
                'havingFilter' => true
            ],
            'amount' => [
                'title' => $this->l('Amount'),
                'type' => 'price',
                'currency' => true,
                'filter_key' => 'a!amount'
            ],
            'payment_method' => [
                'title' => $this->l('Payment Method'),
                'filter_key' => 'a!payment_method'
            ],
            'reference' => [
                'title' => $this->l('Reference'),
                'filter_key' => 'a!reference'
            ],
            'status' => [
                'title' => $this->l('Status'),
                'type' => 'select',
                'list' => [
                    'pending' => $this->l('Pending'),
                    'completed' => $this->l('Completed'),
                    'cancelled' => $this->l('Cancelled')
                ],
                'filter_key' => 'a!status',
                'badge_success' => ['completed'],
                'badge_warning' => ['pending'],
                'badge_danger' => ['cancelled']
            ],
            'date_add' => [
                'title' => $this->l('Date'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ]
        ];

        $this->_select = '
            v.shop_name as vendor_name
        ';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (v.id_vendor = a.id_vendor)
        ';

        // Add custom actions
        $this->addRowAction('view');

        // Add bulk actions
        $this->bulk_actions = [
            'markCompleted' => [
                'text' => $this->l('Mark as Completed'),
                'confirm' => $this->l('Mark selected payments as completed?')
            ],
            'markCancelled' => [
                'text' => $this->l('Mark as Cancelled'),
                'confirm' => $this->l('Mark selected payments as cancelled?')
            ]
        ];
    }

    /**
     * Initialize page header toolbar
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if (empty($this->display)) {
      

            $this->page_header_toolbar_btn['pending_commissions'] = [
                'href' => self::$currentIndex . '&pending_commissions&token=' . $this->token,
                'desc' => $this->l('View Pending Commissions'),
                'icon' => 'process-icon-new'
            ];
        }
    }

    /**
     * Process bulk actions
     */
    public function processBulkMarkCompleted()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $success = true;

            foreach ($this->boxes as $id) {
                $success &= $this->updatePaymentStatus($id, 'completed');
            }

            if ($success) {
                $this->confirmations[] = $this->l('Selected payments have been marked as completed.');
            } else {
                $this->errors[] = $this->l('An error occurred while updating payment status.');
            }
        } else {
            $this->errors[] = $this->l('No payment has been selected.');
        }
    }

    /**
     * Process bulk actions
     */
    public function processBulkMarkCancelled()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $success = true;

            foreach ($this->boxes as $id) {
                $success &= $this->updatePaymentStatus($id, 'cancelled');
            }

            if ($success) {
                $this->confirmations[] = $this->l('Selected payments have been marked as cancelled.');
            } else {
                $this->errors[] = $this->l('An error occurred while updating payment status.');
            }
        } else {
            $this->errors[] = $this->l('No payment has been selected.');
        }
    }

    /**
     * Update payment status
     */
    protected function updatePaymentStatus($id_payment, $status)
    {
        $payment = new VendorPayment($id_payment);

        if (!Validate::isLoadedObject($payment)) {
            return false;
        }

        $payment->status = $status;
        return $payment->save();
    }

    /**
     * Render form
     */
    public function renderForm()
    {
        // Get vendors
        $vendors = Vendor::getAllVendors();
        $vendorsArray = [];
        foreach ($vendors as $vendor) {
            $vendorsArray[] = [
                'id' => $vendor['id_vendor'],
                'name' => $vendor['shop_name']
            ];
        }

        // Get payment methods
        $paymentMethods = [
            ['id' => 'bank_transfer', 'name' => $this->l('Bank Transfer')],
            ['id' => 'paypal', 'name' => $this->l('PayPal')],
            ['id' => 'check', 'name' => $this->l('Check')],
            ['id' => 'other', 'name' => $this->l('Other')]
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Vendor Payment'),
                'icon' => 'icon-money'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Vendor'),
                    'name' => 'id_vendor',
                    'options' => [
                        'query' => $vendorsArray,
                        'id' => 'id',
                        'name' => 'name'
                    ],
                    'required' => true,
                    'desc' => $this->l('Select vendor to pay')
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Amount'),
                    'name' => 'amount',
                    'required' => true,
                    'suffix' => $this->context->currency->sign,
                    'desc' => $this->l('Enter payment amount')
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Payment Method'),
                    'name' => 'payment_method',
                    'options' => [
                        'query' => $paymentMethods,
                        'id' => 'id',
                        'name' => 'name'
                    ],
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Reference'),
                    'name' => 'reference',
                    'required' => true,
                    'desc' => $this->l('Enter payment reference (e.g., transaction ID, check number)')
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Status'),
                    'name' => 'status',
                    'options' => [
                        'query' => [
                            ['id' => 'pending', 'name' => $this->l('Pending')],
                            ['id' => 'completed', 'name' => $this->l('Completed')],
                            ['id' => 'cancelled', 'name' => $this->l('Cancelled')]
                        ],
                        'id' => 'id',
                        'name' => 'name'
                    ],
                    'required' => true
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];

        if (!$this->object->id) {
            $this->fields_value['status'] = 'completed';
        }

        return parent::renderForm();
    }

    /**
     * Init content for pending commissions - FIXED FOR NEW STRUCTURE
     */
    public function initContentPendingCommissions()
    {
        $this->display = '';

        // Get the default status and its commission action
        $defaultStatus = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
             WHERE active = 1 
             ORDER BY position ASC'
        );

        $defaultAction = $defaultStatus ? $defaultStatus['commission_action'] : 'none';

        $sql = '
            SELECT 
                v.id_vendor, 
                v.shop_name,
                (
                    SELECT COALESCE(SUM(vp.amount), 0)
                    FROM ' . _DB_PREFIX_ . 'mv_vendor_payment vp
                    WHERE vp.id_vendor = v.id_vendor
                    AND vp.status = "completed"
                ) AS total_paid,
                (
                    SELECT COALESCE(SUM(vt.vendor_amount), 0)
                    FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
                    LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
                    WHERE vod.id_vendor = v.id_vendor
                    AND vt.status = "pending"
                    AND vt.transaction_type = "commission"
                ) AS pending_amount
            FROM ' . _DB_PREFIX_ . 'mv_vendor v
            HAVING pending_amount > 0
            ORDER BY pending_amount DESC
        ';

        $pendingCommissions = Db::getInstance()->executeS($sql);
        
        foreach ($pendingCommissions as &$commission) {
            $commission['pending_amount'] = (float)$commission['pending_amount'];
            $commission['transaction_count'] = $this->countPendingTransactions($commission['id_vendor']);
        }

        // Get payment methods
        $paymentMethods = [
            'bank_transfer' => $this->l('Bank Transfer'),
            'paypal' => $this->l('PayPal'),
            'check' => $this->l('Check'),
            'other' => $this->l('Other')
        ];

        $this->context->smarty->assign([
            'pending_commissions' => $pendingCommissions,
            'payment_methods' => $paymentMethods,
            'current_url' => self::$currentIndex . '&token=' . $this->token,
            'currency' => $this->context->currency
        ]);

        $this->content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'multivendor/views/templates/admin/pending_commissions.tpl');
        $this->context->smarty->assign('content', $this->content);
    }

    /**
     * Count pending transactions for a vendor - FIXED FOR NEW STRUCTURE
     * 
     * @param int $id_vendor Vendor ID
     * @return int Number of pending transactions
     */
    protected function countPendingTransactions($id_vendor)
    {
        $query = new DbQuery();
        $query->select('COUNT(DISTINCT vt.order_detail_id) as count');
        $query->from('mv_vendor_transaction', 'vt');
        $query->leftJoin('mv_vendor_order_detail', 'vod', 'vod.id_order_detail = vt.order_detail_id');
        $query->where('vod.id_vendor = ' . (int)$id_vendor);
        $query->where('vt.transaction_type = "commission"');
        $query->where('vt.status = "pending"');
        
        $count = Db::getInstance()->getValue($query);
        return (int)$count;
    }

    /**
     * AJAX process pay commission - FIXED FOR NEW STRUCTURE
     */
    public function ajaxProcessPayCommission()
    {
        $id_vendor = (int)Tools::getValue('id_vendor');
        $payment_method = Tools::getValue('payment_method');
        $reference = Tools::getValue('reference');

        if (!$id_vendor || !$payment_method || !$reference) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Missing required parameters')
            ]));
        }

        // Use the updated TransactionHelper method
        $result = TransactionHelper::payVendorCommissions(
            $id_vendor,
            $payment_method,
            $reference
        );

        if ($result['success']) {
            die(json_encode([
                'success' => true,
                'message' => $this->l('Successfully paid commissions'),
                'amount_paid' => $result['amount_paid'],
                'transactions_count' => $result['transactions_count']
            ]));
        } else {
            die(json_encode([
                'success' => false,
                'message' => $result['message']
            ]));
        }
    }

    /**
     * Get vendor transaction details for view - NEW METHOD
     * 
     * @param int $id_vendor_payment Payment ID
     * @return array Transaction details
     */
    protected function getPaymentTransactionDetails($id_vendor_payment)
    {
        $query = new DbQuery();
        $query->select('
            vt.*, 
            vod.product_name, 
            vod.product_reference, 
            vod.product_quantity,
            vod.id_vendor,
            od.id_order,
            o.reference as order_reference, 
            o.date_add as order_date
        ');
        $query->from('mv_vendor_transaction', 'vt');
        $query->leftJoin('mv_vendor_order_detail', 'vod', 'vod.id_order_detail = vt.order_detail_id');
        $query->leftJoin('order_detail', 'od', 'od.id_order_detail = vt.order_detail_id');
        $query->leftJoin('orders', 'o', 'o.id_order = od.id_order');
        $query->where('vt.id_vendor_payment = ' . (int)$id_vendor_payment);
        $query->where('vt.transaction_type = "commission"');
        $query->orderBy('o.date_add DESC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Render view for payment details - ENHANCED
     */
    public function renderView()
    {
        $payment = new VendorPayment($this->id_object);
        
        if (!Validate::isLoadedObject($payment)) {
            $this->errors[] = $this->l('Payment not found.');
            return false;
        }

        // Get vendor information
        $vendor = new Vendor($payment->id_vendor);
        
        // Get transaction details using the new method
        $transactionDetails = $this->getPaymentTransactionDetails($payment->id);

        $this->context->smarty->assign([
            'payment' => $payment,
            'vendor' => $vendor,
            'transaction_details' => $transactionDetails,
            'currency' => $this->context->currency
        ]);

        $this->content = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'multivendor/views/templates/admin/payment_view.tpl'
        );

        return $this->content;
    }

    /**
     * Process controller actions - FIXED
     */
    public function postProcess()
    {
        if (Tools::isSubmit('pending_commissions')) {
            $this->initContentPendingCommissions();
            return;
        }

        parent::postProcess();
    }

    /**
     * Get vendor summary for dashboard - NEW METHOD
     * 
     * @param int $id_vendor Vendor ID
     * @return array Vendor summary data
     */
    public function getVendorSummary($id_vendor)
    {
        // Get total earnings
        $totalEarnings = TransactionHelper::getVendorTotalEarnings($id_vendor);
        
        // Get pending commissions
        $pendingCommissions = TransactionHelper::getVendorPendingCommission($id_vendor);
        
        // Get total paid
        $totalPaid = VendorPayment::getVendorTotalPaid($id_vendor);
        
        // Get transaction count
        $transactionCount = $this->countPendingTransactions($id_vendor);

        return [
            'total_earnings' => $totalEarnings,
            'pending_commissions' => $pendingCommissions,
            'total_paid' => $totalPaid,
            'pending_transaction_count' => $transactionCount,
            'payment_percentage' => $totalEarnings > 0 ? ($totalPaid / $totalEarnings) * 100 : 0
        ];
    }
}