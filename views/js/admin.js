/**
 * Fixed admin.js file for multivendor module
 */

$(document).ready(function () {

    // Initialize autocomplete for vendor creation/editing
    if ($('select[name="id_customer"]').length > 0) {
        replaceSelectWithAutocomplete('id_customer', 'searchCustomers');
    }

    if ($('select[name="id_supplier"]').length > 0) {
        replaceSelectWithAutocomplete('id_supplier', 'searchSuppliers');
    }

    // Initialize order line status functionality
    initializeOrderLineStatus();
});

/**
 * Replace a select element with an autocomplete text input
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

        // Build AJAX URL properly
        var ajaxUrl = currentIndex + '&ajax=1&action=' + ajaxAction + '&token=' + token;

        $.ajax({
            url: ajaxUrl,
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
            },
            error: function (xhr, status, error) {
                console.error('Autocomplete AJAX error:', status, error);
                resultsContainer.empty().append('<div class="list-group-item text-danger">Error loading results</div>').show();
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
 * Initialize order line status functionality for admin orders
 */
function initializeOrderLineStatus() {
    // Check if we're on an order page
    const isOrderPage = $('#orderProductsTable').length > 0;

    if (!isOrderPage) {
        return;
    }


    // Get order ID
    const orderId = getOrderIdFromPage();
    if (!orderId) {
        return;
    }


    // Add status column to order products table
    addOrderLineStatusColumn();

    // Load status data
    loadOrderLineStatuses(orderId);

    // Setup event handlers
    setupOrderLineStatusHandlers();
}

/**
 * Add the order line status column to the admin order products table
 */
function addOrderLineStatusColumn() {
    const headerRow = $('#orderProductsTable thead tr');

    // Check if column already exists
    if (headerRow.find('th.cellProductLineStatus').length > 0) {
        return;
    }


    // Add header column before the Total column
    const totalHeader = headerRow.find('th:contains("Total")');
    if (totalHeader.length === 0) {
        // Fallback: add at the end
        headerRow.append('<th class="cellProductLineStatus"><p>Line Status</p></th>');
    } else {
        totalHeader.before('<th class="cellProductLineStatus"><p>Line Status</p></th>');
    }

    // Add status cells to each product row
    $('#orderProductsTable tbody tr.cellProduct').each(function () {
        const $row = $(this);
        const productId = $row.attr('id') ? $row.attr('id').replace('orderProduct_', '') : '';

        if (!productId) {
            console.warn('No product ID found for row:', $row);
            return;
        }

        const statusCell = '<td class="cellProductLineStatus text-center">' +
            '<div class="js-line-status-placeholder" data-order-detail-id="' + productId + '">' +
            '<span class="badge badge-secondary">Loading...</span>' +
            '</div></td>';

        const totalCell = $row.find('td.cellProductTotalPrice');
        if (totalCell.length === 0) {
            // Fallback: add at the end
            $row.append(statusCell);
        } else {
            totalCell.before(statusCell);
        }
    });
}

/**
 * Load order line statuses via AJAX
 */
function loadOrderLineStatuses(orderId) {

    // Build the AJAX URL
    var ajaxUrl = buildAdminAjaxUrl();

    if (!ajaxUrl) {
        console.error('Could not build AJAX URL');
        $('.js-line-status-placeholder').html('<span class="badge badge-danger">Config Error</span>');
        return;
    }


    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            ajax: 1,
            action: 'getOrderLineStatusesForAdmin',
            id_order: orderId
        },
        beforeSend: function () {
        },
        success: function (response) {
            if (response && response.success) {
                updateOrderLineStatusCells(response);
            } else {
                console.error('Error loading statuses:', response ? response.message : 'No response');
                $('.js-line-status-placeholder').html('<span class="badge badge-danger">Error: ' + (response ? response.message : 'No response') + '</span>');
            }
        },
        error: function (xhr, status, error) {

            $('.js-line-status-placeholder').html('<span class="badge badge-danger">Connection Error</span>');
        }
    });
}

/**
 * Update the order line status cells with loaded data
 */
function updateOrderLineStatusCells(response) {
    $('.js-line-status-placeholder').each(function () {
        const $placeholder = $(this);
        const orderDetailId = $placeholder.data('order-detail-id');
        const statusData = response.statusData[orderDetailId];


        if (statusData && statusData.is_vendor_product && statusData.vendor_name) {
            // This is a vendor product, create status dropdown
            let html = '<select class="form-control order-line-status-select" ';
            html += 'data-order-detail-id="' + orderDetailId + '" ';
            html += 'data-vendor-id="' + statusData.id_vendor + '">';

            // Add status options
            if (response.availableStatuses && response.availableStatuses.length > 0) {
                $.each(response.availableStatuses, function (i, status) {
                    const selected = (statusData.status_type_id == status.id_order_line_status_type) ? 'selected' : '';
                    const color = status.color || '#6c757d';
                    html += '<option value="' + status.id_order_line_status_type + '" ' + selected + ' ';
                    html += 'style="background-color: ' + color + '; color: white;">';
                    html += status.name;
                    html += '</option>';
                });
            } else {
                html += '<option value="">No statuses available</option>';
            }

            html += '</select>';
            html += '<div class="text-muted small mt-1">' + statusData.vendor_name + '</div>';

            $placeholder.html(html);
        } else {
            // Not a vendor product
            $placeholder.html('<span class="badge badge-secondary">Not vendor product</span>');
        }
    });
}

/**
 * Setup event handlers for order line status changes
 */
