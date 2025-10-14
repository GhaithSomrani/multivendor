{*
* Admin Vendor Payments - Current Transactions Table Template
* File: views/templates/admin/current_transactions_table.tpl
*}
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{l s='Payment Transactions' mod='multivendor'}</h3>
    </div>
    <div class="panel-body">
        <!-- Current payment transactions -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>{l s='Order' mod='multivendor'}</th>
                        <th>{l s='Product' mod='multivendor'}</th>
                        <th>{l s='Type' mod='multivendor'}</th>
                        <th>{l s='Amount' mod='multivendor'}</th>
                        <th>{l s='Status' mod='multivendor'}</th>
                        <th>{l s='Manifest' mod='multivendor'}</th>
                        <th>{l s='Date' mod='multivendor'}</th>
                        <th>{l s='Actions' mod='multivendor'}</th>
                    </tr>
                </thead>
                <tbody>
                    {if $current_transactions && count($current_transactions) > 0}
                        {foreach from=$current_transactions item=transaction}
                            <tr>
                                <td>
                                    <strong>{$transaction.id_order}</strong><br>
                                    <small>{$transaction.id_order_detail}</small>
                                </td>
                                <td>
                                    <strong>{$transaction.product_name|escape:'html':'UTF-8'}</strong><br>
                                    <small>
                                        {if $transaction.product_reference}
                                            SKU: {$transaction.product_reference|escape:'html':'UTF-8'}<br>
                                        {/if}
                                        {if $transaction.product_quantity > 0}
                                            Qty: {$transaction.product_quantity|intval}
                                        {/if}
                                    </small>
                                </td>
                                <td>
                                    {if $transaction.transaction_type == 'refund'}
                                        <span class="badge badge-warning">
                                            <i class="icon-undo"></i> {l s='Refund' mod='multivendor'}
                                        </span>
                                    {elseif $transaction.transaction_type == 'commission'}
                                        <span class="badge badge-success">
                                            <i class="icon-money"></i> {l s='Commission' mod='multivendor'}
                                        </span>
                                    {elseif $transaction.transaction_type == 'adjustment'}
                                        <span class="badge badge-info">
                                            <i class="icon-edit"></i> {l s='Adjustment' mod='multivendor'}
                                        </span>
                                    {else}
                                        <span class="badge badge-default">
                                            {$transaction.transaction_type|escape:'html':'UTF-8'|capitalize}
                                        </span>
                                    {/if}
                                </td>
                                <td>
                                    {if $transaction.transaction_type == 'refund'}
                                        <span class="text-danger">
                                            <strong>-{$transaction.vendor_amount|number_format:3} TND</strong>
                                        </span>
                                    {else}
                                        <span class="text-success">
                                            <strong>{$transaction.vendor_amount|number_format:3} TND</strong>
                                        </span>
                                    {/if}
                                </td>
                                <td>
                                    <span class="badge" style="background-color: {$transaction.status_color}; color: white;">
                                        {$transaction.line_status|escape:'html':'UTF-8'}
                                    </span>
                                </td>
                                <td>
                                    {assign var="manifestObj" value=TransactionHelper::getManifestReference($transaction.id_order_detail ,$transaction.transaction_type)}
                                    {assign var="manifestlink" value=manifest::getAdminLink($manifestObj.id_manifest)}
                                    {assign var="manifeststatus" value=Manifest::getStatus($manifestObj.id_manifest)}

                                    <span>
                                        <a href="{$manifestlink}" target="_blank">
                                           {$manifestObj.reference}</a>{if $manifeststatus}-[{$manifeststatus}]{/if} 
                                    </span>
                                </td>
                                <td>
                                    <small>{$transaction.order_date|date_format:'Y-m-d H:i'}</small>
                                </td>
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
                                {l s='No transactions found for this payment' mod='multivendor'}
                            </td>
                        </tr>
                    {/if}
                </tbody>
            </table>
        </div>

        <!-- Add new transactions section -->
        <div class="alert alert-info">
            <h4>{l s='Add More Transactions' mod='multivendor'}</h4>

            <!-- Filters for available transactions -->
            <div class="row" style="margin-bottom: 15px;">
                <!-- Status Filter -->
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label for="available-date-from-filter">{l s='From Date' mod='multivendor'}:</label>
                    <input type="date" id="available-date-from-filter" class="form-control" />
                </div>

                <!-- Date To Filter -->
                <div class="col-md-2">
                    <label for="available-date-to-filter">{l s='To Date' mod='multivendor'}:</label>
                    <input type="date" id="available-date-to-filter" class="form-control" />
                </div>

                <!-- Refund Filter Checkbox -->
                <div class="col-md-2">
                    <label for="available-include-refunds-filter" style="display: block; margin-bottom: 5px;">
                        {l s='Transaction Types' mod='multivendor'}:
                    </label>
                    <div class="checkbox" style="margin-top: 5px;">
                        <label style="font-weight: normal;">
                            <input type="checkbox" id="available-include-refunds-filter" value="1">
                            {l s='Include Refunds' mod='multivendor'}
                        </label>
                    </div>
                </div>

                <!-- Advanced Filters Checkbox -->
                <div class="col-md-2">
                    <label for="available-advanced-filters" style="display: block; margin-bottom: 5px;">
                        {l s='Advanced Filters' mod='multivendor'}:
                    </label>
                    <div class="checkbox" style="margin-top: 5px;">
                        <label style="font-weight: normal;">
                            <input type="checkbox" id="available-advanced-filters" value="1">
                            {l s='Enable Advanced Filters' mod='multivendor'}
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-2">
                    <button type="button" id="load-available-transactions" class="btn btn-primary"
                        style="margin-top: 25px;">
                        <i class="icon-search"></i> {l s='Load Available' mod='multivendor'}
                    </button>
                    <button type="button" id="clear-available-filters" class="btn btn-default"
                        style="margin-top: 25px; margin-left: 10px;">
                        <i class="icon-remove"></i> {l s='Clear' mod='multivendor'}
                    </button>
                </div>
            </div>

            <!-- Available transactions container -->
            <div id="available-transactions-container">
                <!-- This will be populated via AJAX -->
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var vendorId = {$vendor_id|intval};
        var paymentId = {$payment_id|intval};

        // Load available transactions with filters
        function loadAvailableTransactions() {
            var statusFilter = $('#available-status-filter').val();
            var dateFrom = $('#available-date-from-filter').val();
            var dateTo = $('#available-date-to-filter').val();
            var includeRefunds = $('#available-include-refunds-filter').is(':checked');
            var advanced = $('#available-advanced-filters').is(':checked');
            $.ajax({
                url: '{$ajax_url}',
                type: 'POST',
                data: {
                    ajax: true,
                    action: 'getAvailableTransactions',
                    vendor_id: vendorId,
                    payment_id: paymentId,
                    status_filter: statusFilter,
                    date_from: dateFrom,
                    date_to: dateTo,
                    include_refunds: includeRefunds ? 1 : 0,
                    advanced: advanced ? 1 : 0
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#available-transactions-container').html(response.html);
                    } else {
                        $('#available-transactions-container').html(
                            '<div class="alert alert-warning">' +
                            '<i class="icon-warning"></i> ' + response.message +
                            '</div>'
                        );
                    }
                },
                error: function() {
                    $('#available-transactions-container').html(
                        '<div class="alert alert-danger">' +
                        '<i class="icon-exclamation-triangle"></i> {l s="Error loading available transactions" mod="multivendor"}' + 
                        '</div>'
                    );
                }
            });
        }

        // Load available transactions on page load
        loadAvailableTransactions();

        // Load available transactions button click
        $('#load-available-transactions').click(function() {
            loadAvailableTransactions();
        });

        // Clear filters button click
        $('#clear-available-filters').click(function() {
            $('#available-status-filter').val('');
            $('#available-date-from-filter').val('');
            $('#available-date-to-filter').val('');
            $('#available-include-refunds-filter').prop('checked', false);
            loadAvailableTransactions();
        });

        // Remove transaction handler
        $(document).on('click', '.remove-transaction', function() {
                var transactionId = $(this).data('transaction-id');
                var amount = $(this).data('amount');

                if (confirm('{l s="Are you sure you want to remove this transaction from the payment?" mod="multivendor"}')) {
                $.ajax({
                    url: '{$ajax_url}',
                    type: 'POST',
                    data: {
                        ajax: true,
                        action: 'removeTransactionFromPayment',
                        transaction_id: transactionId,
                        payment_id: paymentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Reload the page to refresh both tables
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('{l s="Error removing transaction" mod="multivendor"}');
                    }
                });
            }
        });

    // Add transaction handler
    $(document).on('click', '.add-transaction', function() {
        var transactionId = $(this).data('transaction-id');

        $.ajax({
            url: '{$ajax_url}',
            type: 'POST',
            data: {
                ajax: true,
                action: 'addTransactionToPayment',
                transaction_id: transactionId,
                payment_id: paymentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Reload the page to refresh both tables
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('{l s="Error adding transaction" mod="multivendor"}');
            }
        });
    });
    });
</script>