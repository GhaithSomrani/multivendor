/**
 * Complete orders.js file with global MPN input functionality and single manifest generation
 */

const verifiedOrderDetails = new Set();

$(document).ready(function () {
    // Set initial colors for status dropdowns
    $('.order-line-status-select').each(function () {
        const selectedOption = $(this).find('option:selected');
        const color = selectedOption.css('background-color');
        $(this).css('background-color', color);
    });

    // Handle status change dropdown
    $('.order-line-status-select').on('change', function () {
        const $select = $(this);
        const orderDetailId = $select.data('order-detail-id');
        const newStatusTypeId = $select.val();
        const originalStatusTypeId = $select.data('original-status-type-id');

        // Show loading state
        $select.prop('disabled', true);

        // Make AJAX request to updateVendorStatus
        $.ajax({
            url: ordersAjaxUrl,
            type: 'POST',
            data: {
                action: 'updateVendorStatus',
                id_order_detail: orderDetailId,
                id_status_type: newStatusTypeId,
                comment: '', // Empty comment for quick status update
                token: ordersAjaxToken
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // Update the UI
                    $select.data('original-status-type-id', newStatusTypeId);
                    showSuccessMessage(response.message || 'Status updated successfully');

                    // Update the dropdown appearance
                    const selectedOption = $select.find('option:selected');
                    const color = selectedOption.css('background-color');
                    $select.css('background-color', color);

                    // Check if we should add this to the manifest
                    checkAndAddToManifestIfNeeded(orderDetailId, newStatusTypeId);
                } else {
                    // Revert to original status
                    $select.val(originalStatusTypeId);
                    showErrorMessage(response.message || 'Error updating status');
                }
            },
            error: function () {
                // Revert to original status
                $select.val(originalStatusTypeId);
                showErrorMessage('Connection error. Please try again.');
            },
            complete: function () {
                // Re-enable the select
                $select.prop('disabled', false);
            }
        });
    });

    // Handle status history view
    $('.view-status-history').on('click', function () {
        const orderDetailId = $(this).data('order-detail-id');
        loadStatusHistory(orderDetailId);
    });

    // Initialize the global MPN verification
    initGlobalMpnVerification();

    // Make status badges clickable for filtering
    $('.mv-filter-status').on('click', function () {
        const status = $(this).data('status');

        // Update active state on filter badges
        $('.mv-filter-status').removeClass('active');
        $(this).addClass('active');

        // Show loading indicator
        $('.mv-table-container').addClass('mv-loading-content');

        // Filter the table rows
        if (status === 'all') {
            // Show all rows
            $('.mv-table tbody tr').show();
        } else {
            // Hide all rows first
            $('.mv-table tbody tr').hide();

            // Show only rows with matching status
            $('.mv-table tbody tr').each(function () {
                const rowStatus = $(this).find('.mv-status-select option:selected').val() ||
                    $(this).find('.mv-status-badge').text().trim().toLowerCase();

                if (rowStatus.toLowerCase().includes(status.toLowerCase())) {
                    $(this).show();
                }
            });
        }

        // Remove loading indicator
        $('.mv-table-container').removeClass('mv-loading-content');

        // Update the page title to indicate filtering
        if (status !== 'all') {
            const statusText = $(this).text().split(':')[0].trim();
            $('.page-header h1').text('Orders - ' + statusText);
        } else {
            $('.page-header h1').text('My Order Lines');
        }
    });

    // Handle bulk actions for order selection
    initBulkActions();

    // Handle print manifest button click
    $('#print-manifest-btn').on('click', function () {
        printPickupManifest();
    });

    // Close modal when clicking outside or on close button
    $(document).on('click', '.mv-modal-backdrop, .mv-modal-close', function () {
        $('#statusHistoryModal').removeClass('mv-modal-open');
    });

    // Close modal when pressing Escape key
    $(document).on('keyup', function (e) {
        if (e.keyCode === 27) { // Escape key
            $('#statusHistoryModal').removeClass('mv-modal-open');
        }
    });
});

/**
 * Initialize global MPN verification system
 */
