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
        const newStatus = $select.val();
        const originalStatus = $select.data('original-status');

        // Show loading state
        $select.prop('disabled', true);

        // Make AJAX request to updateVendorStatus
        $.ajax({
            url: ordersAjaxUrl,
            type: 'POST',
            data: {
                action: 'updateVendorStatus',
                id_order_detail: orderDetailId,
                status: newStatus,
                comment: '', // Empty comment for quick status update
                token: ordersAjaxToken
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // Update the UI
                    $select.data('original-status', newStatus);
                    showSuccessMessage(response.message || 'Status updated successfully');

                    // Update the dropdown appearance
                    const selectedOption = $select.find('option:selected');
                    const color = selectedOption.css('background-color');
                    $select.css('background-color', color);
                } else {
                    // Revert to original status
                    $select.val(originalStatus);
                    showErrorMessage(response.message || 'Error updating status');
                }
            },
            error: function () {
                // Revert to original status
                $select.val(originalStatus);
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
});

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

function showSuccessMessage(message) {
    const $alert = $('<div class="mv-alert mv-alert-success mv-alert-dismissible">' + message + '</div>');
    $('.mv-card-body').first().prepend($alert);
    setTimeout(function () {
        $alert.fadeOut(function () {
            $(this).remove();
        });
    }, 3000);
}

function showErrorMessage(message) {
    const $alert = $('<div class="mv-alert mv-alert-danger mv-alert-dismissible">' + message + '</div>');
    $('.mv-card-body').first().prepend($alert);
    setTimeout(function () {
        $alert.fadeOut(function () {
            $(this).remove();
        });
    }, 5000);
}



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



function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');

    // File download
    if (window.navigator.msSaveOrOpenBlob) {
        window.navigator.msSaveOrOpenBlob(csvFile, filename);
    } else {
        downloadLink.href = URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        downloadLink.setAttribute('download', filename);
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
}
$(document).ready(function () {
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
});

$(document).ready(function () {
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

    function updateBulkControls() {
        const count = selectedOrders.length;

        $('#selected-count').text(count + ' ' + selectedText);

        $('#bulk-status-select, #apply-bulk-status').prop('disabled', count === 0);
    }

    function checkBulkCompletion(completed, total, success, error) {
        if (completed === total) {
            let message = '';

            if (success > 0) {
                message += success + ' ' + successStatusText + '\n';
            }

            if (error > 0) {
                message += error + ' ' + errorStatusText;
            }

            showNotification(success > 0 ? 'success' : 'error', message);

            resetBulkControls();
        }
    }

    function updateRowStatus(id, newStatus) {
        const row = $(`tr[data-id="${id}"]`);
        const select = row.find('.order-line-status-select');

        if (select.length) {
            select.val(newStatus);

            row.attr('data-status', newStatus.toLowerCase());
        }

        row.find('.mv-row-checkbox').prop('checked', false);
    }

    function resetBulkControls() {
        $('#apply-bulk-status').prop('disabled', true).text(applyText);
        $('#bulk-status-select').val('');
        $('.mv-row-checkbox, #select-all-orders').prop('checked', false);

        selectedOrders = [];
        updateBulkControls();
    }

    function showNotification(type, message) {
        const alertClass = type === 'success' ? 'mv-alert-success' : 'mv-alert-danger';
        const alert = $(`<div class="mv-alert ${alertClass}">${message}</div>`);

        $('.mv-card-body').first().prepend(alert);

        setTimeout(function () {
            alert.fadeOut(function () {
                $(this).remove();
            });
        }, 5000);
    }
    /**
     * MPN Verification and Manifest System
     * This handles real-time MPN verification and manifest generation
     */
    initMpnVerification();

});



/**
 * Initialize MPN verification system
 */
