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
        $this->addRowAction('delete');

        // Add bulk actions
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?')
            ],
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
            $this->page_header_toolbar_btn['new_payment'] = [
                'href' => self::$currentIndex . '&addvendor_payment&token=' . $this->token,
                'desc' => $this->l('Add New Payment'),
                'icon' => 'process-icon-new'
            ];

            $this->page_header_toolbar_btn['pending_commissions'] = [
                'href' => self::$currentIndex . '&pending_commissions&token=' . $this->token,
                'desc' => $this->l('View Pending Commissions'),
                'icon' => 'process-icon-money'
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
     * Init content for pending commissions
     */
    public function initContentPendingCommissions()
    {
        $this->display = '';

        // Get the default status and its commission action
        $defaultStatus = Db::getInstance()->getRow(
            '
        SELECT * FROM `' . _DB_PREFIX_ . 'mv_order_line_status_type` 
        WHERE active = 1 
        ORDER BY position ASC '
        );

        $defaultAction = $defaultStatus ? $defaultStatus['commission_action'] : 'none';

        $query = new DbQuery();
        $query->select('v.id_vendor, v.shop_name');

        $query->select('(
        SELECT SUM(vod.vendor_amount) 
        FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
        WHERE vod.id_vendor = v.id_vendor
        AND (
            (olst.commission_action = "add") 
            OR 
            (ols.id_order_line_status_type IS NULL AND "' . pSQL($defaultAction) . '" = "add")
        )
    ) as commissions_added');

        $query->select('(
        SELECT COALESCE(SUM(vp.amount), 0)
        FROM ' . _DB_PREFIX_ . 'mv_vendor_payment vp
        WHERE vp.id_vendor = v.id_vendor
        AND vp.status = "completed"
    ) as total_paid');

        $query->select('(
    SELECT COALESCE(SUM(vt.vendor_amount), 0)
    FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
    WHERE vt.id_vendor = v.id_vendor
    AND vt.status = "pending"
    ) as pending_amount');

        $query->from('mv_vendor', 'v');
        $query->having('pending_amount > 0');
        $query->orderBy('pending_amount DESC');

        $pendingCommissions = Db::getInstance()->executeS($query);

        // Format the results
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
     * Count pending transactions for a vendor
     * This counts unique order details with commission_action = 'add' that haven't been paid
     * 
     * @param int $id_vendor Vendor ID
     * @return int Number of pending transactions
     */
    protected function countPendingTransactions($id_vendor)
    {
        $query = new DbQuery();
        $query->select('COUNT(DISTINCT vt.order_detail_id) as count');
        $query->from('mv_vendor_transaction', 'vt');
        $query->where('vt.id_vendor = ' . (int)$id_vendor);
        $query->where('vt.transaction_type = "commission"');
        $query->where('vt.status = "pending"');
        $count = Db::getInstance()->getValue($query);
        return (int)$count;
    }
    /**
     * AJAX process pay commission
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

        $result = TransactionHelper::payVendorCommissions(
            $id_vendor,
            $payment_method,
            $reference,
            $this->context->employee->id
        );

        if ($result['success']) {
            die(json_encode([
                'success' => true,
                'message' => sprintf(
                    $this->l('Successfully paid %s for %d order lines'),
                    Tools::displayPrice($result['amount_paid']),
                )
            ]));
        } else {
            die(json_encode([
                'success' => false,
                'message' => $result['message']
            ]));
        }
    }

    /**
     * Process controller actions
     */
    public function postProcess()
    {
        if (Tools::isSubmit('pending_commissions')) {
            return;
        }
        $this->initContentPendingCommissions();

        parent::postProcess();
    }
}