function initGlobalMpnVerification() {
    const $globalMpnInput = $('#global-mpn-input');

    // Focus on the global input when page loads
    $globalMpnInput.focus();

    // Handle input event for global MPN input
    $globalMpnInput.on('keyup', function (e) {
        // If user presses Enter, process the MPN
        if (e.keyCode === 13) {
            processMpnInput();
        }
    });

    // On blur, also process the input
    $globalMpnInput.on('blur', function () {
        if ($(this).val().trim().length > 0) {
            processMpnInput();
        }
    });

    // Add existing order lines with "add commission" status to manifest
    checkExistingOrderLinesForManifest();
}

/**
 * Process the global MPN input
 */
function processMpnInput() {
    const mpnValue = $('#global-mpn-input').val().trim();
    const $statusMessage = $('#mpn-status-message');

    if (!mpnValue) {
        return;
    }

    // Update status to searching
    $statusMessage.text('Searching for MPN: ' + mpnValue + '...')
        .removeClass('success error')
        .addClass('searching');

    // Find the matching row
    let found = false;
    let $matchingRow = null;
    let orderDetailId = null;

    $('.mv-table tbody tr').each(function () {
        const rowMpn = $(this).data('product-mpn');

        if (rowMpn === mpnValue) {
            found = true;
            $matchingRow = $(this);
            orderDetailId = $(this).data('id');
            return false; // Break the loop
        }
    });

    if (found && $matchingRow && orderDetailId) {
        // Remove any previous highlights
        $('.mv-table tbody tr').removeClass('mv-mpn-found');

        // Show success message
        $statusMessage.text('MPN found! Processing order line...')
            .removeClass('searching error')
            .addClass('success');

        // Highlight the matching row
        $matchingRow.addClass('mv-mpn-found');

        // Scroll to the matching row
        $('html, body').animate({
            scrollTop: $matchingRow.offset().top - 100
        }, 500);

        // Check if this order is already verified
        if (verifiedOrderDetails.has(orderDetailId)) {
            $statusMessage.text('This order line is already verified.')
                .removeClass('searching')
                .addClass('success');
        } else {
            // Process the verification
            updateOrderLineStatus(orderDetailId);
        }
    } else {
        // Show error message
        $statusMessage.text('MPN not found. Please try again.')
            .removeClass('searching success')
            .addClass('error');
    }

    // Clear the input
    $('#global-mpn-input').val('').focus();
}

/**
 * Initialize bulk actions
 */
function initBulkActions() {
    // Variables to track state
    let selectedOrders = [];

    // Handle "Select All" checkbox
    $('#select-all-orders').on('change', function () {
        const isChecked = $(this).prop('checked');
        $('.mv-row-checkbox').prop('checked', isChecked);

        // Update selected orders array
        selectedOrders = isChecked ?
            $('.mv-row-checkbox').map(function () { return $(this).data('id'); }).get() :
            [];

        updateBulkControls();
    });

    // Handle individual row checkboxes
    $(document).on('change', '.mv-row-checkbox', function () {
        const id = $(this).data('id');

        if ($(this).prop('checked')) {
            // Add to selected orders if not already in the array
            if (selectedOrders.indexOf(id) === -1) {
                selectedOrders.push(id);
            }
        } else {
            // Remove from selected orders
            const index = selectedOrders.indexOf(id);
            if (index !== -1) {
                selectedOrders.splice(index, 1);
            }

            // Uncheck "Select All" if any row is unchecked
            $('#select-all-orders').prop('checked', false);
        }

        updateBulkControls();
    });

    $('#apply-bulk-status').on('click', function () {
        const newStatus = $('#bulk-status-select').val();

        if (!newStatus || selectedOrders.length === 0) {
            return;
        }

        if (!confirm(bulkStatusChangeConfirmText)) {
            return;
        }

        $('#apply-bulk-status').prop('disabled', true).text(processingText);

        $.ajax({
            url: ordersAjaxUrl,
            type: 'POST',
            data: {
                ajax: true,
                action: 'bulkUpdateVendorStatus',
                order_detail_ids: selectedOrders,
                status: newStatus,
                comment: bulkChangeComment,
                token: ordersAjaxToken
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $.each(response.results, function (id, success) {
                        if (success) {
                            updateRowStatus(id, newStatus);
                        }
                    });

                    showNotification('success', response.message);
                } else {
                    showNotification('error', response.message || 'Error updating statuses');
                }

                resetBulkControls();
            },
            error: function () {
                showNotification('error', errorStatusText);
                resetBulkControls();
            }
        });
    });

    /**
     * Update bulk control elements based on selection state
     */
    function updateBulkControls() {
        const count = selectedOrders.length;

        $('#selected-count').text(count + ' ' + selectedText);

        $('#bulk-status-select, #apply-bulk-status').prop('disabled', count === 0);
    }

    /**
     * Update row status after bulk update
     */
    function updateRowStatus(id, newStatus) {
        const row = $(`tr[data-id="${id}"]`);
        const select = row.find('.order-line-status-select');

        if (select.length) {
            select.val(newStatus);

            row.attr('data-status', newStatus.toLowerCase());
        }

        row.find('.mv-row-checkbox').prop('checked', false);
    }

    /**
     * Reset bulk action controls
     */
    function resetBulkControls() {
        $('#apply-bulk-status').prop('disabled', true).text(applyText);
        $('#bulk-status-select').val('');
        $('.mv-row-checkbox, #select-all-orders').prop('checked', false);

        selectedOrders = [];
        updateBulkControls();
    }
}

