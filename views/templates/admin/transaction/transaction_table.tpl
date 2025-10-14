{*
* Admin Vendor Payments - Transaction Table Template with Refund Filter
* File: views/templates/admin/transaction/transaction_table.tpl
*}

<div class="form-group">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{l s='Vendor Transactions' mod='multivendor'}</h3>
            </div>
            <div class="panel-body">
                <!-- Filters Section -->
                <div id="filters-section" class="row" style="margin-bottom: 15px">
                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label for="status-filter">{l s='Filter by Status' mod='multivendor'}:</label>
                        <select id="status-filter" class="form-control">
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
                        <label for="date-from-filter">{l s='From Date' mod='multivendor'}:</label>
                        <input type="date" id="date-from-filter" class="form-control" />
                    </div>

                    <!-- Date To Filter -->
                    <div class="col-md-2">
                        <label for="date-to-filter">{l s='To Date' mod='multivendor'}:</label>
                        <input type="date" id="date-to-filter" class="form-control" />
                    </div>

                    <!-- Refund Filter Checkbox -->
                    <div class="col-md-2">
                        <label for="include-refunds-filter" style="display: block; margin-bottom: 5px;">
                            {l s='Transaction Types' mod='multivendor'}:
                        </label>
                        <div class="checkbox" style="margin-top: 5px;">
                            <label style="font-weight: normal;">
                                <input type="checkbox" id="include-refunds-filter" value="1">
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
                    <!-- Clear Filters Button -->
                    <div class="col-md-2">
                        <button type="button" id="clear-filters" class="btn btn-default" style="margin-top: 25px;">
                            <i class="icon-remove"></i> {l s='Clear All' mod='multivendor'}
                        </button>
                        <button type="button" id="apply-filters" class="btn btn-primary"
                            style="margin-top: 25px; margin-left: 10px;">
                            <i class="icon-search"></i> {l s='Apply Filters' mod='multivendor'}
                        </button>
                    </div>
                </div>

                <!-- Instructions -->
                <div id="vendor-selection-message" class="alert alert-info">
                    <i class="icon-info-circle"></i>
                    {l s='Please select a vendor above to view their pending transactions.' mod='multivendor'}
                </div>

                <!-- Loading indicator -->
                <div id="transactions-loading" style="display: none; text-align: center; padding: 20px;">
                    <i class="icon-spinner icon-spin"></i> {l s='Loading transactions...' mod='multivendor'}
                </div>

                <!-- Transactions table container -->
                <div id="transactions-container" style="display: none;">
                    <div class="alert alert-success">
                        <strong>{l s='Pending Transactions' mod='multivendor'}</strong>
                        <span id="transaction-count" class="badge badge-info pull-right">0</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all-transactions" />

                                    </th>
                                    <th>{l s='Order' mod='multivendor'}</th>
                                    <th>{l s='Product' mod='multivendor'}</th>
                                    <th>{l s='Type' mod='multivendor'}</th>
                                    <th>{l s='Amount' mod='multivendor'}</th>
                                    <th>{l s='Status' mod='multivendor'}</th>
                                    <th>{l s='Manifest' mod='multivendor'}</th>
                                    <th>{l s='Date' mod='multivendor'}</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-tbody">
                            </tbody>
                        </table>
                    </div>

                    <!-- Transaction selection actions -->
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <strong>{l s='Selected Transactions Total: ' mod='multivendor'}</strong>
                                <span id="selected-total" class="badge badge-warning">0.00</span>
                                <span>{$currency_sign}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No transactions message -->
                <div id="no-transactions-message" style="display: none;">
                    <div class="alert alert-warning">
                        <i class="icon-warning"></i>
                        {l s='No pending transactions found for the selected filters.' mod='multivendor'}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var currentVendorId = null;

        function loadTransactions(vendorId, statusFilter, dateFrom, dateTo, includeRefunds, advanced) {
            if (!vendorId) {
                $('#vendor-selection-message').show();
                $('#transactions-container').hide();
                $('#no-transactions-message').hide();
                return;
            }

            $('#vendor-selection-message').hide();
            $('#transactions-loading').show();
            $('#transactions-container').hide();
            $('#no-transactions-message').hide();

            $.ajax({
                url: '{$ajax_url}',
                type: 'POST',
                data: {
                    ajax: true,
                    action: 'getFilteredTransactions',
                    id_vendor: vendorId,
                    status_filter: statusFilter,
                    date_from: dateFrom,
                    date_to: dateTo,
                    include_refunds: includeRefunds ? 1 : 0,
                    advanced: advanced ? 1 : 0
                },
                dataType: 'json',
                success: function(response) {
                    $('#transactions-loading').hide();

                    if (response.success && response.count > 0) {
                        $('#transactions-tbody').html(response.html);
                        $('#transaction-count').text(response.count);
                        $('#transactions-container').show();
                        $('#no-transactions-message').hide();
                        // Reset selection
                        $('#select-all-transactions').prop('checked', false);
                        updateSelectedTotal();
                        $('#reference').val(response.reference);
                    } else {
                        $('#transactions-container').hide();
                        $('#no-transactions-message').show();
                    }
                },
                error: function() {
                    $('#transactions-loading').hide();
                    $('#transactions-container').hide();
                    $('#no-transactions-message').show();
                    alert('{l s='Error loading transactions' mod='multivendor'}');
                }
            });
        }

        function updateSelectedTotal() {
            var total = 0;
            $('#transactions-tbody input[type="checkbox"]:checked').each(function() {
                var amount = parseFloat($(this).data('amount')) || 0;
                total += amount;
            });
            $('#selected-total').text(total.toFixed(2));
        }

        // Apply filters button click
        $('#apply-filters').click(function() {
            if (currentVendorId) {
                var statusFilter = $('#status-filter').val();
                var dateFrom = $('#date-from-filter').val();
                var dateTo = $('#date-to-filter').val();
                var includeRefunds = $('#include-refunds-filter').is(':checked');
                var advanced = $('#available-advanced-filters').is(':checked');

                loadTransactions(currentVendorId, statusFilter, dateFrom, dateTo, includeRefunds,
                    advanced);
            }
        });

        // Clear filters button click
        $('#clear-filters').click(function() {
            $('#status-filter').val('');
            $('#date-from-filter').val('');
            $('#date-to-filter').val('');
            $('#include-refunds-filter').prop('checked', false);
            $('#available-advanced-filters').prop('checked', false);

            if (currentVendorId) {
                loadTransactions(currentVendorId, null, null, null, false, false);
            }
        });

        // Select all checkbox
        $(document).on('change', '#select-all-transactions', function() {
            var isChecked = $(this).is(':checked');
            $('#transactions-tbody input[type="checkbox"]').prop('checked', isChecked);
            updateSelectedTotal();
        });

        // Individual transaction checkbox
        $(document).on('change', '#transactions-tbody input[type="checkbox"]', function() {
            updateSelectedTotal();

            // Update select all checkbox state
            var totalCheckboxes = $('#transactions-tbody input[type="checkbox"]').length;
            var checkedCheckboxes = $('#transactions-tbody input[type="checkbox"]:checked').length;
            $('#select-all-transactions').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        // Global function for vendor selection change
        window.onVendorSelectionChange = function(vendorId) {
            currentVendorId = vendorId;
            // Clear filters when vendor changes
            $('#status-filter').val('');
            $('#date-from-filter').val('');
            $('#date-to-filter').val('');
            $('#include-refunds-filter').prop('checked', false);
            $('#available-advanced-filters').prop('checked', false);
            loadTransactions(vendorId, null, null, null, false, false);
        };

        // Initialize on page load
        if (typeof initialVendorId !== 'undefined' && initialVendorId) {
            currentVendorId = initialVendorId;
            loadTransactions(initialVendorId, null, null, null, false, false);
        }
    });
</script>