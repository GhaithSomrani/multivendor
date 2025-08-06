{*
* Admin Vendor Payments - Current Transactions Table Template
* File: views/templates/admin/current_transactions_table.tpl
*}

<div class="form-group">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{l s='Current Payment Transactions' mod='multivendor'}</h3>
            </div>
            <div class="panel-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{l s='Order' mod='multivendor'}</th>
                            <th>{l s='Order Detail ID' mod='multivendor'}</th>
                            <th>{l s='Product' mod='multivendor'}</th>
                            <th>{l s='Product Reference (Qty)' mod='multivendor'}</th>
                            <th>{l s='Amount' mod='multivendor'}</th>
                            <th>{l s='Transaction Date' mod='multivendor'}</th>
                            <th>{l s='Actions' mod='multivendor'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $transaction_details && count($transaction_details) > 0}
                            {foreach from=$transaction_details item=transaction}
                                <tr id="transaction-row-{$transaction.id_vendor_transaction|intval}">
                                    <td>#{$transaction.order_reference|escape:'htmlall':'UTF-8'}</td>
                                    <td>{$transaction.id_order_detail|intval}</td>
                                    <td>{$transaction.product_name|escape:'htmlall':'UTF-8'}</td>
                                    <td>{$transaction.product_reference|escape:'htmlall':'UTF-8'}
                                        ({$transaction.product_quantity|intval})</td>
                                    <td>{$transaction.vendor_amount|number_format:2} {$currency_sign|escape:'htmlall':'UTF-8'}
                                    </td>
                                    <td>{$transaction.transaction_date|date_format:'%d/%m/%Y %H:%M'}</td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-xs remove-transaction"
                                            data-transaction-id="{$transaction.id_vendor_transaction|intval}"
                                            data-amount="{$transaction.vendor_amount|floatval}">
                                            <i class="icon-trash"></i> {l s='Remove' mod='multivendor'}
                                        </button>
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="7" class="text-center">
                                    {l s='No transactions found for this payment' mod='multivendor'}</td>
                            </tr>
                        {/if}
                    </tbody>
                </table>

                <!-- Add new transactions section -->
                <div class="alert alert-info">
                    <h4>{l s='Add More Transactions' mod='multivendor'}</h4>

                    <!-- Filters for available transactions -->
                    <div class="row" style="margin-bottom: 15px;">
                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <label for="available-status-filter">{l s='Filter by Status' mod='multivendor'}:</label>
                            <select id="available-status-filter" class="form-control">
                                <option value="">{l s='All Statuses' mod='multivendor'}</option>
                                {foreach from=$status_types item=status}
                                    <option value="{$status.id_order_line_status_type|intval}">
                                        {$status.name|escape:'htmlall':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </div>

                        <!-- Date From Filter -->
                        <div class="col-md-3">
                            <label for="available-date-from">{l s='From Date' mod='multivendor'}:</label>
                            <input type="date" id="available-date-from" class="form-control" />
                        </div>

                        <!-- Date To Filter -->
                        <div class="col-md-3">
                            <label for="available-date-to">{l s='To Date' mod='multivendor'}:</label>
                            <input type="date" id="available-date-to" class="form-control" />
                        </div>

                        <!-- Clear Button -->
                        <div class="col-md-3">
                            <button type="button" id="clear-available-filters" class="btn btn-default"
                                style="margin-top: 25px;">
                                <i class="icon-remove"></i> {l s='Clear' mod='multivendor'}
                            </button>
                        </div>
                    </div>

                    <!-- Available transactions container -->
                    <div id="available-transactions-container">
                        {l s='Loading available transactions...' mod='multivendor'}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var currentVendorId = $('#id_vendor').val();
        var transactionsByVendor = {literal}{}{/literal};

        // Select all checkbox handler
        $('#select-all').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('input[name="selected_order_details[]"]').prop('checked', isChecked);
            calculateTotals();
        });

        // Individual checkbox handler
        $(document).on('change', 'input[name="selected_order_details[]"]', function() {
            calculateTotals();
            updateSelectAllState();
        });

        // Status filter handler
        $('#status-filter').on('change', function() {
            var statusFilter = $(this).val();
            var dateFrom = $('#date-from-filter').val();
            var dateTo = $('#date-to-filter').val();
            loadTransactions(currentVendorId, statusFilter, dateFrom, dateTo);
        });

        // Date filter handlers
        $('#date-from-filter, #date-to-filter').on('change', function() {
            var statusFilter = $('#status-filter').val();
            var dateFrom = $('#date-from-filter').val();
            var dateTo = $('#date-to-filter').val();
            loadTransactions(currentVendorId, statusFilter, dateFrom, dateTo);
        });

        // Clear all filters button
        $('#clear-filters').on('click', function() {
            $('#status-filter').val('');
            $('#date-from-filter').val('');
            $('#date-to-filter').val('');
            loadTransactions(currentVendorId, null, null, null);
        });

        // Legacy clear status filter button (maintain backward compatibility)
        $('#clear-status-filter').on('click', function() {
            $('#status-filter').val('');
            var dateFrom = $('#date-from-filter').val();
            var dateTo = $('#date-to-filter').val();
            loadTransactions(currentVendorId, null, dateFrom, dateTo);
        });

        // Available transactions filters (for add payment context)
        $('#available-status-filter, #available-date-from, #available-date-to').on('change', function() {
            loadAvailableTransactions();
        });

        // Clear available filters
        $('#clear-available-filters').on('click', function() {
            $('#available-status-filter').val('');
            $('#available-date-from').val('');
            $('#available-date-to').val('');
            loadAvailableTransactions();
        });

        // Calculate totals
        function calculateTotals() {
            var selectedCount = 0;
            var selectedTotal = 0;

            $('input[name="selected_order_details[]"]:checked').each(function() {
                selectedCount++;
                selectedTotal += parseFloat($(this).data('amount')) || 0;
            });

            $('#selected-count').text(selectedCount);
            $('#selected-total').text(selectedTotal.toFixed(2));
            $('input[name="amount"]').val(selectedTotal.toFixed(2));

            // Enable/disable submit button
            var submitButton = $(
                "button[name='submitAddvendor_payment'], input[name='submitAddvendor_payment']");
            if (selectedCount > 0) {
                submitButton.prop('disabled', false).removeClass('disabled');
            } else {
                submitButton.prop('disabled', true).addClass('disabled');
            }
        }

        // Update select all checkbox state
        function updateSelectAllState() {
            var allCheckboxes = $('input[name="selected_order_details[]"]');
            var checkedCheckboxes = $('input[name="selected_order_details[]"]:checked');

            if (checkedCheckboxes.length === 0) {
                $('#select-all').prop('checked', false).prop('indeterminate', false);
            } else if (checkedCheckboxes.length === allCheckboxes.length) {
                $('#select-all').prop('checked', true).prop('indeterminate', false);
            } else {
                $('#select-all').prop('checked', false).prop('indeterminate', true);
            }
        }

        // Enhanced load transactions function with date filters
        function loadTransactions(vendorId, statusFilter, dateFrom, dateTo) {
            if (!vendorId) {
                $('#vendor-selection-message').show();
                $('#transactions-container').hide();
                $('#loading-indicator').hide();
                $('#no-transactions-message').hide();
                return;
            }

            $('#vendor-selection-message').hide();
            $('#transactions-container').hide();
            $('#no-transactions-message').hide();
            $('#loading-indicator').show();

            var ajaxData = {
                ajax: 1,
                action: "getFilteredTransactions",
                id_vendor: vendorId,
                token: "{$token|escape:'htmlall':'UTF-8'}"
            };

            // Add optional filters
            if (statusFilter) {
                ajaxData.status_filter = statusFilter;
            }
            if (dateFrom) {
                ajaxData.date_from = dateFrom;
            }
            if (dateTo) {
                ajaxData.date_to = dateTo;
            }

            $.ajax({
                url: "{$current_index|escape:'htmlall':'UTF-8'}",
                type: "POST",
                data: ajaxData,
                dataType: "json",
                success: function(response) {
                    $('#loading-indicator').hide();

                    if (response.success) {
                        if (response.count > 0) {
                            $('#transactions-tbody').html(response.html);
                            $('#transactions-container').show();
                            $('#status-filter-section, #filters-section').show();
                        } else {
                            $('#no-transactions-message').show();
                        }

                        // Reset totals and checkbox states
                        resetTransactionSelection();
                    } else {
                        $('#no-transactions-message').show();
                        console.error('Error loading transactions:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading-indicator').hide();
                    $('#no-transactions-message').show();
                    console.error('AJAX error:', error);
                }
            });
        }

        // Load available transactions (for add payment context)
        function loadAvailableTransactions() {
            var statusFilter = $('#available-status-filter').val();
            var dateFrom = $('#available-date-from').val();
            var dateTo = $('#available-date-to').val();

            $('#available-transactions-container').html('<i class="icon-spinner icon-spin"></i> Loading...');

            var ajaxData = {
                ajax: 1,
                action: 'getAvailableTransactions',
                vendor_id: typeof vendorId !== 'undefined' ? vendorId : currentVendorId,
                payment_id: typeof paymentId !== 'undefined' ? paymentId : null,
                token: "{$token|escape:'htmlall':'UTF-8'}"
            };

            // Add optional filters
            if (statusFilter) {
                ajaxData.status_filter = statusFilter;
            }
            if (dateFrom) {
                ajaxData.date_from = dateFrom;
            }
            if (dateTo) {
                ajaxData.date_to = dateTo;
            }

            $.ajax({
                url: "{$current_index|escape:'htmlall':'UTF-8'}",
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#available-transactions-container').html(response.html);
                    } else {
                        $('#available-transactions-container').html(
                            '<div class="alert alert-danger">' + (response.message ||
                                'Error loading transactions') + '</div>');
                    }
                },
                error: function() {
                    $('#available-transactions-container').html(
                        '<div class="alert alert-danger">Error loading available transactions</div>'
                        );
                }
            });
        }

        // Reset transaction selection state
        function resetTransactionSelection() {
            $('#selected-count').text('0');
            $('#selected-total').text('0.00');
            $('input[name="amount"]').val('0.00');
            $('#select-all').prop('checked', false).prop('indeterminate', false);

            // Disable submit button until transactions are selected
            var submitButton = $(
                "button[name='submitAddvendor_payment'], input[name='submitAddvendor_payment']");
            submitButton.prop('disabled', true).addClass('disabled');
        }

        // Date validation helper
        function validateDateRange() {
            var dateFrom = $('#date-from-filter').val();
            var dateTo = $('#date-to-filter').val();

            if (dateFrom && dateTo && dateFrom > dateTo) {
                alert('From date cannot be later than To date');
                $('#date-from-filter').focus();
                return false;
            }
            return true;
        }

        // Add date validation on blur
        $('#date-from-filter, #date-to-filter').on('blur', function() {
            validateDateRange();
        });

        // Global function for vendor selection change
        window.onVendorSelectionChange = function(vendorId) {
            currentVendorId = vendorId;
            // Clear filters when vendor changes
            $('#status-filter').val('');
            $('#date-from-filter').val('');
            $('#date-to-filter').val('');
            loadTransactions(vendorId, null, null, null);
        };

        // Initialize on page load
        if (typeof initialVendorId !== 'undefined' && initialVendorId) {
            currentVendorId = initialVendorId;
            loadTransactions(initialVendorId, null, null, null);
        }
    });

    // Second JavaScript for Add/Edit Payment context
    $(document).ready(function() {
        var vendorId = {$vendor_id|intval};
        var paymentId = {$payment_id|intval};

        // Load available transactions on page load
        loadAvailableTransactions();

        // Remove transaction handler
        $(document).on('click', '.remove-transaction', function() {
                var transactionId = $(this).data('transaction-id');
                var amount = $(this).data('amount');

                if (confirm('{l s='Are you sure you want to remove this transaction from the payment?' mod='multivendor'}')) {
                removeTransactionFromPayment(transactionId, amount);
            }
        });

    // Add transaction handler
    $(document).on('click', '.add-transaction', function() {
        var transactionId = $(this).data('transaction-id');
        var amount = $(this).data('amount');

        addTransactionToPayment(transactionId, amount);
    });

    // Status filter handler for available transactions
    $('#available-status-filter').on('change', function() {
        loadAvailableTransactions();
    });

    // Date filter handlers for available transactions
    $('#available-date-from, #available-date-to').on('change', function() {
        loadAvailableTransactions();
    });

    // Clear filter handler (legacy support)
    $('#clear-available-filter').on('click', function() {
        $('#available-status-filter').val('');
        loadAvailableTransactions();
    });

    // Clear all available filters handler
    $('#clear-available-filters').on('click', function() {
        $('#available-status-filter').val('');
        $('#available-date-from').val('');
        $('#available-date-to').val('');
        loadAvailableTransactions();
    });

    // Enhanced load available transactions with date filters
    function loadAvailableTransactions() {
        var statusFilter = $('#available-status-filter').val();
        var dateFrom = $('#available-date-from').val();
        var dateTo = $('#available-date-to').val();

        $('#available-transactions-container').html('<i class="icon-spinner icon-spin"></i> {l s='Loading...' mod='multivendor'}');

        var ajaxData = {
            ajax: 1,
            action: 'getAvailableTransactions',
            vendor_id: vendorId,
            payment_id: paymentId,
            token: '{$token|escape:'htmlall':'UTF-8'}'
        };

        // Add optional filters
        if (statusFilter) {
            ajaxData.status_filter = statusFilter;
        }
        if (dateFrom) {
            ajaxData.date_from = dateFrom;
        }
        if (dateTo) {
            ajaxData.date_to = dateTo;
        }

        $.ajax({
            url: '{$current_index|escape:'htmlall':'UTF-8'}',
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#available-transactions-container').html(response.html);
                } else {
                    $('#available-transactions-container').html('<div class="alert alert-danger">' +
                        (response.message || 'Error loading transactions') + '</div>');
                }
            },
            error: function() {
                $('#available-transactions-container').html('<div class="alert alert-danger">{l s='Error loading available transactions' mod='multivendor'}</div>');
            }
        });
    }

    // Remove transaction from payment
    function removeTransactionFromPayment(transactionId, amount) {
        $.ajax({
            url: '{$current_index|escape:'htmlall':'UTF-8'}',
            type: 'POST',
            data: {
                ajax: 1,
                action: 'removeTransactionFromPayment',
                transaction_id: transactionId,
                payment_id: paymentId,
                token: '{$token|escape:'htmlall':'UTF-8'}'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove row from table
                    $('#transaction-row-' + transactionId).fadeOut(function() {
                        $(this).remove();
                    });

                    // Update payment amount
                    var currentAmount = parseFloat($('input[name="amount"]').val()) || 0;
                    var newAmount = currentAmount - amount;
                    $('input[name="amount"]').val(newAmount.toFixed(2));

                    // Reload available transactions
                    loadAvailableTransactions();

                    // Show success message
                    showNotification('success', response.message);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', '{l s='Error removing transaction' mod='multivendor'}');
            }
        });
    }

    // Add transaction to payment
    function addTransactionToPayment(transactionId, amount) {
        $.ajax({
            url: '{$current_index|escape:'htmlall':'UTF-8'}',
            type: 'POST',
            data: {
                ajax: 1,
                action: 'addTransactionToPayment',
                transaction_id: transactionId,
                payment_id: paymentId,
                token: '{$token|escape:'htmlall':'UTF-8'}'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update payment amount
                    var currentAmount = parseFloat($('input[name="amount"]').val()) || 0;
                    var newAmount = currentAmount + amount;
                    $('input[name="amount"]').val(newAmount.toFixed(2));

                    // Reload page to refresh current transactions table
                    location.reload();
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function() {
                showNotification('error', '{l s='Error adding transaction' mod='multivendor'}');
            }
        });
    }

    // Show notification function
    function showNotification(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var notification = '<div class="alert ' + alertClass + ' alert-dismissible" role="alert">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' + message + '</div>';

        // Prepend to the panel body
        $('.panel-body').first().prepend(notification);

        // Auto-hide after 3 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 3000);
    }

    // Date validation for available transactions
    $('#available-date-from, #available-date-to').on('blur', function() {
        var dateFrom = $('#available-date-from').val();
        var dateTo = $('#available-date-to').val();

        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('From date cannot be later than To date');
            $(this).focus();
        }
    });
    });
</script>