/**
 * Update order line status via AJAX
 * @param {number} orderDetailId - The order detail ID to update
 */
function updateOrderLineStatus(orderDetailId) {
    showNotification('info', 'Verifying...');

    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'getAddCommissionStatus',
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.status) {
                const newStatus = response.status.name;

                $.ajax({
                    url: ordersAjaxUrl,
                    type: 'POST',
                    data: {
                        action: 'updateVendorStatus',
                        id_order_detail: orderDetailId,
                        status: newStatus,
                        comment: 'MPN verified by scanning',
                        token: ordersAjaxToken
                    },
                    dataType: 'json',
                    success: function (updateResponse) {
                        if (updateResponse.success) {
                            showNotification('success', 'MPN verified and status updated');

                            // Update the status in UI
                            updateStatusInUI(orderDetailId, newStatus, response.status.color);

                            // Add to manifest
                            addToManifest(orderDetailId);

                            // Mark as verified
                            $(`tr[data-id="${orderDetailId}"]`).addClass('mv-mpn-verified');

                            // Update status message
                            $('#mpn-status-message').text('Order line verified successfully. Ready for next scan.')
                                .removeClass('searching error')
                                .addClass('success');
                        } else {
                            showNotification('error', updateResponse.message || 'Failed to update status');

                            // Update status message
                            $('#mpn-status-message').text('Failed to update status: ' + (updateResponse.message || 'Unknown error'))
                                .removeClass('searching success')
                                .addClass('error');
                        }
                    },
                    error: function () {
                        showNotification('error', 'Connection error');

                        // Update status message
                        $('#mpn-status-message').text('Connection error. Please try again.')
                            .removeClass('searching success')
                            .addClass('error');
                    }
                });
            } else {
                showNotification('error', 'No suitable status found for commission');

                // Update status message
                $('#mpn-status-message').text('No suitable status found for commission')
                    .removeClass('searching success')
                    .addClass('error');
            }
        },
        error: function () {
            showNotification('error', 'Failed to get commission status');

            // Update status message
            $('#mpn-status-message').text('Failed to get commission status')
                .removeClass('searching success')
                .addClass('error');
        }
    });
}

/**
 * Update status in the UI
 * @param {number} orderDetailId - The order detail ID
 * @param {string} newStatus - The new status name
 * @param {string} statusColor - The color for the status
 */
