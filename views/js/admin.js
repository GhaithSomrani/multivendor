$(document).ready(function() {
    replaceSelectWithAutocomplete('id_customer', 'searchCustomers');

    replaceSelectWithAutocomplete('id_supplier', 'searchSuppliers');
});

/**
 * Replace a select element with an autocomplete text input
 * @param {string} selectName - The name attribute of the select element
 * @param {string} ajaxAction - The AJAX action to call for search results
 */
function replaceSelectWithAutocomplete(selectName, ajaxAction) {
    var selectElement = $('select[name="' + selectName + '"]');
    if (selectElement.length === 0) return;

    var currentValue = selectElement.val();
    var currentText = selectElement.find('option:selected').text();

    var container = $('<div class="autocomplete-container form-group"></div>');

    var hiddenInput = $('<input type="hidden" name="' + selectName + '" value="' + currentValue + '">');

    var textInput = $('<input type="text" id="search_' + selectName + '" class="form-control" value="' + currentText + '" placeholder="Type at least 3 characters to search">');

    var resultsContainer = $('<div id="' + selectName + '_results" class="list-group" style="position:absolute; z-index:1000; width:100%; display:none;"></div>');

    selectElement.after(container);
    container.append(textInput, hiddenInput, resultsContainer);
    selectElement.hide();

    textInput.on('keyup', function() {
        var query = $(this).val();
        if (query.length < 3) {
            resultsContainer.hide();
            return;
        }

        $.ajax({
            url: currentIndex + '&ajax=1&action=' + ajaxAction + '&token=' + token,
            type: 'POST',
            data: {
                controller: 'AdminVendors',
                q: query
            },
            dataType: 'json',
            success: function(data) {
                resultsContainer.empty();

                if (data.length === 0) {
                    resultsContainer.append('<div class="list-group-item">No results found</div>');
                } else {
                    $.each(data, function(i, item) {
                        var displayText = '';
                        var itemId = '';

                        if (ajaxAction === 'searchCustomers') {
                            displayText = item.firstname + ' ' + item.lastname + ' (' + item.email + ')';
                            itemId = item.id_customer;
                        } else if (ajaxAction === 'searchSuppliers') {
                            displayText = item.name;
                            itemId = item.id_supplier;
                        }

                        var listItem = $('<a href="#" class="list-group-item">' + displayText + '</a>');

                        listItem.data('id', itemId);
                        listItem.on('click', function(e) {
                            e.preventDefault();
                            hiddenInput.val($(this).data('id'));
                            textInput.val($(this).text());
                            resultsContainer.hide();
                        });

                        resultsContainer.append(listItem);
                    });
                }

                resultsContainer.show();
            }
        });
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#search_' + selectName + ', #' + selectName + '_results').length) {
            resultsContainer.hide();
        }
    });
}

/**
 * Admin Orders JS file for multivendor module
 */


