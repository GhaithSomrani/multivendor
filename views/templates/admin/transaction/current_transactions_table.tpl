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
                                    <td>{$transaction.product_reference|escape:'htmlall':'UTF-8'} ({$transaction.product_quantity|intval})</td>
                                    <td>{$transaction.vendor_amount|number_format:2} {$currency_sign|escape:'htmlall':'UTF-8'}</td>
                                    <td>{$transaction.transaction_date|date_format:'%d/%m/%Y %H:%M'}</td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-danger btn-xs remove-transaction" 
                                                data-transaction-id="{$transaction.id_vendor_transaction|intval}"
                                                data-amount="{$transaction.vendor_amount|floatval}">
                                            <i class="icon-trash"></i> {l s='Remove' mod='multivendor'}
                                        </button>
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="7" class="text-center">{l s='No transactions found for this payment' mod='multivendor'}</td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
                
                <!-- Add new transactions section -->
                <div class="alert alert-info">
                    <h4>{l s='Add More Transactions' mod='multivendor'}</h4>
                    
                    <!-- Status Filter for available transactions -->
                    <div class="row" style="margin-bottom: 15px;">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <button type="button" id="clear-available-filter" class="btn btn-default" style="margin-top: 25px;">
                                <i class="icon-remove"></i> {l s='Clear Filter' mod='multivendor'}
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

    // Clear filter handler
    $('#clear-available-filter').on('click', function() {
        $('#available-status-filter').val('');
        loadAvailableTransactions();
    });

    // Load available transactions
    function loadAvailableTransactions() {
        var statusFilter = $('#available-status-filter').val();
        
        $('#available-transactions-container').html('<i class="icon-spinner icon-spin"></i> {l s='Loading...' mod='multivendor'}');

        $.ajax({
            url: '{$current_index|escape:'htmlall':'UTF-8'}',
            type: 'POST',
            data: {
                ajax: 1,
                action: 'getAvailableTransactions',
                vendor_id: vendorId,
                payment_id: paymentId,
                status_filter: statusFilter,
                token: '{$token|escape:'htmlall':'UTF-8'}'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#available-transactions-container').html(response.html);
                } else {
                    $('#available-transactions-container').html('<div class="alert alert-danger">' + response.message + '</div>');
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
});
</script>