function updateStatusInUI(orderDetailId, newStatus, statusColor) {
    const $row = $('tr[data-id="' + orderDetailId + '"]');
    const $statusCell = $row.find('td:nth-child(6)'); // Status column

    const $select = $row.find('.order-line-status-select');
    if ($select.length) {
        $select.val(newStatus);
        $select.css('background-color', statusColor);
        $select.data('original-status', newStatus);
        const $selectedOption = $select.find('option:selected');
        $selectedOption.prop('selected', true);
        const $statusBadge = $row.find('.mv-status-badge');
        if ($statusBadge.length) {
            $statusBadge.text(newStatus)
                .css('background-color', statusColor);
        }
    } else {
        const $badge = $statusCell.find('.mv-status-badge');
        if ($badge.length) {
            $badge.text(newStatus)
                .css('background-color', statusColor);
        }
    }

    $row.attr('data-status', newStatus.toLowerCase());

    // Add a temporary class to highlight the change
    $row.addClass('status-updated');
    setTimeout(function () {
        $row.removeClass('status-updated');
    }, 1000);
}

/**
 * Add verified order line to manifest
 * @param {number} orderDetailId - The order detail ID to add
 */
function addToManifest(orderDetailId) {
    orderDetailId = parseInt(orderDetailId);

    if (verifiedOrderDetails.has(orderDetailId)) {
        return;
    }

    const $row = $('tr[data-id="' + orderDetailId + '"]');

    const orderRef = $row.find('td:nth-child(2) a').text();
    const productName = $row.find('td:nth-child(3)').text();
    const productMpn = $row.data('product-mpn');
    const quantity = $row.find('td:nth-child(4)').text();
    const timestamp = new Date().toLocaleTimeString();

    verifiedOrderDetails.add(orderDetailId);

    $('#manifest-items').append(`
        <tr data-order-detail-id="${orderDetailId}">
          <td>${orderRef}</td>
          <td>${productName}</td>
          <td>${productMpn}</td>
          <td>${quantity}</td>
          <td>${timestamp}</td>
        </tr>
    `);

    $('#manifest-count').text(verifiedOrderDetails.size);
    $('#pickup-manifest-block').show();
}

/**
 * Check and add to manifest if needed based on status
 */
function checkAndAddToManifestIfNeeded(orderDetailId, newStatus) {
    // Get the "add commission" status to compare
    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'getAddCommissionStatus',
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.status) {
                // If the selected status matches the "add commission" status
                if (newStatus === response.status.name) {
                    // Check if this order detail is already in the manifest
                    if (!verifiedOrderDetails.has(parseInt(orderDetailId))) {
                        // Add it to the manifest
                        addToManifest(orderDetailId);

                        // Show a notification
                        showNotification('success', 'Added to pickup manifest');
                    }
                } else {
                    // If the status was changed to something else, remove from manifest if present
                    if (verifiedOrderDetails.has(parseInt(orderDetailId))) {
                        removeFromManifest(orderDetailId);
                        showNotification('info', 'Removed from pickup manifest');
                    }
                }
            }
        }
    });
}

/**
 * Remove an order line from the manifest
 * @param {number} orderDetailId - The order detail ID to remove
 */
function removeFromManifest(orderDetailId) {
    // Remove from the set
    verifiedOrderDetails.delete(parseInt(orderDetailId));

    // Remove from the UI
    $('#manifest-items tr[data-order-detail-id="' + orderDetailId + '"]').remove();

    // Update the count
    $('#manifest-count').text(verifiedOrderDetails.size);

    // Hide the manifest block if empty
    if (verifiedOrderDetails.size === 0) {
        $('#pickup-manifest-block').hide();
    }
}

/**
 * Check existing order lines on page load
 * and add those with the appropriate status to the manifest
 */
function checkExistingOrderLinesForManifest() {
    // Get the "add commission" status
    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'getAddCommissionStatus',
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.status) {
                const addCommissionStatus = response.status.name;

                // Loop through all order lines with status selects
                $('.order-line-status-select').each(function () {
                    const $select = $(this);
                    const currentStatus = $select.val();
                    const orderDetailId = $select.data('order-detail-id');

                    // If the current status matches the "add commission" status,
                    // add it to the manifest
                    if (currentStatus === addCommissionStatus) {
                        addToManifest(orderDetailId);
                    }
                });
            }
        }
    });
}

/**
 * Load status history
 */
