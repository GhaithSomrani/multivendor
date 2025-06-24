<?php

/**
 * Complete Admin Vendor Payments Controller
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

        $this->addRowAction('view');

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
     * SIMPLIFIED: Pay commission method - Clean and efficient
     */
    public function ajaxProcessPayCommission()
    {
        $id_vendor = (int)Tools::getValue('id_vendor');
        $payment_method = Tools::getValue('payment_method');
        $reference = Tools::getValue('reference');

        if (!$id_vendor || !$payment_method || !$reference) {
            die(json_encode(['success' => false, 'message' => $this->l('Missing required parameters')]));
        }

        try {
            // Get unpaid commission amount and details
            $pendingData = $this->getUnpaidCommissionDetails($id_vendor);
            
            if (empty($pendingData['order_details']) || $pendingData['total_amount'] <= 0) {
                die(json_encode(['success' => false, 'message' => $this->l('No unpaid commissions found')]));
            }

            Db::getInstance()->execute('START TRANSACTION');

            // Create payment record
            $payment = new VendorPayment();
            $payment->id_vendor = $id_vendor;
            $payment->amount = $pendingData['total_amount'];
            $payment->payment_method = pSQL($payment_method);
            $payment->reference = pSQL($reference);
            $payment->status = 'completed';
            $payment->date_add = date('Y-m-d H:i:s');

            if (!$payment->save()) {
                throw new Exception('Failed to create payment record');
            }

            // Mark all unpaid transactions as paid
            $this->markTransactionsAsPaid($pendingData['order_details'], $payment->id);

            Db::getInstance()->execute('COMMIT');

            die(json_encode([
                'success' => true,
                'message' => $this->l('Payment processed successfully'),
                'amount_paid' => $pendingData['total_amount'],
                'transactions_count' => count($pendingData['order_details'])
            ]));

        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            PrestaShopLogger::addLog('Payment error: ' . $e->getMessage(), 3);
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Get unpaid commission details for a vendor - FIXED to get only unpaid order details
     */
    private function getUnpaidCommissionDetails($id_vendor)
    {
        // Get only order details that haven't been paid yet
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        $query = '
            SELECT 
                vod.id_order_detail,
                vod.vendor_amount,
                vod.product_name,
                vod.product_reference,
                vod.product_quantity,
                o.reference as order_reference,
                o.date_add as order_date
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_transaction vt ON vt.order_detail_id = vod.id_order_detail AND vt.transaction_type = "commission"
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND (
                (olst.commission_action = "add") 
                OR 
                (ols.id_order_line_status_type IS NULL AND "' . pSQL($defaultStatusType->commission_action) . '" = "add")
            )
            AND (vt.status IS NULL OR vt.status != "paid")
            ORDER BY o.date_add DESC
        ';

        $orderDetails = Db::getInstance()->executeS($query);
        
        // Calculate total from unpaid order details
        $totalAmount = 0;
        foreach ($orderDetails as $detail) {
            $totalAmount += (float)$detail['vendor_amount'];
        }

        return [
            'order_details' => $orderDetails,
            'total_amount' => $totalAmount
        ];
    }

    /**
     * Mark transactions as paid - ONLY for unpaid order details
     */
    private function markTransactionsAsPaid($orderDetails, $paymentId)
    {
        foreach ($orderDetails as $detail) {
            // Check if transaction exists and is unpaid
            $existingTransaction = Db::getInstance()->getRow(
                'SELECT id_vendor_transaction, status FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction 
                 WHERE order_detail_id = ' . (int)$detail['id_order_detail'] . ' 
                 AND transaction_type = "commission"'
            );

            if ($existingTransaction) {
                // Only update if not already paid
                if ($existingTransaction['status'] != 'paid') {
                    Db::getInstance()->update('mv_vendor_transaction', [
                        'status' => 'paid',
                        'id_vendor_payment' => (int)$paymentId,
                        'date_add' => date('Y-m-d H:i:s')
                    ], 'id_vendor_transaction = ' . (int)$existingTransaction['id_vendor_transaction']);
                }
            } else {
                // Create new transaction record for unpaid commission
                Db::getInstance()->insert('mv_vendor_transaction', [
                    'order_detail_id' => (int)$detail['id_order_detail'],
                    'vendor_amount' => (float)$detail['vendor_amount'],
                    'transaction_type' => 'commission',
                    'status' => 'paid',
                    'id_vendor_payment' => (int)$paymentId,
                    'date_add' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    /**
     * FIXED: Init content for pending commissions - Updated to match commission summary logic
     */
    public function initContentPendingCommissions()
    {
        $this->display = '';

        // Get the default status and its commission action
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);
        $defaultAction = $defaultStatusType->commission_action;

        // Use the same logic as getVendorCommissionSummary
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
                    SELECT COALESCE(SUM(vod.vendor_amount), 0) 
                    FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
                    LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
                    LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
                    WHERE vod.id_vendor = v.id_vendor
                    AND (
                        (olst.commission_action = "add") 
                        OR 
                        (ols.id_order_line_status_type IS NULL AND "' . pSQL($defaultAction) . '" = "add")
                    )
                ) AS total_commission_added
            FROM ' . _DB_PREFIX_ . 'mv_vendor v
            HAVING (total_commission_added - total_paid) > 0
            ORDER BY (total_commission_added - total_paid) DESC
        ';

        $pendingCommissions = Db::getInstance()->executeS($sql);
        
        // Calculate pending amount for each vendor
        foreach ($pendingCommissions as &$commission) {
            $commission['total_commission_added'] = (float)$commission['total_commission_added'];
            $commission['total_paid'] = (float)$commission['total_paid'];
            $commission['pending_amount'] = $commission['total_commission_added'] - $commission['total_paid'];
            $commission['transaction_count'] = $this->countPendingTransactionsFixed($commission['id_vendor']);
        }

        // Get payment methods
        $paymentMethods = [
            'bank_transfer' => $this->l('Virement bancaire'),
            'cash' => $this->l('Espèces'),
            'check' => $this->l('Chèque'),
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
     * FIXED: Count only unpaid transactions
     */
    protected function countPendingTransactionsFixed($id_vendor)
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        $query = '
            SELECT COUNT(DISTINCT vod.id_order_detail) as count
            FROM ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_transaction vt ON vt.order_detail_id = vod.id_order_detail AND vt.transaction_type = "commission"
            WHERE vod.id_vendor = ' . (int)$id_vendor . '
            AND (
                (olst.commission_action = "add") 
                OR 
                (ols.id_order_line_status_type IS NULL AND "' . pSQL($defaultStatusType->commission_action) . '" = "add")
            )
            AND (vt.status IS NULL OR vt.status != "paid")
        ';
        
        return (int)Db::getInstance()->getValue($query);
    }

    /**
     * ENHANCED: Get payment details with complete order line information
     */
    protected function getPaymentTransactionDetails($id_vendor_payment)
    {
        $query = '
            SELECT 
                vt.id_vendor_transaction,
                vt.vendor_amount,
                vt.transaction_type,
                vt.status,
                vt.date_add as transaction_date,
                vod.product_name,
                vod.product_reference,
                vod.product_quantity,
                vod.id_vendor,
                o.id_order,
                o.reference as order_reference,
                o.date_add as order_date,
                vt.vendor_amount as commission_amount
            FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vt.id_vendor_payment = ' . (int)$id_vendor_payment . '
            AND vt.transaction_type = "commission"
            ORDER BY o.date_add DESC
        ';

        return Db::getInstance()->executeS($query);
    }

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

    protected function updatePaymentStatus($id_payment, $status)
    {
        $payment = new VendorPayment($id_payment);
        if (!Validate::isLoadedObject($payment)) {
            return false;
        }
        $payment->status = $status;
        return $payment->save();
    }

    public function renderForm()
    {
        $vendors = Vendor::getAllVendors();
        $vendorsArray = [];
        foreach ($vendors as $vendor) {
            $vendorsArray[] = [
                'id' => $vendor['id_vendor'],
                'name' => $vendor['shop_name']
            ];
        }

        $paymentMethods = [
            ['id' => 'bank_transfer', 'name' => $this->l('Virement bancaire')],
            ['id' => 'cash', 'name' => $this->l('Espèces')],
            ['id' => 'check', 'name' => $this->l('Chèque')],
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
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Amount'),
                    'name' => 'amount',
                    'required' => true,
                    'suffix' => $this->context->currency->sign
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
                    'required' => true
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

    public function renderView()
    {
        $payment = new VendorPayment($this->id_object);
        
        if (!Validate::isLoadedObject($payment)) {
            $this->errors[] = $this->l('Payment not found.');
            return false;
        }

        $vendor = new Vendor($payment->id_vendor);
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

    public function postProcess()
    {
        if (Tools::isSubmit('pending_commissions')) {
            $this->initContentPendingCommissions();
            return;
        }

        return parent::postProcess();
    }
}