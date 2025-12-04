<?php

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
                'title' => $this->l('Statut'),
                'type' => 'select',
                'list' => [
                    'pending' => $this->l('En cours'),
                    'completed' => $this->l('Effectué'),
                    'cancelled' => $this->l('Annulé')
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

        $this->_select = 'vendor.shop_name as vendor_name';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` vendor ON (vendor.id_vendor = a.id_vendor)';

        // Add both view and edit actions
        $idtoskip = Db::getInstance()->executeS('SELECT id_vendor_payment FROM ' . _DB_PREFIX_ . 'mv_vendor_payment WHERE status = "completed"');
        $idtoskip = array_column($idtoskip, 'id_vendor_payment');
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowActionSkipList('delete', $idtoskip);

        $this->bulk_actions = [
            'markCompleted' => [
                'text' => $this->l('Mark as Completed'),
                'confirm' => $this->l('Mark selected payments as completed?')
            ],
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected payments?')
            ]
        ];
    }



    /**
     * Initialize toolbar for view page with print button
     */
    public function initPageHeaderToolbar()
    {
        if ($this->display == 'view' && $this->id_object) {
            $this->page_header_toolbar_btn['print'] = [
                'href' => self::$currentIndex . '&' . $this->identifier . '=' . $this->id_object . '&printpayment&token=' . $this->token,
                'desc' => $this->l('Print Payment'),
                'icon' => 'process-icon-print',
                'target' => '_blank'
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Handle print payment request
     */
    public function processPosition()
    {
        // Handle print request before parent processing
        if (Tools::isSubmit('printpayment')) {
            PrestaShopLogger::addLog('Print payment request detected', 1, null, 'AdminVendorPaymentsController');
            $this->processPrintPayment();
            return;
        }

        return parent::processPosition();
    }

    function processDelete()
    {
        // Prevent deletion of completed payments
        $id_payment = (int)Tools::getValue($this->identifier);
        if (!$id_payment) {
            $this->errors[] = $this->l('Invalid payment ID');
            return false;
        }


        $resetTransaction = Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'mv_vendor_transaction SET id_vendor_payment = 0  , status = "pending" WHERE id_vendor_payment = ' . (int)$id_payment);
        if (!$resetTransaction) {
            $this->errors[] = $this->l('Error resetting transactions for this payment.');
            return false;
        }
        $payment = new VendorPayment($id_payment);
        if ($payment->status === 'completed') {
            $this->errors[] = $this->l('You cannot delete a completed payment.');
            return false;
        }
        return parent::processDelete();
    }
    /**
     * Override postProcess to catch print requests
     */
    public function postProcess()
    {
        if (Tools::isSubmit('printpayment')) {
            PrestaShopLogger::addLog('Print payment request in postProcess', 1, null, 'AdminVendorPaymentsController');
            $this->processPrintPayment();
            return;
        }

        return parent::postProcess();
    }

    /**
     * Override run to catch print requests early
     */
    public function run()
    {
        if (Tools::isSubmit('printpayment')) {
            PrestaShopLogger::addLog('Print payment request in run method', 1, null, 'AdminVendorPaymentsController');
            $this->processPrintPayment();
            return;
        }

        return parent::run();
    }

    /**
     * Process print payment
     */
    public function processPrintPayment()
    {
        $id_payment = (int)Tools::getValue($this->identifier);

        if (!$id_payment) {
            $this->errors[] = $this->l('Invalid payment ID');
            return;
        }

        $payment = new VendorPayment($id_payment);

        if (!Validate::isLoadedObject($payment)) {
            $this->errors[] = $this->l('Payment not found');
            return;
        }

        $vendor = new Vendor($payment->id_vendor);
        $vendorAddress = VendorHelper::getSupplierAddressByVendor($payment->id_vendor);

        $transactionDetails = $this->getPaymentTransactionDetails($payment->id);

        // Prepare data for print template
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

        // Use a dedicated print template
        $content = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'multivendor/views/templates/pdf/payment_print.tpl'
        );

        // Output the print page
        echo $content;
    }

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

        // Payment methods
        $paymentMethods = [
            ['id' => 'bank_transfer', 'name' => $this->l('Bank Transfer')],
            ['id' => 'cash', 'name' => $this->l('Cash')],
            ['id' => 'check', 'name' => $this->l('Check')],
            // ['id' => 'other', 'name' => $this->l('Other')]
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->object->id ? $this->l('Edit Payment') : $this->l('Payment Information'),
                'icon' => 'icon-credit-card'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Vendor'),
                    'name' => 'id_vendor',
                    'options' => [
                        'query' => $vendorsArray,
                        'id' => 'id',
                        'name' => 'name',
                        'default' => [
                            'value' => '',
                            'label' => $this->l('Select vendor')
                        ]
                    ],
                    'required' => true,
                    'disabled' => $this->object->id ? true : false,
                    'onchange' => !$this->object->id ? 'onVendorSelectionChange(this.value);' : '',
                    'hint' => !$this->object->id ? $this->l('Select a vendor to view and select their pending transactions') : ''
                ]
            ],
            'submit' => [
                'title' => $this->object->id ? $this->l('Update Payment') : $this->l('Save Payment')
            ]
        ];

        // For new payments, show transaction selection
        if (!$this->object->id) {
            $this->fields_form['input'][] = [
                'type' => 'html',
                'name' => 'order_details_table',
                'html_content' => $this->renderUnifiedTransactionTable()
            ];
            $this->fields_form['input'][] = [
                'type' => 'text',
                'label' => $this->l('Total Amount'),
                'name' => 'amount',
                'required' => true,
                'suffix' => $this->context->currency->sign,
                'readonly' => true
            ];
        } else {
            // For existing payments, show current transactions and allow editing basic info
            $this->fields_form['input'][] = [
                'type' => 'html',
                'name' => 'current_transactions_table',
                'html_content' => $this->renderCurrentTransactionsTable()
            ];
            $this->fields_form['input'][] = [
                'type' => 'text',
                'label' => $this->l('Amount'),
                'name' => 'amount',
                'required' => true,
                'suffix' => $this->context->currency->sign,
                'readonly' => true
            ];
        }

        // Add payment method field
        $this->fields_form['input'][] = [
            'type' => 'select',
            'label' => $this->l('Payment Method'),
            'name' => 'payment_method',
            'options' => [
                'query' => $paymentMethods,
                'id' => 'id',
                'name' => 'name'
            ],
            'required' => true
        ];

        // Add reference field
        $this->fields_form['input'][] = [
            'type' => 'text',
            'label' => $this->l('Reference'),
            'name' => 'reference',
            'hint' => $this->l('Transaction ID, Check Number, etc.'),
            'required' => true

        ];

        // Add status field
        $this->fields_form['input'][] = [
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
        ];

        // Add notes field
        $this->fields_form['input'][] = [
            'type' => 'textarea',
            'label' => $this->l('Notes'),
            'name' => 'notes',
            'rows' => 3
        ];

        // Set default values for new payments
        if (!$this->object->id) {
            $this->fields_value['status'] = 'pending';
        }

        return parent::renderForm();
    }

    protected function renderUnifiedTransactionTable()
    {
        $statusTypes = orderLineStatusType::getAllActiveStatusTypes();

        $this->context->smarty->assign([
            'status_types' => $statusTypes,
            'currency_sign' => $this->context->currency->sign,
            'current_index' => self::$currentIndex,
            'token' => $this->token,
            'ajax_url' => self::$currentIndex . '&token=' . $this->token

        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'multivendor/views/templates/admin/transaction/transaction_table.tpl'
        );
    }



    /**
     * Render current transactions table for existing payments
     */
    protected function renderCurrentTransactionsTable()
    {
        if (!$this->object->id) {
            return '';
        }

        $transactionDetails = $this->getPaymentTransactionDetails($this->object->id);
        $statusTypes = OrderLineStatusType::getAllActiveStatusTypes();

        // Assign variables to Smarty
        $this->context->smarty->assign([
            'current_transactions' => $transactionDetails,
            'status_types' => $statusTypes,
            'currency_sign' => $this->context->currency->sign,
            'vendor_id' => $this->object->id_vendor ?? 0,
            'payment_id' => $this->object->id,

            'ajax_url' => self::$currentIndex . '&token=' . $this->token
        ]);

        // Fetch and return the template content
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'multivendor/views/templates/admin/transaction/current_transactions_table.tpl'
        );
    }



    /**
     * AJAX: Remove transaction from payment
     */
    public function ajaxProcessRemoveTransactionFromPayment()
    {
        $transaction_id = (int)Tools::getValue('transaction_id');
        $payment_id = (int)Tools::getValue('payment_id');

        if (!$transaction_id || !$payment_id) {
            die(json_encode(['success' => false, 'message' => $this->l('Invalid parameters')]));
        }

        try {
            // Verify transaction belongs to payment
            $transaction = Db::getInstance()->getRow(
                'SELECT * FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction 
             WHERE id_vendor_transaction = ' . (int)$transaction_id . '
             AND id_vendor_payment = ' . (int)$payment_id
            );

            if (!$transaction) {
                die(json_encode(['success' => false, 'message' => $this->l('Transaction not found')]));
            }

            // Remove transaction from payment
            $transactionObj = new VendorTransaction($transaction_id);
            $transactionObj->status = 'pending';
            $transactionObj->id_vendor_payment = 0;
            $result = $transactionObj->save();


            if ($result) {
                // Update payment amount
                $this->updatePaymentAmount($payment_id);

                die(json_encode([
                    'success' => true,
                    'message' => $this->l('Transaction removed successfully'),
                    'amount_removed' => $transaction['vendor_amount']
                ]));
            }

            die(json_encode(['success' => false, 'message' => $this->l('Error updating transaction')]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $this->l('Error removing transaction')]));
        }
    }

    /**
     * AJAX: Get available transactions for vendor
     */
    public function ajaxProcessGetAvailableTransactions()
    {
        $vendor_id = (int)Tools::getValue('vendor_id');
        $payment_id = (int)Tools::getValue('payment_id');
        $status_filter = Tools::getValue('status_filter') ? (int)Tools::getValue('status_filter') : null;
        $date_from = Tools::getValue('date_from') ? pSQL(Tools::getValue('date_from')) : null;
        $date_to = Tools::getValue('date_to') ? pSQL(Tools::getValue('date_to')) : null;
        $include_refunds = Tools::getValue('include_refunds') ? (bool)Tools::getValue('include_refunds') : false;
        $advanced = Tools::getValue('advanced') ? (bool)Tools::getValue('advanced') : false;
        if (!$vendor_id) {
            die(json_encode(['success' => false, 'message' => $this->l('Invalid vendor ID')]));
        }

        try {
            $transactions = $this->getAllPendingTransactions($vendor_id, $status_filter, $date_from, $date_to, $include_refunds, $advanced);

            // Assign variables to Smarty
            $this->context->smarty->assign([
                'advanced' => $advanced,
                'transactions' => $transactions,
                'currency_sign' => $this->context->currency->sign
            ]);

            // Fetch template content
            $html = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ . 'multivendor/views/templates/admin/transaction/available_transactions.tpl'
            );

            die(json_encode(['success' => true, 'html' => $html]));
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error in ajaxProcessGetAvailableTransactions: ' . $e->getMessage(), 3, null, 'AdminVendorPaymentsController');
            die(json_encode(['success' => false, 'message' => $this->l('Error loading transactions')]));
        }
    }

    /**
     * AJAX: Add transaction to payment
     */
    public function ajaxProcessAddTransactionToPayment()
    {
        $transaction_id = (int)Tools::getValue('transaction_id');
        $payment_id = (int)Tools::getValue('payment_id');

        if (!$transaction_id || !$payment_id) {
            die(json_encode(['success' => false, 'message' => $this->l('Invalid parameters')]));
        }

        try {
            // Verify transaction is pending
            $transaction = Db::getInstance()->getRow(
                'SELECT * FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction 
             WHERE id_vendor_transaction = ' . (int)$transaction_id . '
             AND status = "pending" AND id_vendor_payment = 0'
            );

            if (!$transaction) {
                die(json_encode(['success' => false, 'message' => $this->l('Transaction not available')]));
            }

            $transactionObj = new VendorTransaction($transaction_id);
            $transactionObj->status = 'paid';
            $transactionObj->id_vendor_payment = (int)$payment_id;
            $result = $transactionObj->save();


            if ($result) {
                // Update payment amount
                $this->updatePaymentAmount($payment_id);

                die(json_encode([
                    'success' => true,
                    'message' => $this->l('Transaction added successfully')
                ]));
            }

            die(json_encode(['success' => false, 'message' => $this->l('Error updating transaction')]));
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $this->l('Error adding transaction')]));
        }
    }
    /**
     * Update payment total amount based on associated transactions
     */
    protected function updatePaymentAmount($payment_id)
    {
        $total = Db::getInstance()->getValue(
            'SELECT SUM(vendor_amount) 
             FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction 
             WHERE id_vendor_payment = ' . (int)$payment_id
        );

        if ($total !== false) {
            $vendorPaymentOBJ = new VendorPayment($payment_id);
            $vendorPaymentOBJ->amount = (float)$total;
            $vendorPaymentOBJ->save();
        }
    }


    /**
     * Get all pending transactions from mv_vendor_transaction with proper status filtering
     */
    public function getAllPendingTransactions($id_vendor = null, $status_filter = null, $date_from = null, $date_to = null, $include_refunds = false, $advanced = false)
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
        vod.id_order, 
        o.reference as order_reference, 
        o.date_add as order_date, 
        v.shop_name, 
        olst.name as line_status, 
        olst.color as status_color, 
        ols.id_order_line_status_type as status_type_id, 
        olst.commission_action as commission_action
    FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
    INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
    INNER JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
    INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor v ON v.id_vendor = vod.id_vendor
    LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
    LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
    WHERE vt.status = "pending" 
    AND vt.id_vendor_payment = 0
';

        if ($include_refunds) {
            $query .= ' AND (vt.transaction_type = "commission" OR vt.transaction_type = "refund")';
            $query .= ' AND (olst.commission_action = "add" OR olst.commission_action = "refund")';
            if (!$advanced) {
                $query .= 'AND NOT (olst.commission_action = "add" AND vt.transaction_type = "refund") ';
                $query .= 'AND NOT (olst.commission_action = "refund" AND vt.transaction_type = "commission")';
            }
        } else {
            $query .= ' AND vt.transaction_type = "commission"';
            $query .= ' AND olst.commission_action = "add"';
        }


        if ($id_vendor) {
            $query .= ' AND vod.id_vendor = ' . (int)$id_vendor;
        }

        if ($date_from) {
            $query .= ' AND DATE(o.date_add) >= "' . pSQL($date_from) . '"';
        }
        if ($date_to) {
            $query .= ' AND DATE(o.date_add) <= "' . pSQL($date_to) . '"';
        }

        if ($status_filter) {
            $query .= ' HAVING status_type_id = ' . (int)$status_filter;
        }

        $query .= ' ORDER BY v.shop_name, o.date_add DESC ';

        return Db::getInstance()->executeS($query);
    }
    /**
     * AJAX handler for filtered transactions - FIXED VERSION
     */
    public function ajaxProcessGetFilteredTransactions()
    {
        $id_vendor = Tools::getValue('id_vendor') ? (int)Tools::getValue('id_vendor') : null;
        $status_filter = Tools::getValue('status_filter') ? (int)Tools::getValue('status_filter') : null;
        $date_from = Tools::getValue('date_from') ? pSQL(Tools::getValue('date_from')) : null;
        $date_to = Tools::getValue('date_to') ? pSQL(Tools::getValue('date_to')) : null;
        $include_refunds = Tools::getValue('include_refunds') ? (bool)Tools::getValue('include_refunds') : false;
        $advanced = Tools::getValue('advanced') ? (bool)Tools::getValue('advanced') : false;

        // Log the request for debugging
        PrestaShopLogger::addLog('AJAX getFilteredTransactions called - Vendor: ' . $id_vendor . ', Status: ' . $status_filter . ', Date From: ' . $date_from . ', Date To: ' . $date_to . ', Include Refunds: ' . ($include_refunds ? 'Yes' : 'No'), 1, null, 'AdminVendorPaymentsController');

        try {
            if (!$id_vendor) {
                die(json_encode([
                    'success' => false,
                    'message' => $this->l('Vendor ID is required'),
                    'count' => 0
                ]));
            }

            $transactions = $this->getAllPendingTransactions($id_vendor, $status_filter, $date_from, $date_to, $include_refunds, $advanced);
            $html = '';

            if ($transactions && count($transactions) > 0) {
                foreach ($transactions as $transaction) {
                    $html .= $this->renderTransactionRow($transaction, $advanced);
                }
            }

            die(json_encode([
                'success' => true,
                'html' => $html,
                'count' => count($transactions),
                'reference' => VendorPayment::generateReference($id_vendor),
                'debug' => [
                    'vendor_id' => $id_vendor,
                    'status_filter' => $status_filter,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'include_refunds' => $include_refunds,
                    'transaction_count' => count($transactions)
                ]
            ]));
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error in ajaxProcessGetFilteredTransactions: ' . $e->getMessage(), 3, null, 'AdminVendorPaymentsController');
            die(json_encode([
                'success' => false,
                'message' => $this->l('Error loading transactions: ') . $e->getMessage()
            ]));
        }
    }
    /**
     * Render individual transaction row
     */
    protected function renderTransactionRow($transaction, $advanced = false)
    {
        $this->context->smarty->assign([
            'advanced' => $advanced,
            'transaction' => $transaction,
            'currency' => $this->context->currency
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'multivendor/views/templates/admin/transaction/transaction_row.tpl'
        );
    }
    /**
     * Process form submission for new payments
     */
    public function processAdd()
    {
        $selectedTransactions = Tools::getValue('selected_order_details');
        $selectedVendor = (int)Tools::getValue('id_vendor');

        if (empty($selectedTransactions)) {
            $this->errors[] = $this->l('Please select at least one transaction for payment.');
            return false;
        }

        if (!$selectedVendor) {
            $this->errors[] = $this->l('Please select a vendor for payment.');
            return false;
        }

        // Calculate total and validate transactions belong to selected vendor
        $totalAmount = 0;
        $validTransactions = [];

        foreach ($selectedTransactions as $transactionId) {
            $transaction = Db::getInstance()->getRow(
                '
                SELECT vt.*, vod.id_vendor 
                FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
                INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
                WHERE vt.id_vendor_transaction = ' . (int)$transactionId . '
                AND vt.status = "pending" 
                AND vt.id_vendor_payment = 0
                AND vod.id_vendor = ' . (int)$selectedVendor
            );

            if ($transaction) {
                $totalAmount += (float)$transaction['vendor_amount'];
                $validTransactions[] = $transaction;
            } else {
                $this->errors[] = $this->l('Invalid transaction selected or transaction does not belong to selected vendor.');
                return false;
            }
        }

        if (empty($validTransactions)) {
            $this->errors[] = $this->l('No valid transactions selected for the chosen vendor.');
            return false;
        }

        // Set the calculated amount
        $_POST['amount'] = $totalAmount;

        $result = parent::processAdd();

        if ($result && $this->object && $this->object->id) {
            // Update selected transactions
            $transactionIds = array_column($validTransactions, 'id_vendor_transaction');
            $this->markTransactionsAsPaid($transactionIds, $this->object->id);
        }

        return $result;
    }

    /**
     * Process form submission for updating payments
     */
    public function processUpdate()
    {
        if (parent::processUpdate()) {
            $paymentId = (int)Tools::getValue($this->identifier);
            $paymentObj = new VendorPayment($paymentId);
            return $paymentObj->save();
        }
    }

    /**
     * Mark transactions as paid
     */
    protected function markTransactionsAsPaid($transactionIds, $paymentId)
    {
        if (empty($transactionIds)) {
            return false;
        }

        foreach ($transactionIds as $id) {
            $transaction = new VendorTransaction($id);
            $transaction->status = 'paid';
            $transaction->id_vendor_payment = (int)$paymentId;
            $transaction->save();
        }
        return true;
    }

    /**
     * Render view for payment details
     */
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
            'currency' => $this->context->currency,
            'print_url' => self::$currentIndex . '&' . $this->identifier . '=' . $this->id_object . '&printpayment&token=' . $this->token
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'multivendor/views/templates/admin/payment_view.tpl'
        );
    }

    /**
     * Get payment transaction details
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

    /**
     * Bulk action: Mark as completed
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
}