function initMpnVerification() {
    $('.mv-mpn-input:first').focus();

    $(document).on('input', '.mv-mpn-input', function () {
        const $input = $(this);
        const enteredMpn = $input.val().trim();
        const actualMpn = $input.data('product-mpn');
        const orderDetailId = $input.data('order-detail-id');
        const commissionAction = $input.data('commission-action');

        if (verifiedOrderDetails.has(orderDetailId) || commissionAction !== 'none') {
            return;
        }

        if (enteredMpn === actualMpn) {
            $input.prop('disabled', true)
                .addClass('is-valid')
                .css('background-color', '#d4edda');

            updateOrderLineStatus(orderDetailId);
        }
    });

    $('#print-manifest-btn').on('click', function () {
        if (verifiedOrderDetails.size > 0) {
            printPickupManifest(Array.from(verifiedOrderDetails));
        } else {
            showNotification('warning', 'No verified order lines to print');
        }
    });
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

                            updateStatusInUI(orderDetailId, newStatus, response.status.color);

                            addToManifest(orderDetailId);

                            $('.mv-mpn-input[data-order-detail-id="' + orderDetailId + '"]')
                                .data('commission-action', 'add');

                            focusNextInput(orderDetailId);
                        } else {
                            showNotification('error', updateResponse.message || 'Failed to update status');

                            $('.mv-mpn-input[data-order-detail-id="' + orderDetailId + '"]')
                                .prop('disabled', false)
                                .removeClass('is-valid')
                                .css('background-color', '');
                        }
                    },
                    error: function () {
                        showNotification('error', 'Connection error');

                        $('.mv-mpn-input[data-order-detail-id="' + orderDetailId + '"]')
                            .prop('disabled', false)
                            .removeClass('is-valid')
                            .css('background-color', '');
                    }
                });
            } else {
                showNotification('error', 'No suitable status found for commission');

                $('.mv-mpn-input[data-order-detail-id="' + orderDetailId + '"]')
                    .prop('disabled', false)
                    .removeClass('is-valid')
                    .css('background-color', '');
            }
        },
        error: function () {
            showNotification('error', 'Failed to get commission status');

            $('.mv-mpn-input[data-order-detail-id="' + orderDetailId + '"]')
                .prop('disabled', false)
                .removeClass('is-valid')
                .css('background-color', '');
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
    const $statusCell = $row.find('td:nth-child(6)');

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
  const reference = $('.mv-mpn-input[data-order-detail-id="' + orderDetailId + '"]').data('product-reference');
  const quantity = $row.find('td:nth-child(4)').text();
  const timestamp = new Date().toLocaleTimeString();
  
  verifiedOrderDetails.add(orderDetailId);
  
  $('#manifest-items').append(`
    <tr data-order-detail-id="${orderDetailId}">
      <td>${orderRef}</td>
      <td>${productName}</td>
      <td>${reference}</td>
      <td>${quantity}</td>
      <td>${timestamp}</td>
    </tr>
  `);
  
  $('#manifest-count').text(verifiedOrderDetails.size);
  $('#pickup-manifest-block').show();
}

/**
 * Print pickup manifest
 * @param {Array} orderDetailIds - Array of order detail IDs to include in manifest
 */
function printPickupManifest(orderDetailIds) {
    // Generate the URL for the pickup manifest controller
    const manifestUrl = window.location.origin + window.location.pathname +
        '?fc=module&module=multivendor&controller=multiawb&details=' +
        orderDetailIds.join(',');

    // Open in a new tab/window
    window.open(manifestUrl, '_blank');
}

/**
 * Focus on the next unverified input field
 * @param {number} currentOrderDetailId - The current order detail ID
 */
function focusNextInput(currentOrderDetailId) {
    const $inputs = $('.mv-mpn-input').not(':disabled');
    if ($inputs.length > 0) {
        // Find the current input's position
        const currentIndex = $inputs.index($('.mv-mpn-input[data-order-detail-id="' + currentOrderDetailId + '"]'));

        // Focus on the next input if available
        if (currentIndex < $inputs.length - 1) {
            $inputs.eq(currentIndex + 1).focus();
        }
    }
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

$(document).on('change', '.order-line-status-select', function () {
    const $select = $(this);
    const orderDetailId = $select.data('order-detail-id');
    const newStatus = $select.val();
    const originalStatus = $select.data('original-status');

    // Check if we should add this to the manifest
    checkAndAddToManifestIfNeeded(orderDetailId, newStatus);

    // Update the commission action data attribute if needed
    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'getStatusCommissionAction',
            status: newStatus,
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                // Update the data attribute on the select
                $select.data('commission-action', response.commission_action);

                // Also update the input field
                $('.mv-mpn-input[data-order-detail-id="' + orderDetailId + '"]')
                    .data('commission-action', response.commission_action);
            }
        }
    });
});
/**
 * Check if the selected status matches the "add commission" status
 * and add it to the manifest if it does
 * @param {number} orderDetailId - The order detail ID
 * @param {string} newStatus - The newly selected status
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
 * Initialize reference verification system
 */
function initReferenceVerification() {
    // Focus on the first input when page loads for immediate scanning
    $('.mv-mpn-input:first').focus();

    // Add existing order lines with "add commission" status to manifest
    checkExistingOrderLinesForManifest();

    // ... rest of the function stays the same ...
}

/**
 * Check all existing order lines on page load
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