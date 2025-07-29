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

        // Add both view and edit actions
        $this->addRowAction('view');
        $this->addRowAction('edit');

        $this->bulk_actions = [
            'markCompleted' => [
                'text' => $this->l('Mark as Completed'),
                'confirm' => $this->l('Mark selected payments as completed?')
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
            _PS_MODULE_DIR_ . 'multivendor/views/templates/admin/payment_print.tpl'
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
            ['id' => 'other', 'name' => $this->l('Other')]
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
                    'disabled' => $this->object->id ? true : false, // Disable vendor change in edit mode
                    // Use unified vendor selection function
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

        // Add other payment fields
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

        $this->fields_form['input'][] = [
            'type' => 'text',
            'label' => $this->l('Reference'),
            'name' => 'reference',
            'hint' => $this->l('Transaction ID, Check Number, etc.')
        ];

        $this->fields_form['input'][] = [
            'type' => 'textarea',
            'label' => $this->l('Notes'),
            'name' => 'notes',
            'rows' => 3
        ];

        return parent::renderForm();
    }

    protected function renderUnifiedTransactionTable()
    {
        // Get all order line status types for filtering
        $statusTypes = $this->getAllOrderLineStatusTypes();

        $html = '<div class="form-group">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">' . $this->l('Vendor Transactions') . '</h3>
                </div>
                <div class="panel-body">
                    <!-- Status Filter (only visible when vendor is selected) -->
                    <div id="status-filter-section" class="row" style="margin-bottom: 15px; display: none;">
                        <div class="col-md-6">
                            <label for="status-filter">' . $this->l('Filter by Status') . ':</label>
                            <select id="status-filter" class="form-control">
                                <option value="">' . $this->l('All Statuses') . '</option>';

        foreach ($statusTypes as $status) {
            $html .= '<option value="' . (int)$status['id_order_line_status_type'] . '">' . htmlspecialchars($status['name']) . '</option>';
        }

        $html .= '          </select>
                        </div>
                        <div class="col-md-6">
                            <button type="button" id="clear-status-filter" class="btn btn-default" style="margin-top: 25px;">
                                <i class="icon-remove"></i> ' . $this->l('Clear Filter') . '
                            </button>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div id="vendor-selection-message" class="alert alert-info">
                        <i class="icon-info-circle"></i> ' . $this->l('Please select a vendor above to view their pending transactions.') . '
                    </div>

                    <!-- Loading indicator -->
                    <div id="loading-indicator" style="display: none; text-align: center; padding: 20px;">
                        <i class="icon-spinner icon-spin"></i> ' . $this->l('Loading transactions...') . '
                    </div>

                    <!-- Transactions table -->
                    <div id="transactions-container" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" id="select-all" title="' . $this->l('Select All') . '">
                                        </th>
                                        <th>' . $this->l('Order') . '</th>
                                        <th>' . $this->l('Order Detail ID') . '</th>
                                        <th>' . $this->l('Product') . '</th>
                                        <th>' . $this->l('Reference (Qty)') . '</th>
                                        <th>' . $this->l('Amount') . '</th>
                                        <th>' . $this->l('Status') . '</th>
                                        <th>' . $this->l('Date') . '</th>
                                    </tr>
                                </thead>
                                <tbody id="transactions-tbody">
                                    <!-- Dynamic content -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <strong>' . $this->l('Selection Summary') . ':</strong><br>
                                    <span id="selected-count">0</span> ' . $this->l('transactions selected') . ' - 
                                    <strong>' . $this->l('Total') . ':</strong> <span id="selected-total">0.00</span> ' . $this->context->currency->sign . '
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No transactions message -->
                    <div id="no-transactions-message" class="alert alert-warning" style="display: none;">
                        <i class="icon-warning-sign"></i> ' . $this->l('No pending transactions found for the selected vendor.') . '
                    </div>
                </div>
            </div>
        </div>
    </div>';

        $html .= $this->getUnifiedTransactionJavaScript();

        return $html;
    }

    protected function getUnifiedTransactionJavaScript()
    {
        return '<script type="text/javascript">
        $(document).ready(function() {
            // Initialize form state
            var selectedVendor = null;
            
            // Status filter change
            $("#status-filter").on("change", function() {
                if (selectedVendor) {
                    loadVendorTransactions(selectedVendor, $(this).val());
                }
            });
            
            // Clear status filter
            $("#clear-status-filter").click(function() {
                $("#status-filter").val("");
                if (selectedVendor) {
                    loadVendorTransactions(selectedVendor, "");
                }
            });
            
            $("#select-all").change(function() {
                $(".transaction-checkbox:visible").prop("checked", this.checked);
                updateTotals();
            });
            
            // Individual checkbox change
            $(document).on("change", ".transaction-checkbox", function() {
                updateTotals();
                updateSelectAllState();
            });
            
            function updateSelectAllState() {
                var totalCheckboxes = $(".transaction-checkbox:visible").length;
                var checkedCheckboxes = $(".transaction-checkbox:visible:checked").length;
                
                if (totalCheckboxes === 0) {
                    $("#select-all").prop("indeterminate", false).prop("checked", false);
                } else if (checkedCheckboxes === totalCheckboxes) {
                    $("#select-all").prop("indeterminate", false).prop("checked", true);
                } else if (checkedCheckboxes > 0) {
                    $("#select-all").prop("indeterminate", true);
                } else {
                    $("#select-all").prop("indeterminate", false).prop("checked", false);
                }
            }
            
            function loadVendorTransactions(vendorId, statusFilter) {
                statusFilter = statusFilter || "";
                
                // Show loading
                $("#loading-indicator").show();
                $("#transactions-container, #no-transactions-message").hide();
                
                $.ajax({
                    url: "' . self::$currentIndex . '",
                    type: "POST",
                    data: {
                        ajax: 1,
                        action: "getFilteredTransactions",
                        id_vendor: vendorId,
                        status_filter: statusFilter,
                        token: "' . $this->token . '"
                    },
                    dataType: "json",
                    success: function(response) {
                        $("#loading-indicator").hide();
                        
                        if (response.success) {
                            if (response.count > 0) {
                                $("#transactions-tbody").html(response.html);
                                $("#transactions-container").show();
                                $("#status-filter-section").show();
                            } else {
                                $("#no-transactions-message").show();
                                $("#status-filter-section").hide();
                            }
                            updateTotals();
                            updateSelectAllState();
                        } else {
                            $("#no-transactions-message").show();
                            $("#status-filter-section").hide();
                            console.error("Error loading transactions:", response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        $("#loading-indicator").hide();
                        $("#no-transactions-message").show();
                        $("#status-filter-section").hide();
                        console.error("AJAX error:", error);
                    }
                });
            }
            
            function updateTotals() {
                var selectedCheckboxes = $(".transaction-checkbox:checked");
                var count = selectedCheckboxes.length;
                var total = 0;
                
                selectedCheckboxes.each(function() {
                    total += parseFloat($(this).data("amount")) || 0;
                });
                
                $("#selected-count").text(count);
                $("#selected-total").text(total.toFixed(2));
                
                // Update the main amount field
                $("input[name=\'amount\']").val(total.toFixed(2));
                
                // Enable/disable form submission based on selection
                var submitButton = $("button[name=\'submitAddvendor_payment\'], input[name=\'submitAddvendor_payment\']");
                if (count > 0) {
                    submitButton.prop("disabled", false).removeClass("disabled");
                } else {
                    submitButton.prop("disabled", true).addClass("disabled");
                }
            }
            
            updateTotals();
        });
        
        function onVendorSelectionChange(vendorId) {
            if (vendorId && vendorId !== "") {
                selectedVendor = parseInt(vendorId);
                
                // Hide instruction message
                $("#vendor-selection-message").hide();
                
                // Clear status filter
                $("#status-filter").val("");
                
                loadVendorTransactions(selectedVendor, "");
            } else {
                selectedVendor = null;
                
                $("#vendor-selection-message").show();
                $("#transactions-container, #no-transactions-message, #status-filter-section").hide();
                
                // Clear totals
                $("#selected-count").text("0");
                $("#selected-total").text("0.00");
                $("input[name=\'amount\']").val("0.00");
                
                // Disable submit button
                var submitButton = $("button[name=\'submitAddvendor_payment\'], input[name=\'submitAddvendor_payment\']");
                submitButton.prop("disabled", true).addClass("disabled");
            }
        }
        
        // Make loadVendorTransactions globally accessible
        window.loadVendorTransactions = function(vendorId, statusFilter) {
            statusFilter = statusFilter || "";
            
            // Show loading
            $("#loading-indicator").show();
            $("#transactions-container, #no-transactions-message").hide();
            
            $.ajax({
                url: "' . self::$currentIndex . '",
                type: "POST",
                data: {
                    ajax: 1,
                    action: "getFilteredTransactions",
                    id_vendor: vendorId,
                    status_filter: statusFilter,
                    token: "' . $this->token . '"
                },
                dataType: "json",
                success: function(response) {
                    $("#loading-indicator").hide();
                    
                    if (response.success) {
                        if (response.count > 0) {
                            $("#transactions-tbody").html(response.html);
                            $("#transactions-container").show();
                            $("#status-filter-section").show();
                        } else {
                            $("#no-transactions-message").show();
                            $("#status-filter-section").hide();
                        }
                        
                        // Update totals and checkbox states
                        $("#selected-count").text("0");
                        $("#selected-total").text("0.00");
                        $("input[name=\'amount\']").val("0.00");
                        $("#select-all").prop("checked", false).prop("indeterminate", false);
                        
                        // Disable submit button until transactions are selected
                        var submitButton = $("button[name=\'submitAddvendor_payment\'], input[name=\'submitAddvendor_payment\']");
                        submitButton.prop("disabled", true).addClass("disabled");
                    } else {
                        $("#no-transactions-message").show();
                        $("#status-filter-section").hide();
                        console.error("Error loading transactions:", response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $("#loading-indicator").hide();
                    $("#no-transactions-message").show();
                    $("#status-filter-section").hide();
                    console.error("AJAX error:", error);
                }
            });
        };
    </script>';
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

        $html = '<div class="form-group">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">' . $this->l('Current Payment Transactions') . '</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>' . $this->l('Order') . '</th>
                                <th>' . $this->l('Order Detail ID') . '</th>
                                <th>' . $this->l('Product') . '</th>
                                <th>' . $this->l('Product Reference (Qty)') . '</th>
                                <th>' . $this->l('Amount') . '</th>
                                <th>' . $this->l('Transaction Date') . '</th>
                                <th>' . $this->l('Actions') . '</th>
                            </tr>
                        </thead>
                        <tbody>';

        if ($transactionDetails) {
            foreach ($transactionDetails as $transaction) {
                $html .= '<tr id="transaction-row-' . (int)$transaction['id_vendor_transaction'] . '">
                    <td>#' . htmlspecialchars($transaction['order_reference']) . '</td>
                    <td>' . (int)$transaction['id_order_detail'] . '</td>
                    <td>' . htmlspecialchars($transaction['product_name']) . '</td>
                    <td>' . htmlspecialchars($transaction['product_reference']) . ' (' . (int)$transaction['product_quantity'] . ')</td>
                    <td>' . number_format($transaction['vendor_amount'], 2) . ' ' . $this->context->currency->sign . '</td>
                    <td>' . date('d/m/Y H:i', strtotime($transaction['transaction_date'])) . '</td>
                    <td>
                        <button type="button" 
                                class="btn btn-danger btn-xs remove-transaction" 
                                data-transaction-id="' . (int)$transaction['id_vendor_transaction'] . '"
                                data-amount="' . (float)$transaction['vendor_amount'] . '">
                            <i class="icon-trash"></i> ' . $this->l('Remove') . '
                        </button>
                    </td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="7" class="text-center">' . $this->l('No transactions found for this payment') . '</td></tr>';
        }

        $html .= '      </tbody>
                    </table>
                    
                    <!-- Add new transactions section -->
                    <div class="alert alert-info">
                        <h4>' . $this->l('Add More Transactions') . '</h4>
                        <!-- Status Filter for available transactions -->
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-6">
                                <label for="available-status-filter">' . $this->l('Filter by Status') . ':</label>
                                <select id="available-status-filter" class="form-control">
                                    <option value="">' . $this->l('All Statuses') . '</option>';

        $statusTypes = $this->getAllOrderLineStatusTypes();
        foreach ($statusTypes as $status) {
            $html .= '<option value="' . (int)$status['id_order_line_status_type'] . '">' . htmlspecialchars($status['name']) . '</option>';
        }

        $html .= '              </select>
                            </div>
                            <div class="col-md-6">
                                <button type="button" id="clear-available-status-filter" class="btn btn-default" style="margin-top: 25px;">
                                    <i class="icon-remove"></i> ' . $this->l('Clear Filter') . '
                                </button>
                            </div>
                        </div>
                        <button type="button" id="show-available-transactions" class="btn btn-primary">
                            <i class="icon-plus"></i> ' . $this->l('Add Transactions') . '
                        </button>
                    </div>
                    
                    <!-- Available transactions (hidden by default) -->
                    <div id="available-transactions" style="display: none;">
                        <h4>' . $this->l('Available Pending Transactions') . '</h4>
                        <div id="pending-transactions-container">
                            <!-- Will be loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

        $html .= $this->getEditTransactionJavaScript();

        return $html;
    }

    /**
     * JavaScript for edit mode transaction management
     */
    protected function getEditTransactionJavaScript()
    {
        return '<script type="text/javascript">
        $(document).ready(function() {
            // Remove transaction from payment
            $(document).on("click", ".remove-transaction", function() {
                var transactionId = $(this).data("transaction-id");
                var amount = parseFloat($(this).data("amount"));
                
                if (confirm("' . $this->l('Are you sure you want to remove this transaction from the payment?') . '")) {
                    $.ajax({
                        url: "' . self::$currentIndex . '",
                        type: "POST",
                        data: {
                            ajax: 1,
                            action: "removeTransactionFromPayment",
                            transaction_id: transactionId,
                            payment_id: ' . (int)$this->object->id . ',
                            token: "' . $this->token . '"
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                $("#transaction-row-" + transactionId).remove();
                                updatePaymentAmount(-amount);
                                showSuccessMessage("' . $this->l('Transaction removed successfully') . '");
                            } else {
                                alert(response.message || "' . $this->l('Error removing transaction') . '");
                            }
                        }
                    });
                }
            });
            
            // Show available transactions
            $("#show-available-transactions").click(function() {
                $("#available-transactions").toggle();
                if ($("#available-transactions").is(":visible")) {
                    loadAvailableTransactions();
                }
            });
            
            // Status filter for available transactions
            $("#available-status-filter").on("change", function() {
                if ($("#available-transactions").is(":visible")) {
                    loadAvailableTransactionsWithFilter();
                }
            });
            
            // Clear available status filter
            $("#clear-available-status-filter").click(function() {
                $("#available-status-filter").val("");
                if ($("#available-transactions").is(":visible")) {
                    loadAvailableTransactionsWithFilter();
                }
            });
            
            function loadAvailableTransactionsWithFilter() {
                var statusFilter = $("#available-status-filter").val();
                $.ajax({
                    url: "' . self::$currentIndex . '",
                    type: "POST",
                    data: {
                        ajax: 1,
                        action: "getAvailableTransactions",
                        vendor_id: ' . (int)($this->object->id_vendor ?? 0) . ',
                        payment_id: ' . (int)$this->object->id . ',
                        status_filter: statusFilter,
                        token: "' . $this->token . '"
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            $("#pending-transactions-container").html(response.html);
                        }
                    }
                });
            }
            
            function loadAvailableTransactions() {
                loadAvailableTransactionsWithFilter();
            }
            
            // Add transaction to payment
            $(document).on("click", ".add-transaction", function() {
                var transactionId = $(this).data("transaction-id");
                var amount = parseFloat($(this).data("amount"));
                
                $.ajax({
                    url: "' . self::$currentIndex . '",
                    type: "POST",
                    data: {
                        ajax: 1,
                        action: "addTransactionToPayment",
                        transaction_id: transactionId,
                        payment_id: ' . (int)$this->object->id . ',
                        token: "' . $this->token . '"
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // Reload to show updated data
                        } else {
                            alert(response.message || "' . $this->l('Error adding transaction') . '");
                        }
                    }
                });
            });
            
            function updatePaymentAmount(change) {
                var currentAmount = parseFloat($("input[name=\'amount\']").val()) || 0;
                var newAmount = currentAmount + change;
                $("input[name=\'amount\']").val(newAmount.toFixed(2));
            }
            
            function showSuccessMessage(message) {
                var alert = $("<div class=\"alert alert-success\">" + message + "</div>");
                $(".panel-body").prepend(alert);
                setTimeout(function() { alert.fadeOut(); }, 3000);
            }
        });
    </script>';
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
            // Get transaction details
            $transaction = Db::getInstance()->getRow(
                'SELECT * FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction 
                 WHERE id_vendor_transaction = ' . (int)$transaction_id . '
                 AND id_vendor_payment = ' . (int)$payment_id
            );

            if (!$transaction) {
                die(json_encode(['success' => false, 'message' => $this->l('Transaction not found')]));
            }

            // Remove transaction from payment
            $result = Db::getInstance()->update(
                'mv_vendor_transaction',
                [
                    'status' => 'pending',
                    'id_vendor_payment' => 0
                ],
                'id_vendor_transaction = ' . (int)$transaction_id
            );

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

        if (!$vendor_id) {
            die(json_encode(['success' => false, 'message' => $this->l('Invalid vendor ID')]));
        }

        $transactions = $this->getAllPendingTransactions($vendor_id, $status_filter);
        $html = '';

        if ($transactions) {
            $html .= '<table class="table table-striped">
                <thead>
                    <tr>
                        <th>' . $this->l('Order') . '</th>
                        <th>' . $this->l('Order Detail ID') . '</th>
                        <th>' . $this->l('Product') . '</th>
                        <th>' . $this->l('Amount') . '</th>
                        <th>' . $this->l('Status') . '</th>
                        <th>' . $this->l('Actions') . '</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($transactions as $transaction) {
                $html .= '<tr>
                    <td>#' . htmlspecialchars($transaction['order_reference']) . '</td>
                    <td>' . (int)$transaction['id_order_detail'] . '</td>
                    <td>' . htmlspecialchars($transaction['product_name']) . '</td>
                    <td>' . number_format($transaction['vendor_amount'], 2) . ' ' . $this->context->currency->sign . '</td>
                    <td><span class="badge" style="background-color: ' . htmlspecialchars($transaction['status_color']) . '">' . htmlspecialchars($transaction['status_name']) . '</span></td>
                    <td>
                        <button type="button" 
                                class="btn btn-success btn-xs add-transaction"
                                data-transaction-id="' . (int)$transaction['id_vendor_transaction'] . '"
                                data-amount="' . (float)$transaction['vendor_amount'] . '">
                            <i class="icon-plus"></i> ' . $this->l('Add') . '
                        </button>
                    </td>
                </tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html = '<div class="alert alert-info">' . $this->l('No pending transactions available') . '</div>';
        }

        die(json_encode(['success' => true, 'html' => $html]));
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

            // Add transaction to payment
            $result = Db::getInstance()->update(
                'mv_vendor_transaction',
                [
                    'status' => 'paid',
                    'id_vendor_payment' => (int)$payment_id
                ],
                'id_vendor_transaction = ' . (int)$transaction_id
            );

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
            Db::getInstance()->update(
                'mv_vendor_payment',
                ['amount' => (float)$total],
                'id_vendor_payment = ' . (int)$payment_id
            );
        }
    }

    /**
     * Get all order line status types for filtering
     */
    protected function getAllOrderLineStatusTypes()
    {
        $query = 'SELECT id_order_line_status_type, name, color 
              FROM ' . _DB_PREFIX_ . 'mv_order_line_status_type 
              WHERE active = 1 
              ORDER BY position ASC';

        return Db::getInstance()->executeS($query);
    }

    /**
     * Get all pending transactions from mv_vendor_transaction
     */
    protected function getAllPendingTransactions($id_vendor = null, $status_filter = null)
    {
        $defaultStatusTypeId = OrderLineStatus::getDefaultStatusTypeId();
        $defaultStatusType = new OrderLineStatusType($defaultStatusTypeId);

        $query = '
        SELECT 
            vt.id_vendor_transaction,
            vt.order_detail_id as id_order_detail,
            vt.vendor_amount,
            vod.id_vendor,
            vod.product_name,
            vod.product_reference,
            vod.product_quantity,
            o.reference as order_reference,
            o.date_add as order_date,
            v.shop_name as vendor_name,
            COALESCE(olst.id_order_line_status_type, ' . (int)$defaultStatusTypeId . ') as status_type_id,
            COALESCE(olst.name, "' . pSQL($defaultStatusType->name) . '") as status_name,
            COALESCE(olst.color, "' . pSQL($defaultStatusType->color) . '") as status_color
        FROM ' . _DB_PREFIX_ . 'mv_vendor_transaction vt
        INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor_order_detail vod ON vod.id_order_detail = vt.order_detail_id
        INNER JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = vod.id_order
        INNER JOIN ' . _DB_PREFIX_ . 'mv_vendor v ON v.id_vendor = vod.id_vendor
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status ols ON ols.id_order_detail = vod.id_order_detail AND ols.id_vendor = vod.id_vendor
        LEFT JOIN ' . _DB_PREFIX_ . 'mv_order_line_status_type olst ON olst.id_order_line_status_type = ols.id_order_line_status_type
        WHERE vt.status = "pending"
        AND vt.id_vendor_payment = 0
        AND vt.transaction_type = "commission"';

        if ($id_vendor) {
            $query .= ' AND vod.id_vendor = ' . (int)$id_vendor;
        }

        if ($status_filter) {
            $query .= ' AND COALESCE(olst.id_order_line_status_type, ' . (int)$defaultStatusTypeId . ') = ' . (int)$status_filter;
        }

        $query .= ' ORDER BY v.shop_name, o.date_add DESC';

        return Db::getInstance()->executeS($query);
    }

    /**
     * AJAX handler for filtered transactions
     */
    public function ajaxProcessGetFilteredTransactions()
    {
        $id_vendor = Tools::getValue('id_vendor') ? (int)Tools::getValue('id_vendor') : null;
        $status_filter = Tools::getValue('status_filter') ? (int)Tools::getValue('status_filter') : null;

        try {
            $transactions = $this->getAllPendingTransactions($id_vendor, $status_filter);
            $html = '';

            if ($transactions) {
                foreach ($transactions as $transaction) {
                    $html .= $this->renderTransactionRow($transaction);
                }
            }

            die(json_encode([
                'success' => true,
                'html' => $html,
                'count' => count($transactions)
            ]));
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error in ajaxProcessGetFilteredTransactions: ' . $e->getMessage(), 3);
            die(json_encode(['success' => false, 'message' => $this->l('Error loading transactions')]));
        }
    }

    /**
     * Render individual transaction row
     */
    protected function renderTransactionRow($transaction)
    {
        return '<tr>
        <td>
            <input type="checkbox" 
                   name="selected_order_details[]" 
                   value="' . (int)$transaction['id_vendor_transaction'] . '"
                   data-amount="' . (float)$transaction['vendor_amount'] . '"
                   data-vendor="' . (int)$transaction['id_vendor'] . '"
                   class="transaction-checkbox">
        </td>
        <td>#' . htmlspecialchars($transaction['order_reference']) . '</td>
        <td>' . (int)$transaction['id_order_detail'] . '</td>
        <td>' . htmlspecialchars($transaction['product_name']) . '</td>
        <td>' . htmlspecialchars($transaction['product_reference']) . ' (' . (int)$transaction['product_quantity'] . ')</td>
        <td class="text-right"><strong>' . number_format($transaction['vendor_amount'], 2) . ' ' . $this->context->currency->sign . '</strong></td>
        <td><span class="badge" style="background-color: ' . htmlspecialchars($transaction['status_color']) . '">' . htmlspecialchars($transaction['status_name']) . '</span></td>
        <td>' . date('d/m/Y', strtotime($transaction['order_date'])) . '</td>
    </tr>';
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
        // For existing payments, we don't need to handle transaction selection
        // as it's handled via AJAX calls for add/remove transactions
        return parent::processUpdate();
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