$(document).ready(function() {
    console.log('Multivendor module admin-orders.js loaded');

    // Check if the AJAX URL is defined
    if (typeof multivendorAjaxUrl === 'undefined') {
        console.error('multivendorAjaxUrl is not defined!');
        // Fallback URL construction
        const baseUrl = window.location.origin;
        var multivendorAjaxUrl = baseUrl + '/index.php?fc=module&module=multivendor&controller=ajax';
        console.log('Using fallback URL: ' + multivendorAjaxUrl);
    } else {
        console.log('Using defined AJAX URL: ' + multivendorAjaxUrl);
    }

    // Get admin token
    // In PrestaShop 1.7+, the token is available in the global JavaScript variable
    var adminToken = '';
    if (typeof token !== 'undefined') {
        adminToken = token;
    } else if (typeof employee_token !== 'undefined') {
        adminToken = employee_token;
    }

    // Add the Line Status column to the order products table
    if ($('#orderProductsTable').length > 0) {
        // First, find the table header row
        const headerRow = $('#orderProductsTable thead tr');

        // Check if our column already exists
        if (headerRow.find('th.cellProductLineStatus').length === 0) {
            // Insert the new column header before the "Total" column
            headerRow.find('th:contains("Total")').before('<th class="cellProductLineStatus"><p>Line Status</p></th>');

            // For each product row, insert the status cell
            $('#orderProductsTable tbody tr.cellProduct').each(function() {
                const productId = $(this).attr('id').replace('orderProduct_', '');

                // Create the status cell with loading placeholder
                let statusCell = '<td class="cellProductLineStatus text-center">';
                statusCell += '<div class="js-line-status-placeholder" data-order-detail-id="' + productId + '">';
                statusCell += '<span class="badge badge-secondary">Loading...</span>';
                statusCell += '</div>';
                statusCell += '</td>';

                // Insert the cell before the total column
                $(this).find('td.cellProductTotalPrice').before(statusCell);
            });

            // Load status data via AJAX
            loadOrderLineStatuses();
        }
    }

    // Event handler for status change
    $(document).on('change', '.order-line-status-select', function() {
        const orderDetailId = $(this).data('order-detail-id');
        const vendorId = $(this).data('vendor-id');
        const newStatus = $(this).val();

        updateOrderLineStatus(orderDetailId, vendorId, newStatus);
    });
    /**
     * Load order line statuses via AJAX
     */
    function loadOrderLineStatuses() {
        const orderId = getOrderId();
        console.log('Loading statuses for order:', orderId);

        $.ajax({
            url: multivendorAjaxUrl,
            type: 'POST',
            data: {
                action: 'getOrderLineStatusesForAdmin',
                id_order: orderId,
                token: adminToken
            },
            dataType: 'json',
            success: function(response) {
                console.log('Status data received:', response);
                if (response.success) {
                    // Update each status placeholder
                    $.each(response.statusData, function(orderDetailId, data) {
                        const placeholder = $('.js-line-status-placeholder[data-order-detail-id="' + orderDetailId + '"]');

                        if (placeholder.length > 0) {
                            if (data) {
                                // Create dropdown with current status selected
                                let html = '<select class="form-control order-line-status-select" ';
                                html += 'data-order-detail-id="' + orderDetailId + '" ';
                                html += 'data-vendor-id="' + data.id_vendor + '">';

                                // Add options for all available statuses
                                $.each(response.availableStatuses, function(i, status) {
                                    html += '<option value="' + status.name + '" ';
                                    if (data.status === status.name) {
                                        html += 'selected ';
                                    }
                                    html += 'style="background-color: ' + status.color + '">';
                                    html += status.name;
                                    html += '</option>';
                                });

                                html += '</select>';

                                // Add current vendor info
                                html += '<div class="text-muted small mt-1">';
                                html += data.vendor_name ? data.vendor_name : 'No vendor';
                                html += '</div>';

                                placeholder.html(html);
                            } else {
                                placeholder.html('<span class="badge badge-secondary">No vendor</span>');
                            }
                        }
                    });
                } else {
                    console.error('Error:', response.message);
                    $('.js-line-status-placeholder').html('<span class="badge badge-danger">Error loading status</span>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.log('Response text:', xhr.responseText);
                $('.js-line-status-placeholder').html('<span class="badge badge-danger">Error loading status</span>');
            }
        });
    }

    /**
     * Update order line status
     */
    function updateOrderLineStatus(orderDetailId, vendorId, newStatus) {
        const orderId = getOrderId();
        console.log('Updating status for order detail:', orderDetailId, 'to:', newStatus);

        $.ajax({
            url: multivendorAjaxUrl,
            type: 'POST',
            data: {
                action: 'updateOrderLineStatus',
                id_order: orderId,
                id_order_detail: orderDetailId,
                id_vendor: vendorId,
                status: newStatus,
                token: adminToken
            },
            dataType: 'json',
            success: function(response) {
                console.log('Update response:', response);
                if (response.success) {
                    showSuccessMessage('Status updated successfully');
                } else {
                    console.error('Error updating status:', response.message);
                    showErrorMessage('Error updating status: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.log('Response text:', xhr.responseText);
                showErrorMessage('Error communicating with server: ' + error);
            }
        });
    }

    /**
     * Get order ID from URL or hidden input
     */
    function getOrderId() {
        // Try to get from URL
        const matches = window.location.pathname.match(/\/orders\/(\d+)/);
        if (matches && matches[1]) {
            return matches[1];
        }

        // Try to get from hidden input
        const hiddenInput = $('input[name="id_order"]');
        if (hiddenInput.length > 0) {
            return hiddenInput.val();
        }

        // Get from the current URL query parameters
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id_order');
    }
});

// $(document).ready(function() {

//     console.log('Multivendor module admin-orders.js loaded');

//     // Check if the AJAX URL is defined
//     if (typeof multivendorAjaxUrl === 'undefined') {
//         console.error('multivendorAjaxUrl is not defined!');
//         // Fallback URL construction
//         const baseUrl = window.location.origin;
//         // multivendorAjaxUrl = baseUrl + '/index.php?fc=module&module=multivendor&controller=ajax';
//         console.log('Using fallback URL: ' + multivendorAjaxUrl);
//     } else {
//         console.log('Using defined AJAX URL: ' + multivendorAjaxUrl);
//     }

//     // Rest of your existing code...
// });