function loadStatusHistory(orderDetailId) {
    // Show loading in modal
    $('#statusHistoryContent').html('<div class="mv-loading">Loading history...</div>');
    $('#statusHistoryModal').addClass('mv-modal-open');

    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'getStatusHistory',
            id_order_detail: orderDetailId,
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                displayStatusHistory(response.history);
            } else {
                $('#statusHistoryContent').html('<p class="mv-error">Could not load status history</p>');
            }
        },
        error: function () {
            $('#statusHistoryContent').html('<p class="mv-error">Connection error. Please try again.</p>');
        }
    });
}

/**
 * Display status history in modal
 */
function displayStatusHistory(history) {
    let html = '<div class="mv-history-list">';

    if (history.length === 0) {
        html += '<p class="mv-empty-state">No status history available</p>';
    } else {
        history.forEach(function (entry) {
            html += '<div class="mv-history-item">';
            html += '<div class="mv-history-date">' + entry.date + '</div>';
            html += '<div class="mv-history-change">';
            html += '<span class="mv-history-old">' + entry.old_status + '</span>';
            html += ' → ';
            html += '<span class="mv-history-new">' + entry.new_status + '</span>';
            html += '</div>';
            if (entry.comment) {
                html += '<div class="mv-history-comment">' + entry.comment + '</div>';
            }
            html += '<div class="mv-history-user">by ' + entry.changed_by + '</div>';
            html += '</div>';
        });
    }

    html += '</div>';

    $('#statusHistoryContent').html(html);
}

/**
 * Export table to CSV
 */
function exportTableToCSV() {
    // Create a form element
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = ordersAjaxUrl;
    form.style.display = 'none';

    // Add action parameter
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'exportOrdersCSV';
    form.appendChild(actionInput);

    // Add token if needed
    if (typeof ordersAjaxToken !== 'undefined') {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = 'token';
        tokenInput.value = ordersAjaxToken;
        form.appendChild(tokenInput);
    }

    // Add the form to the document and submit it
    document.body.appendChild(form);
    form.submit();

    // Remove the form after submission
    setTimeout(function () {
        document.body.removeChild(form);
    }, 1000);
}

/**
 * Print pickup manifest - Updated for single manifest generation
 */
function printPickupManifest() {
    if (verifiedOrderDetails.size === 0) {
        showNotification('error', 'No items in manifest to print');
        return;
    }

    // Generate the URL for the single manifest controller
    const manifestUrl = window.location.origin + window.location.pathname +
        '?fc=module&module=multivendor&controller=manifest&details=' +
        Array.from(verifiedOrderDetails).join(',');

    // Open in a new tab/window
    window.open(manifestUrl, '_blank');
}

/**
 * Show success message
 */
function showSuccessMessage(message) {
    const $alert = $('<div class="mv-alert mv-alert-success mv-alert-dismissible">' + message + '</div>');
    $('.mv-card-body').first().prepend($alert);
    setTimeout(function () {
        $alert.fadeOut(function () {
            $(this).remove();
        });
    }, 3000);
}

/**
 * Show error message
 */
function showErrorMessage(message) {
    const $alert = $('<div class="mv-alert mv-alert-danger mv-alert-dismissible">' + message + '</div>');
    $('.mv-card-body').first().prepend($alert);
    setTimeout(function () {
        $alert.fadeOut(function () {
            $(this).remove();
        });
    }, 5000);
}

/**
 * Show notification message
 * @param {string} type - The notification type (success, error, info, warning)
 * @param {string} message - The notification message
 */
function showNotification(type, message) {
    // Remove existing notifications first
    $('.temp-notification').remove();

    // Determine alert class based on notification type
    const alertClass = type === 'success' ? 'mv-alert-success' :
        type === 'info' ? 'mv-alert-info' :
            type === 'warning' ? 'mv-alert-warning' : 'mv-alert-danger';

    // Create notification element
    const $notification = $(`
    <div class="mv-alert ${alertClass} temp-notification" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;">
      ${message}
    </div>
  `);

    // Add to body and set auto-remove
    $('body').append($notification);

    // Auto-remove after delay (longer for errors)
    const removeDelay = (type === 'error' || type === 'warning') ? 5000 : 3000;
    setTimeout(() => {
        $notification.fadeOut(() => $notification.remove());
    }, removeDelay);
}