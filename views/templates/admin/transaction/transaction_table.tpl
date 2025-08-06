{*
* Admin Vendor Payments - Transaction Table Template
* File: views/templates/admin/transaction_table.tpl
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
                    <div class="col-md-4">
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
                    <div class="col-md-3">
                        <label for="date-from-filter">{l s='From Date' mod='multivendor'}:</label>
                        <input type="date" id="date-from-filter" class="form-control" />
                    </div>
                    
                    <!-- Date To Filter -->
                    <div class="col-md-3">
                        <label for="date-to-filter">{l s='To Date' mod='multivendor'}:</label>
                        <input type="date" id="date-to-filter" class="form-control" />
                    </div>
                    
                    <!-- Clear Filters Button -->
                    <div class="col-md-2">
                        <button type="button" id="clear-filters" class="btn btn-default" style="margin-top: 25px;">
                            <i class="icon-remove"></i> {l s='Clear All' mod='multivendor'}
                        </button>
                    </div>
                </div>

                <!-- Rest of existing template... -->
                <!-- Instructions -->
                <div id="vendor-selection-message" class="alert alert-info">
                    <i class="icon-info-circle"></i> {l s='Please select a vendor above to view their pending transactions.' mod='multivendor'}
                </div>

                <!-- Loading indicator -->
                <div id="loading-indicator" class="alert alert-info" style="display: none;">
                    <i class="icon-refresh icon-spin"></i> {l s='Loading transactions...' mod='multivendor'}
                </div>

                <div id="no-transactions-message" class="alert alert-warning" style="display: none;">
                    <i class="icon-warning"></i> {l s='No pending transactions found for this vendor.' mod='multivendor'}
                </div>

                <!-- Transactions container -->
                <div id="transactions-container" style="display: none;">
                    <div class="row" style="margin-bottom: 15px;">
                        <div class="col-md-6">
                            <label class="control-label">
                                <input type="checkbox" id="select-all" />
                                {l s='Select All Transactions' mod='multivendor'}
                            </label>
                        </div>
                        <div class="col-md-6 text-right">
                            <strong>{l s='Selected' mod='multivendor'}: <span id="selected-count">0</span> | 
                            {l s='Total' mod='multivendor'}: <span id="selected-total">0.00</span> {$currency_sign|escape:'htmlall':'UTF-8'}</strong>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th width="50">{l s='Select' mod='multivendor'}</th>
                                    <th>{l s='Order' mod='multivendor'}</th>
                                    <th>{l s='Product' mod='multivendor'}</th>
                                    <th>{l s='Quantity' mod='multivendor'}</th>
                                    <th>{l s='Amount' mod='multivendor'}</th>
                                    <th>{l s='Status' mod='multivendor'}</th>
                                    <th>{l s='Order Date' mod='multivendor'}</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-tbody">
                                <!-- Transaction rows will be loaded here via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var currentVendorId = null;
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

    // Legacy clear status filter button
    $('#clear-status-filter').on('click', function() {
        $('#status-filter').val('');
        var dateFrom = $('#date-from-filter').val();
        var dateTo = $('#date-to-filter').val();
        loadTransactions(currentVendorId, null, dateFrom, dateTo);
    });

    // Date validation
    $('#date-from-filter, #date-to-filter').on('blur', function() {
        var dateFrom = $('#date-from-filter').val();
        var dateTo = $('#date-to-filter').val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('From date cannot be later than To date');
            $(this).focus();
        }
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
        var submitButton = $("button[name='submitAddvendor_payment'], input[name='submitAddvendor_payment']");
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

    // Reset transaction selection state
    function resetTransactionSelection() {
        $('#selected-count').text('0');
        $('#selected-total').text('0.00');
        $('input[name="amount"]').val('0.00');
        $('#select-all').prop('checked', false).prop('indeterminate', false);
        
        // Disable submit button until transactions are selected
        var submitButton = $("button[name='submitAddvendor_payment'], input[name='submitAddvendor_payment']");
        submitButton.prop('disabled', true).addClass('disabled');
    }

    // Global function for vendor selection change
    window.onVendorSelectionChange = function(vendorId) {
        currentVendorId = vendorId;
        // Clear filters when vendor changes
        $('#status-filter').val('');
        $('#date-from-filter').val('');
        $('#date-to-filter').val('');
        loadTransactions(vendorId, null, null, null);
    };
});
</script>