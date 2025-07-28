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

        $this->_select = 'v.shop_name as vendor_name';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'mv_vendor` v ON (v.id_vendor = a.id_vendor)';
        $this->addRowAction('view');

        $this->bulk_actions = [
            'markCompleted' => [
                'text' => $this->l('Mark as Completed'),
                'confirm' => $this->l('Mark selected payments as completed?')
            ]
        ];
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
            ['id' => 'other', 'name' => $this->l('Other')]
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Payment Information'),
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
                        'name' => 'name'
                    ],
                    'required' => true
                ],
                [
                    'type' => 'html',
                    'name' => 'order_details_table',
                    'html_content' => $this->renderOrderDetailsTable()
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Total Amount'),
                    'name' => 'amount',
                    'required' => true,
                    'suffix' => $this->context->currency->sign,
                    'readonly' => true
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
                'title' => $this->l('Save Payment')
            ]
        ];

        if (!$this->object->id) {
            $this->fields_value['status'] = 'completed';
        }

        return parent::renderForm();
    }

    /**
     * Render order details table with checkboxes
     */
    protected function renderOrderDetailsTable()
    {
        // Get all pending transactions
        $pendingTransactions = $this->getAllPendingTransactions();
        
        $html = '<div class="form-group">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">' . $this->l('Pending Transactions') . '</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"> ' . $this->l('All') . '</th>
                                    <th>' . $this->l('Vendor') . '</th>
                                    <th>' . $this->l('Order') . '</th>
                                    <th>' . $this->l('Product') . '</th>
                                    <th>' . $this->l('Amount') . '</th>
                                    <th>' . $this->l('Date') . '</th>
                                </tr>
                            </thead>
                            <tbody>';

        if ($pendingTransactions) {
            foreach ($pendingTransactions as $transaction) {
                $html .= '<tr>
                    <td>
                        <input type="checkbox" 
                               name="selected_order_details[]" 
                               value="' . (int)$transaction['id_vendor_transaction'] . '"
                               data-amount="' . (float)$transaction['vendor_amount'] . '"
                               data-vendor="' . (int)$transaction['id_vendor'] . '"
                               class="transaction-checkbox">
                    </td>
                    <td>' . htmlspecialchars($transaction['vendor_name']) . '</td>
                    <td>#' . htmlspecialchars($transaction['order_reference']) . '</td>
                    <td>' . htmlspecialchars($transaction['product_name']) . '</td>
                    <td>' . number_format($transaction['vendor_amount'], 2) . ' ' . $this->context->currency->sign . '</td>
                    <td>' . date('d/m/Y', strtotime($transaction['order_date'])) . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="6" class="text-center">' . $this->l('No pending transactions found') . '</td></tr>';
        }

        $html .= '</tbody>
                        </table>
                        <div class="alert alert-info">
                            <strong>' . $this->l('Selected') . ':</strong> <span id="selected-count">0</span> ' . $this->l('transactions') . ' - 
                            <strong>' . $this->l('Total') . ':</strong> <span id="selected-total">0.00</span> ' . $this->context->currency->sign . '
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        $(document).ready(function() {
            // Select all functionality
            $("#select-all").change(function() {
                $(".transaction-checkbox").prop("checked", this.checked);
                updateTotals();
            });
            
            // Individual checkbox change
            $(".transaction-checkbox").change(function() {
                updateTotals();
                
                // Update vendor selection if needed
                var selectedVendor = null;
                var selectedCheckboxes = $(".transaction-checkbox:checked");
                
                if (selectedCheckboxes.length > 0) {
                    selectedVendor = $(selectedCheckboxes[0]).data("vendor");
                    
                    // Check if all selected items are from same vendor
                    var sameVendor = true;
                    selectedCheckboxes.each(function() {
                        if ($(this).data("vendor") !== selectedVendor) {
                            sameVendor = false;
                            return false;
                        }
                    });
                    
                    if (sameVendor) {
                        $("select[name=\'id_vendor\']").val(selectedVendor);
                    }
                }
            });
            
            function updateTotals() {
                var total = 0;
                var count = 0;
                
                $(".transaction-checkbox:checked").each(function() {
                    total += parseFloat($(this).data("amount"));
                    count++;
                });
                
                $("#selected-count").text(count);
                $("#selected-total").text(total.toFixed(2));
                $("input[name=\'amount\']").val(total.toFixed(2));
                
                // Update select all checkbox
                var totalCheckboxes = $(".transaction-checkbox").length;
                $("#select-all").prop("checked", count === totalCheckboxes && count > 0);
            }
        });
        </script>';

        return $html;
    }

    /**
     * Get all pending transactions from mv_vendor_transaction
     */
    protected function getAllPendingTransactions()
    {
        $query = '
            SELECT 
                vt.id_vendor_transaction,
                vt.vendor_amount,
                vod.id_vendor,
                vod.product_name,
                vod.product_quantity,
                o.reference as order_reference,
                o.date_add as order_date,
                v.shop_name as vendor_name
            FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
            INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
            INNER JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor v ON v.id_vendor = vod.id_vendor
            WHERE vt.status = "pending"
            AND vt.id_vendor_payment = 0
            AND vt.transaction_type = "commission"
            ORDER BY v.shop_name, o.date_add DESC
        ';

        return Db::getInstance()->executeS($query);
    }

    /**
     * Process form submission
     */
    public function processAdd()
    {
        $selectedTransactions = Tools::getValue('selected_order_details');
        
        if (empty($selectedTransactions)) {
            $this->errors[] = $this->l('Please select at least one transaction for payment.');
            return false;
        }

        // Calculate total and validate transactions
        $totalAmount = 0;
        $validTransactions = [];
        
        foreach ($selectedTransactions as $transactionId) {
            $transaction = Db::getInstance()->getRow('
                SELECT vt.*, vod.id_vendor 
                FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
                INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
                WHERE vt.id_vendor_transaction = ' . (int)$transactionId . '
                AND vt.status = "pending" 
                AND vt.id_vendor_payment = 0'
            );
            
            if ($transaction) {
                $totalAmount += (float)$transaction['vendor_amount'];
                $validTransactions[] = $transaction;
            }
        }

        if (empty($validTransactions)) {
            $this->errors[] = $this->l('No valid transactions selected.');
            return false;
        }

        // Set the calculated amount
        $_POST['amount'] = $totalAmount;
        
        // Set vendor from first transaction if not set
        if (!Tools::getValue('id_vendor') && !empty($validTransactions)) {
            $_POST['id_vendor'] = $validTransactions[0]['id_vendor'];
        }

        $result = parent::processAdd();
        
        if ($result && $this->object && $this->object->id) {
            // Update selected transactions
            $transactionIds = array_column($validTransactions, 'id_vendor_transaction');
            $this->markTransactionsAsPaid($transactionIds, $this->object->id);
        }

        return $result;
    }

    /**
     * Mark transactions as paid
     */
    protected function markTransactionsAsPaid($transactionIds, $paymentId)
    {
        if (empty($transactionIds)) {
            return false;
        }

        return Db::getInstance()->update(
            'mv_vendor_transaction',
            [
                'status' => 'paid',
                'id_vendor_payment' => (int)$paymentId
            ],
            'id_vendor_transaction IN (' . implode(',', array_map('intval', $transactionIds)) . ')'
        );
    }

    // ... rest of your existing methods (renderView, processBulk, etc.)
    
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

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'multivendor/views/templates/admin/payment_view.tpl'
        );
    }

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
                o.date_add as order_date
            FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
            LEFT JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
            WHERE vt.id_vendor_payment = ' . (int)$id_vendor_payment . '
            AND vt.transaction_type = "commission"
            ORDER BY o.date_add DESC
        ';

        return Db::getInstance()->executeS($query);
    }
}