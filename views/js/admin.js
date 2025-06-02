$(document).ready(function () {
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

    textInput.on('keyup', function () {
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
            success: function (data) {
                resultsContainer.empty();

                if (data.length === 0) {
                    resultsContainer.append('<div class="list-group-item">No results found</div>');
                } else {
                    $.each(data, function (i, item) {
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
                        listItem.on('click', function (e) {
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

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#search_' + selectName + ', #' + selectName + '_results').length) {
            resultsContainer.hide();
        }
    });
}

/**
 * Admin Orders JS file for multivendor module - COMPLETE FIXED VERSION
 */

$(document).ready(function () {
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
    var adminToken = '';
    if (typeof token !== 'undefined') {
        adminToken = token;
    } else if (typeof employee_token !== 'undefined') {
        adminToken = employee_token;
    }

    // Add the Line Status column to the order products table
    if ($('#orderProductsTable').length > 0) {
        console.log('Order products table found, adding status column...');
        
        // First, find the table header row
        const headerRow = $('#orderProductsTable thead tr');

        // Check if our column already exists
        if (headerRow.find('th.cellProductLineStatus').length === 0) {
            // Insert the new column header before the "Total" column
            const totalColumn = headerRow.find('th:contains("Total")');
            if (totalColumn.length > 0) {
                totalColumn.before('<th class="cellProductLineStatus"><p>Line Status</p></th>');
            } else {
                // Fallback: add to the end
                headerRow.append('<th class="cellProductLineStatus"><p>Line Status</p></th>');
            }

            // For each product row, insert the status cell
            $('#orderProductsTable tbody tr.cellProduct').each(function () {
                const rowId = $(this).attr('id');
                if (!rowId) {
                    console.warn('Row without ID found:', this);
                    return;
                }
                
                const productId = rowId.replace('orderProduct_', '');

                // Create the status cell with loading placeholder
                let statusCell = '<td class="cellProductLineStatus text-center">';
                statusCell += '<div class="js-line-status-placeholder" data-order-detail-id="' + productId + '">';
                statusCell += '<span class="badge badge-secondary">Loading...</span>';
                statusCell += '</div>';
                statusCell += '</td>';

                // Insert the cell before the total column or at the end
                const totalCell = $(this).find('td.cellProductTotalPrice');
                if (totalCell.length > 0) {
                    totalCell.before(statusCell);
                } else {
                    $(this).append(statusCell);
                }
            });

            // Load status data via AJAX
            loadOrderLineStatuses();
        }
    }

    // Event handler for status change - FIXED VERSION
    $(document).on('change', '.order-line-status-select', function () {
        const orderDetailId = $(this).data('order-detail-id');
        const vendorId = $(this).data('vendor-id');
        const newStatusTypeId = $(this).val(); // This is the status type ID
        const originalValue = $(this).data('original-value');

        // Store original value if not already stored
        if (typeof originalValue === 'undefined') {
            $(this).data('original-value', $(this).val());
        }

        console.log('Status change detected:', {
            orderDetailId: orderDetailId,
            vendorId: vendorId,
            newStatusTypeId: newStatusTypeId
        });

        updateOrderLineStatus(orderDetailId, vendorId, newStatusTypeId, $(this));
    });

    /**
     * Load order line statuses via AJAX - COMPLETE FIXED VERSION
     */
    function loadOrderLineStatuses() {
        const orderId = getOrderId();
        
        if (!orderId) {
            console.error('Cannot load statuses: Order ID not found');
            $('.js-line-status-placeholder').html('<span class="badge badge-danger">Order ID not found</span>');
            return;
        }
        
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
            success: function (response) {
                console.log('Status data received:', response);
                if (response.success) {
                    $('#orderProductsTable tbody tr.cellProduct').each(function () {
                        const rowId = $(this).attr('id');
                        if (!rowId) return;
                        
                        const orderDetailId = rowId.replace('orderProduct_', '');
                        const placeholder = $('.js-line-status-placeholder[data-order-detail-id="' + orderDetailId + '"]');

                        console.log('Processing order detail:', orderDetailId);

                        if (response.statusData && response.statusData[orderDetailId]) {
                            const data = response.statusData[orderDetailId];
                            console.log('Status data for order detail ' + orderDetailId + ':', data);

                            if (data.vendor_name && data.id_vendor && data.is_vendor_product) {
                                let html = '<select class="form-control order-line-status-select" ';
                                html += 'data-order-detail-id="' + orderDetailId + '" ';
                                html += 'data-vendor-id="' + data.id_vendor + '" ';
                                html += 'data-original-value="' + data.status_type_id + '">';

                                console.log('Available statuses:', response.availableStatuses);
                                console.log('Current status_type_id:', data.status_type_id);

                                if (response.availableStatuses && response.availableStatuses.length > 0) {
                                    $.each(response.availableStatuses, function (i, status) {
                                        html += '<option value="' + status.id_order_line_status_type + '" ';
                                        if (data.status_type_id == status.id_order_line_status_type) {
                                            html += 'selected ';
                                            console.log('Selected status:', status.name);
                                        }
                                        html += 'style="background-color: ' + (status.color || '#ccc') + '; color: white;">';
                                        html += status.name;
                                        html += '</option>';
                                    });
                                } else {
                                    html += '<option value="">No statuses available</option>';
                                }
                                
                                html += '</select>';
                                html += '<div class="text-muted small mt-1">';
                                html += '<strong>' + data.vendor_name + '</strong>';
                                html += '</div>';

                                console.log('Generated HTML for order detail ' + orderDetailId + ':', html);
                                placeholder.html(html);
                            } else if (data.is_vendor_product === false) {
                                placeholder.html('<span class="badge badge-secondary">Not a vendor product</span>');
                            } else {
                                placeholder.html('<span class="badge badge-warning">No vendor assigned</span>');
                            }
                        } else {
                            console.log('No status data found for order detail:', orderDetailId);
                            placeholder.html('<span class="badge badge-secondary">No data</span>');
                        }
                    });
                } else {
                    console.error('Error:', response.message);
                    $('.js-line-status-placeholder').html('<span class="badge badge-danger">Error: ' + (response.message || 'Unknown error') + '</span>');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.log('Response text:', xhr.responseText);
                $('.js-line-status-placeholder').html('<span class="badge badge-danger">AJAX Error: ' + error + '</span>');
            }
        });
    }

    /**
     * Update order line status - COMPLETE FIXED VERSION
     */
    function updateOrderLineStatus(orderDetailId, vendorId, newStatusTypeId, selectElement) {
        const orderId = getOrderId();
        console.log('Updating status for order detail:', orderDetailId, 'vendor:', vendorId, 'to status type ID:', newStatusTypeId);

        // Validate parameters before sending
        if (!orderDetailId || !vendorId || !newStatusTypeId || !orderId) {
            console.error('Missing required parameters:', {
                orderDetailId: orderDetailId,
                vendorId: vendorId, 
                newStatusTypeId: newStatusTypeId,
                orderId: orderId
            });
            showErrorMessage('Missing required parameters for status update');
            // Revert select to original value
            if (selectElement) {
                selectElement.val(selectElement.data('original-value'));
            }
            return;
        }

        // Disable the select during update
        if (selectElement) {
            selectElement.prop('disabled', true);
        }

        $.ajax({
            url: multivendorAjaxUrl,
            type: 'POST',
            data: {
                action: 'updateOrderLineStatus',
                id_order: orderId,
                id_order_detail: orderDetailId,
                id_vendor: vendorId,
                status: newStatusTypeId, // Send as 'status' (admin expects this parameter name)
                token: adminToken
            },
            dataType: 'json',
            success: function (response) {
                console.log('Update response:', response);
                if (response.success) {
                    showSuccessMessage('Status updated successfully');
                    // Update the stored original value
                    if (selectElement) {
                        selectElement.data('original-value', newStatusTypeId);
                    }
                } else {
                    console.error('Error updating status:', response.message);
                    showErrorMessage('Error updating status: ' + (response.message || 'Unknown error'));
                    // Revert select to original value
                    if (selectElement) {
                        selectElement.val(selectElement.data('original-value'));
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.log('Response text:', xhr.responseText);
                showErrorMessage('Error communicating with server: ' + error);
                // Revert select to original value
                if (selectElement) {
                    selectElement.val(selectElement.data('original-value'));
                }
            },
            complete: function() {
                // Re-enable the select
                if (selectElement) {
                    selectElement.prop('disabled', false);
                }
            }
        });
    }

    /**
     * Get order ID from URL or hidden input - IMPROVED VERSION
     */
    function getOrderId() {
        // Method 1: Try to get from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        let orderId = urlParams.get('id_order');
        
        if (orderId) {
            console.log('Order ID found in URL params:', orderId);
            return orderId;
        }

        // Method 2: Try to get from URL path
        const matches = window.location.pathname.match(/\/orders\/(\d+)/);
        if (matches && matches[1]) {
            console.log('Order ID found in URL path:', matches[1]);
            return matches[1];
        }

        // Method 3: Try to get from hidden input
        const hiddenInput = $('input[name="id_order"]');
        if (hiddenInput.length > 0 && hiddenInput.val()) {
            console.log('Order ID found in hidden input:', hiddenInput.val());
            return hiddenInput.val();
        }

        // Method 4: Try to get from order reference in the page
        const orderRefElement = $('.order-reference, .reference');
        if (orderRefElement.length > 0) {
            const refText = orderRefElement.text();
            const refMatch = refText.match(/#(\d+)/);
            if (refMatch && refMatch[1]) {
                console.log('Order ID found in reference:', refMatch[1]);
                return refMatch[1];
            }
        }

        // Method 5: Try to extract from current page URL
        const currentUrl = window.location.href;
        const urlMatch = currentUrl.match(/id_order=(\d+)/);
        if (urlMatch && urlMatch[1]) {
            console.log('Order ID found in current URL:', urlMatch[1]);
            return urlMatch[1];
        }

        console.error('Order ID not found using any method');
        return null;
    }

    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        // Remove existing messages
        $('.multivendor-alert').remove();
        
        const alert = $('<div class="alert alert-success multivendor-alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;">' + message + '</div>');
        $('body').append(alert);
        
        setTimeout(function () {
            alert.fadeOut(function () {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Show error message
     */
    function showErrorMessage(message) {
        // Remove existing messages
        $('.multivendor-alert').remove();
        
        const alert = $('<div class="alert alert-danger multivendor-alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;">' + message + '</div>');
        $('body').append(alert);
        
        setTimeout(function () {
            alert.fadeOut(function () {
                $(this).remove();
            });
        }, 5000);
    }

    // Auto-load statuses when page is ready
    if ($('#orderProductsTable').length > 0) {
        console.log('Auto-loading order line statuses...');
        // Small delay to ensure DOM is fully ready
        setTimeout(function() {
            loadOrderLineStatuses();
        }, 500);
    }
});