function setupOrderLineStatusHandlers() {
    $(document).on('change', '.order-line-status-select', function () {
        const $select = $(this);
        const orderDetailId = $select.data('order-detail-id');
        const vendorId = $select.data('vendor-id');
        const newStatusTypeId = $select.val();
        const orderId = getOrderIdFromPage();



        // Disable select during update
        $select.prop('disabled', true);

        // Build AJAX URL
        var ajaxUrl = buildAdminAjaxUrl();

        if (!ajaxUrl) {
            console.error('Could not build AJAX URL for status update');
            $select.prop('disabled', false);
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                action: 'updateOrderLineStatus',
                id_order: orderId,
                id_order_detail: orderDetailId,
                id_vendor: vendorId,
                status: newStatusTypeId
            },
            success: function (response) {
                if (response && response.success) {
                    showSuccessMessage('Order line status updated successfully');

                    // Update the select appearance
                    const selectedOption = $select.find('option:selected');
                    const color = selectedOption.css('background-color') || '#28a745';
                    $select.css('background-color', color);
                } else {
                    console.error('Status update failed:', response ? response.message : 'No response');
                    showErrorMessage('Failed to update status: ' + (response ? response.message : 'Unknown error'));
                }
            },
            error: function (xhr, status, error) {
                showErrorMessage('Connection error: ' + error);
            },
            complete: function () {
                // Re-enable select
                $select.prop('disabled', false);
            }
        });
    });
}

/**
 * Build the admin AJAX URL
 */
function buildAdminAjaxUrl() {
    // Try to use the multivendor-specific AJAX URL if available
    if (typeof multivendorAdminAjaxUrl !== 'undefined') {
        return multivendorAdminAjaxUrl;
    }

    // Fallback: build URL manually
    if (typeof currentIndex !== 'undefined' && typeof token !== 'undefined') {
        return currentIndex + '&configure=multivendor&ajax=1&token=' + token;
    }

    // Final fallback: try to construct from window location
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
    const fallbackUrl = baseUrl + '/index.php?controller=AdminModules&configure=multivendor&ajax=1';

    // Try to get token from various sources
    var tokenValue = '';
    if (typeof multivendorToken !== 'undefined') {
        tokenValue = multivendorToken;
    } else if (typeof adminToken !== 'undefined') {
        tokenValue = adminToken;
    } else if (typeof token !== 'undefined') {
        tokenValue = token;
    }

    if (tokenValue) {
        return fallbackUrl + '&token=' + tokenValue;
    }

    console.warn('No token found, returning URL without token');
    return fallbackUrl;
}

/**
 * Get order ID from the current page
 */
function getOrderIdFromPage() {
    var orderId = null;

    // Method 1: Try Symfony routing format (/sell/orders/ID/)
    const pathMatch = window.location.pathname.match(/\/sell\/orders\/(\d+)\/?/);
    if (pathMatch) {
        orderId = pathMatch[1];
        return orderId;
    }

    // Method 2: Try legacy URL parameters (?id_order=ID)
    const urlParams = new URLSearchParams(window.location.search);
    orderId = urlParams.get('id_order');
    if (orderId) {
        return orderId;
    }

    // Method 3: Try hidden input
    const input = $('input[name="id_order"]');
    if (input.length > 0 && input.val()) {
        orderId = input.val();
        return orderId;
    }

    // Method 4: Try to extract from currentIndex variable
    if (typeof currentIndex !== 'undefined' && currentIndex.includes('id_order=')) {
        const match = currentIndex.match(/id_order=(\d+)/);
        if (match) {
            orderId = match[1];
            return orderId;
        }
    }

    // Method 5: Try alternative URL patterns
    const altMatch = window.location.href.match(/[?&]id_order=(\d+)/);
    if (altMatch) {
        orderId = altMatch[1];
        return orderId;
    }

    // Method 6: Try data attributes
    const orderElement = $('[data-order-id]');
    if (orderElement.length > 0) {
        orderId = orderElement.data('order-id');
        return orderId;
    }

    // Method 7: Try page title
    const titleMatch = document.title.match(/Order #(\d+)/i);
    if (titleMatch) {
        orderId = titleMatch[1];
        return orderId;
    }

    console.error('Order ID not found using any method');
    return null;
}

/**
 * Show success message
 */
function showSuccessMessage(message) {

    // Create success notification
    const notification = $('<div class="alert alert-success alert-dismissible" role="alert">' +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        message +
        '</div>');

    // Find best container
    var container = $('.content-div').first();
    if (container.length === 0) {
        container = $('#content').first();
    }
    if (container.length === 0) {
        container = $('.panel-body').first();
    }
    if (container.length === 0) {
        container = $('body');
    }

    container.prepend(notification);

    // Auto-remove after 5 seconds
    setTimeout(function () {
        notification.fadeOut(function () {
            $(this).remove();
        });
    }, 5000);
}

/**
 * Show error message
 */
function showErrorMessage(message) {
    console.error('Error:', message);

    // Create error notification
    const notification = $('<div class="alert alert-danger alert-dismissible" role="alert">' +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '<strong>Error:</strong> ' + message +
        '</div>');

    // Find best container
    var container = $('.content-div').first();
    if (container.length === 0) {
        container = $('#content').first();
    }
    if (container.length === 0) {
        container = $('.panel-body').first();
    }
    if (container.length === 0) {
        container = $('body');
    }

    container.prepend(notification);

    // Auto-remove after 8 seconds
    setTimeout(function () {
        notification.fadeOut(function () {
            $(this).remove();
        });
    }, 8000);
}