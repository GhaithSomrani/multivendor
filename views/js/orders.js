/**
 * Complete orders.js file with global MPN input functionality and single manifest generation
 */

const verifiedOrderDetails = new Set();
let currentOrderDetailId = null;
let currentProductName = null;
let currentStatusName = null;
let currentStatusColor = null;

$(document).ready(function () {
    // Set initial colors for status dropdowns
    $('.order-line-status-select').each(function () {
        const selectedOption = $(this).find('option:selected');
        const color = selectedOption.css('background-color');
        $(this).css('background-color', color);
    });


    // Handle status change dropdown - FIXED VERSION for status type IDs
    $('.order-line-status-select').on('change', function () {
        const $select = $(this);
        const orderDetailId = $select.data('order-detail-id');
        const newStatusTypeId = $select.val(); // This is the status type ID
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
                id_status_type: newStatusTypeId, // Send as id_status_type
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

                    // Check if we should add this to the manifest using status type ID
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

    // Make status badges clickable for filtering - FIXED VERSION
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

            // Show only rows with matching status - updated to work with status type IDs
            $('.mv-table tbody tr').each(function () {
                const $row = $(this);
                const $statusSelect = $row.find('.mv-status-select');
                const $statusBadge = $row.find('.mv-status-badge');

                let rowStatus = '';

                if ($statusSelect.length) {
                    // Get the text of the selected option
                    rowStatus = $statusSelect.find('option:selected').text().trim().toLowerCase();
                } else if ($statusBadge.length) {
                    rowStatus = $statusBadge.text().trim().toLowerCase();
                }

                if (rowStatus.includes(status.toLowerCase())) {
                    $row.show();
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

        if (rowMpn == mpnValue) {
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

    // Handle bulk status apply - FIXED VERSION
    $('#apply-bulk-status').on('click', function () {
        const newStatusTypeId = $('#bulk-status-select').val(); // This is now status type ID

        if (!newStatusTypeId || selectedOrders.length === 0) {
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
                id_status_type: newStatusTypeId,
                comment: bulkChangeComment,
                token: ordersAjaxToken
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $.each(response.results, function (id, success) {
                        if (success) {
                            updateRowStatus(id, newStatusTypeId);
                        }
                    });

                    showNotification('success', response.message);
                    $.each(response.results, function (id, success) {
                        if (success == true) {

                            checkAndAddToManifestIfNeeded(id, newStatusTypeId);
                        }
                    });
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
     * Update row status after bulk update - FIXED VERSION
     */
    function updateRowStatus(id, newStatusTypeId) {
        const row = $(`tr[data-id="${id}"]`);
        const select = row.find('.order-line-status-select');

        if (select.length) {
            select.val(newStatusTypeId);

            // Update the visual styling
            const selectedOption = select.find('option:selected');
            const color = selectedOption.css('background-color');
            select.css('background-color', color);

            // Update data attribute
            select.data('original-status-type-id', newStatusTypeId);

            // Update row status attribute with status name for filtering
            const statusName = selectedOption.text().trim();
            row.attr('data-status', statusName.toLowerCase());
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
                const statusTypeId = response.status.id_order_line_status_type; // Use the ID, not the name

                $.ajax({
                    url: ordersAjaxUrl,
                    type: 'POST',
                    data: {
                        action: 'updateVendorStatus',
                        id_order_detail: orderDetailId,
                        id_status_type: statusTypeId,
                        comment: 'MPN verified by scanning',
                        token: ordersAjaxToken
                    },
                    dataType: 'json',
                    success: function (updateResponse) {
                        if (updateResponse.success) {
                            showNotification('success', 'MPN verified and status updated');


                            updateStatusInUI(orderDetailId, response.status.name, response.status.color);

                            // Add to manifest
                            addToManifest(orderDetailId);

                            // Mark as verified
                            $(`tr[data-id="${orderDetailId}"]`).addClass('mv-mpn-verified');

                            // Update status message
                            $('#mpn-status-message').text('Order line verified successfully. Ready for next scan.')
                                .removeClass('searching error')
                                .addClass('success');

                            location.reload(); // Reload the page to reflect changes
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
function addToManifest(orderDetailId, itemData = null) {
    orderDetailId = parseInt(orderDetailId);

    if (verifiedOrderDetails.has(orderDetailId)) {
        return;
    }

    let orderRef, productName, productMpn, quantity;

    if (itemData) {
        // Data from AJAX
        orderRef = '#' + itemData.id_order + '#' + itemData.id_order_detail;
        productName = itemData.product_name;
        productMpn = itemData.product_mpn;
        quantity = itemData.product_quantity;
    } else {
        // Data from current page table row
        const $row = $('tr[data-id="' + orderDetailId + '"]');
        orderRef = $row.find('td:nth-child(2) a').text();
        productName = $row.find('td:nth-child(3)').text();
        productMpn = $row.data('product-mpn');
        quantity = $row.find('td:nth-child(4)').text();
    }

    const timestamp = new Date().toLocaleTimeString();
    verifiedOrderDetails.add(orderDetailId);

    // Add to desktop manifest
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

    // Add to mobile manifest
    addToMobileManifest(orderDetailId, {
        order_reference: orderRef,
        product_name: productName,
        product_mpn: productMpn,
        product_quantity: quantity,
        timestamp: timestamp
    });

    // Update mobile count and show mobile block
    const mobileManifestCount = document.getElementById('mobile-manifest-count');
    const mobileManifestBlock = document.getElementById('mobile-pickup-manifest-block');
    if (mobileManifestCount) {
        mobileManifestCount.textContent = verifiedOrderDetails.size;
    }
    if (mobileManifestBlock) {
        mobileManifestBlock.style.display = 'block';
    }
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
                console.log('response.status', response.status)
                console.log('response.status', newStatus)

                // If the selected status matches the "add commission" status
                if (newStatus === response.status.id_order_line_status_type) {
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

    // Remove from desktop UI
    $('#manifest-items tr[data-order-detail-id="' + orderDetailId + '"]').remove();

    // Update desktop count
    $('#manifest-count').text(verifiedOrderDetails.size);

    // Hide desktop manifest block if empty
    if (verifiedOrderDetails.size === 0) {
        $('#pickup-manifest-block').hide();
    }

    // Remove from mobile manifest
    removeFromMobileManifest(orderDetailId);
}

/**
 * Check existing order lines on page load
 * and add those with the appropriate status to the manifest
 */
function checkExistingOrderLinesForManifest() {
    // Get ALL order details with "add commission" status via AJAX
    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'getAllManifestItems',
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.items) {
                response.items.forEach(function (item) {
                    addToManifest(item.id_order_detail, item);
                });
            }
        }
    });
}

/**
 * Load status history
 */
function loadStatusHistory(orderDetailId) {
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

/**
 * Open status comment modal
 */
function openStatusCommentModal(orderDetailId, productName, currentStatus, statusColor, statusTypeId) {
    console.log('Opening modal for order detail:', orderDetailId);

    // Check if this order detail is changeable
    if (!window.mvChangeableInfo || !window.mvChangeableInfo[orderDetailId]) {
        console.log('Order detail not changeable:', orderDetailId);
        return;
    }

    // Get allowed status transitions for this specific order detail
    const allowedTransitions = window.mvAllowedTransitions[orderDetailId] || {};
    console.log('Allowed transitions for order detail', orderDetailId, ':', allowedTransitions);

    if (Object.keys(allowedTransitions).length === 0) {
        showNotification('warning', changeableTranslations.noStatusAvailable);
        console.log('No transitions available for order detail:', orderDetailId);
        return;
    }

    // Store current order detail info
    currentOrderDetailId = orderDetailId;
    currentProductName = productName;
    currentStatusName = currentStatus;
    currentStatusColor = statusColor || '#777';
    currentStatusTypeId = statusTypeId;

    // Update modal content
    document.getElementById('productInfo').textContent = productName;

    const statusBadge = document.getElementById('currentStatusBadge');
    statusBadge.textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
    statusBadge.style.backgroundColor = currentStatusColor;

    // Populate status dropdown with ONLY allowed transitions
    populateAvailableStatuses(allowedTransitions);

    // Reset comment
    document.getElementById('statusComment').value = '';

    // Show modal
    document.getElementById('statusCommentModal').classList.add('mv-modal-open');
}

function populateAvailableStatuses(allowedTransitions) {
    const select = document.getElementById('newStatusSelect');
    const noStatusDiv = document.getElementById('noStatusAvailable');
    const statusInfoDiv = document.getElementById('statusInfo');
    const statusInfoText = document.getElementById('statusInfoText');
    const submitBtn = document.getElementById('submitStatusComment');

    // Clear existing options
    select.innerHTML = '<option value="">' + (changeableTranslations.selectNewStatus || 'Sélectionnez un nouveau statut...') + '</option>';

    console.log('Populating available statuses:', allowedTransitions);

    if (Object.keys(allowedTransitions).length === 0) {
        // No transitions available
        select.disabled = true;
        noStatusDiv.style.display = 'block';
        statusInfoDiv.style.display = 'none';
        submitBtn.disabled = true;
        console.log('No transitions available - disabling form');
        return;
    }

    // Hide warnings and enable controls
    select.disabled = false;
    noStatusDiv.style.display = 'none';
    submitBtn.disabled = false;

    // Show info about available transitions
    statusInfoDiv.style.display = 'block';
    const transitionCount = Object.keys(allowedTransitions).length;
    statusInfoText.textContent = `${transitionCount} transition(s) disponible(s) depuis le statut actuel`;

    // Add only the allowed status options
    Object.entries(allowedTransitions).forEach(([statusId, statusName]) => {
        const option = document.createElement('option');
        option.value = statusId;
        option.textContent = statusName;

        // Add color styling if available
        const statusColor = getStatusColor(statusName);
        if (statusColor) {
            option.style.backgroundColor = statusColor;
            option.style.color = 'white';
        }

        select.appendChild(option);
        console.log('Added status option:', statusId, statusName, statusColor);
    });

    console.log('Populated', Object.keys(allowedTransitions).length, 'status options');
}



function getStatusColor(statusName) {
    if (window.mvStatusColors && window.mvStatusColors[statusName]) {
        return window.mvStatusColors[statusName];
    }
    return '#777';
}

/**
 * Close status comment modal
 */
function closeStatusCommentModal() {
    document.getElementById('statusCommentModal').classList.remove('mv-modal-open');

    // Reset variables
    currentOrderDetailId = null;
    currentProductName = null;
    currentStatusName = null;
    currentStatusColor = null;
    currentStatusTypeId = null;

    // Reset form elements
    const select = document.getElementById('newStatusSelect');
    select.innerHTML = '<option value="">Sélectionnez un nouveau statut...</option>';
    select.disabled = false;

    document.getElementById('noStatusAvailable').style.display = 'none';
    document.getElementById('statusInfo').style.display = 'none';
    document.getElementById('statusComment').value = '';
    document.getElementById('submitStatusComment').disabled = false;
    document.getElementById('submitStatusComment').innerHTML = 'Mettre à jour le statut';
}

/**
 * Submit status update with comment
 */
function submitStatusWithComment() {
    const newStatusId = document.getElementById('newStatusSelect').value;
    const comment = document.getElementById('statusComment').value.trim();

    console.log('Submitting status change:', {
        orderDetailId: currentOrderDetailId,
        newStatusId: newStatusId,
        comment: comment
    });

    if (!newStatusId) {
        alert(changeableTranslations.selectNewStatus || 'Veuillez sélectionner un nouveau statut');
        return;
    }

    if (!currentOrderDetailId) {
        alert('Erreur: Aucun détail de commande sélectionné');
        return;
    }

    // Double-check that this transition is still allowed
    const allowedTransitions = window.mvAllowedTransitions[currentOrderDetailId] || {};
    if (!allowedTransitions[newStatusId]) {
        alert('Erreur: Cette transition de statut n\'est plus autorisée');
        console.error('Invalid transition attempted:', newStatusId, 'Allowed:', allowedTransitions);
        closeStatusCommentModal();
        return;
    }

    // Disable submit button and show loading
    const submitBtn = document.getElementById('submitStatusComment');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="mv-spinner"></span> Mise à jour...';

    // Make AJAX request
    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'updateVendorStatus',
            id_order_detail: currentOrderDetailId,
            id_status_type: newStatusId,
            comment: comment || 'Statut mis à jour via modal commentaire',
            token: ordersAjaxToken
        },
        success: function (data) {
            console.log('Status update response:', data);

            if (data.success) {
                // Update the status select in the table
                const statusSelect = document.getElementById(`status-select-${currentOrderDetailId}`);
                if (statusSelect) {
                    statusSelect.value = newStatusId;

                    // Update the visual styling
                    const selectedOption = statusSelect.querySelector(`option[value="${newStatusId}"]`);
                    if (selectedOption) {
                        const color = selectedOption.style.backgroundColor;
                        statusSelect.style.backgroundColor = color;
                    }
                }

                // Update the row status attribute for filtering
                const row = document.querySelector(`tr[data-id="${currentOrderDetailId}"]`);
                if (row) {
                    // Find the new status name
                    const newStatusName = Object.entries(window.mvVendorStatuses || {})
                        .find(([id, name]) => id == newStatusId)?.[1] || 'Unknown';
                    row.setAttribute('data-status', newStatusName.toLowerCase());
                }

                // Show success message
                showNotification('success', 'Statut mis à jour avec succès');

                // Close modal
                closeStatusCommentModal();

                // Optional: Refresh allowed transitions for this order detail
                // You might want to update the allowed transitions after a status change
                updateAllowedTransitionsForOrderDetail(currentOrderDetailId);

            } else {
                // Show error message
                showNotification('error', 'Erreur lors de la mise à jour du statut: ' + (data.message || 'Erreur inconnue'));

                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Mettre à jour le statut';
            }
        },
        error: function (xhr, status, error) {
            console.error('Error updating status:', error);
            showNotification('error', 'Erreur réseau survenue');

            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Mettre à jour le statut';
        }
    });
}

function updateAllowedTransitionsForOrderDetail(orderDetailId) {
    // This function refreshes the allowed transitions for a specific order detail
    // after a status change, in case the available transitions have changed

    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getChangeableInfo',
            order_detail_id: orderDetailId, // Get info for specific order detail
            token: ordersAjaxToken
        },
        success: function (data) {
            if (data.success && data.allowed_transitions) {
                // Update the allowed transitions for this order detail
                if (window.mvAllowedTransitions) {
                    window.mvAllowedTransitions[orderDetailId] = data.allowed_transitions[orderDetailId] || {};
                }

                // Update changeable status if provided
                if (data.changeable_info) {
                    if (window.mvChangeableInfo) {
                        window.mvChangeableInfo[orderDetailId] = data.changeable_info[orderDetailId] || false;
                    }
                }

                console.log('Updated transitions for order detail', orderDetailId, ':', window.mvAllowedTransitions[orderDetailId]);

                // Update the action button for this order detail if needed
                updateActionButtonForOrderDetail(orderDetailId);
            }
        },
        error: function (xhr, status, error) {
            console.warn('Could not update allowed transitions:', error);
        }
    });
}

function updateActionButtonForOrderDetail(orderDetailId) {
    const isChangeable = window.mvChangeableInfo && window.mvChangeableInfo[orderDetailId];
    const allowedTransitions = window.mvAllowedTransitions && window.mvAllowedTransitions[orderDetailId] || {};
    const hasTransitions = Object.keys(allowedTransitions).length > 0;

    // Find the action button for this order detail
    const actionCell = document.querySelector(`tr[data-id="${orderDetailId}"] .mv-status-actions`);
    if (!actionCell) return;

    const button = actionCell.querySelector('button, span');
    if (!button) return;

    if (isChangeable && hasTransitions) {
        // Make button active
        button.className = 'mv-btn-comment';
        button.disabled = false;
        button.onclick = function () {
            // You'll need to get the current product info for this
            const row = document.querySelector(`tr[data-id="${orderDetailId}"]`);
            const productName = row ? row.querySelector('.mv-product-name')?.textContent?.trim() : 'Unknown Product';
            const statusElement = row ? row.querySelector('.mv-status-select') : null;
            const currentStatus = statusElement ? statusElement.options[statusElement.selectedIndex]?.text : 'Unknown';
            const statusColor = statusElement ? statusElement.style.backgroundColor : '#777';
            const statusTypeId = statusElement ? statusElement.value : 0;

            openStatusCommentModal(orderDetailId, productName, currentStatus, statusColor, statusTypeId);
        };
    } else {
        // Make button disabled
        button.className = 'mv-btn-comment-disabled';
        button.disabled = true;
        button.onclick = null;
    }
}

/**
 * Utility function to check if order detail has available transitions
 */
function hasAvailableTransitions(orderDetailId) {
    const allowedTransitions = window.mvAllowedTransitions && window.mvAllowedTransitions[orderDetailId] || {};
    return Object.keys(allowedTransitions).length > 0;
}

/**
 * Utility function to get available transition count
 */
function getAvailableTransitionCount(orderDetailId) {
    const allowedTransitions = window.mvAllowedTransitions && window.mvAllowedTransitions[orderDetailId] || {};
    return Object.keys(allowedTransitions).length;
}

$(document).ready(function () {
    // Close modal when clicking backdrop
    $(document).on('click', '#statusCommentModal', function (e) {
        if (e.target === this) {
            closeStatusCommentModal();
        }
    });

    // Close modal with Escape key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#statusCommentModal').hasClass('mv-modal-open')) {
            closeStatusCommentModal();
        }
    });

    // Debug: Verify data is loaded
    console.log('Orders JS initialized with data:');
    console.log('- Changeable info loaded:', !!window.mvChangeableInfo);
    console.log('- Allowed transitions loaded:', !!window.mvAllowedTransitions);
    console.log('- Status colors loaded:', !!window.mvStatusColors);

    if (window.mvAllowedTransitions) {
        const totalOrderDetails = Object.keys(window.mvAllowedTransitions).length;
        const changeableOrderDetails = Object.values(window.mvChangeableInfo || {}).filter(Boolean).length;
        console.log(`- ${totalOrderDetails} order details total, ${changeableOrderDetails} changeable`);
    }
});


// Mobile Functions - Add these to your existing orders.js

// Mobile variables
let isSelectMode = false;
let selectedMobileOrders = new Set();

/**
 * Initialize mobile functionality
 */
function initializeMobileFunctionality() {
    if (window.matchMedia('(max-width: 768px)').matches) {
        initializeMobileOrderHandlers();
        initializeMobileBulkActions();
        initializeMobileMPNScanner();
        initializeMobileStatusSelects();
        initializeMobileHistoryButtons();
        initializeMobileManifest();
    }
}

/**
 * Initialize mobile order handlers
 */
function initializeMobileOrderHandlers() {
    const checkboxes = document.querySelectorAll('.mv-mobile-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            handleMobileCheckboxChange(this);
        });
    });
}

/**
 * Initialize mobile history buttons
 */
function initializeMobileHistoryButtons() {
    const historyButtons = document.querySelectorAll('.mv-mobile-btn-history');
    historyButtons.forEach(button => {
        button.addEventListener('click', function () {
            const orderDetailId = this.dataset.orderDetailId;
            if (typeof viewStatusHistory === 'function') {
                viewStatusHistory(orderDetailId);
            }
        });
    });
}

/**
 * Toggle select all functionality for mobile
 */
function toggleSelectAll() {
    isSelectMode = !isSelectMode;
    const selectButton = document.querySelector('.mv-btn-select-mobile');
    const bulkActions = document.getElementById('mobileBulkActions');
    const checkboxes = document.querySelectorAll('.mv-mobile-checkbox');

    if (isSelectMode) {
        selectButton.innerHTML = '<i class="mv-icon">✖️</i> Annuler';
        selectButton.classList.add('active');
        bulkActions.style.display = 'block';

        checkboxes.forEach(checkbox => {
            checkbox.style.display = 'block';
        });
    } else {
        selectButton.innerHTML = '<i class="mv-icon">☑️</i> Sélectionner';
        selectButton.classList.remove('active');
        bulkActions.style.display = 'none';
        selectedMobileOrders.clear();

        checkboxes.forEach(checkbox => {
            checkbox.style.display = 'none';
            checkbox.checked = false;
        });

        updateMobileSelectedCount();
    }
}

/**
 * Handle mobile checkbox changes
 */
function handleMobileCheckboxChange(checkbox) {
    const orderId = checkbox.dataset.id;

    if (checkbox.checked) {
        selectedMobileOrders.add(orderId);
    } else {
        selectedMobileOrders.delete(orderId);
    }

    updateMobileSelectedCount();
}

/**
 * Update mobile selected count
 */
function updateMobileSelectedCount() {
    const countElement = document.getElementById('mobile-selected-count');
    const applyButton = document.getElementById('mobile-apply-bulk-status');
    const selectElement = document.getElementById('mobile-bulk-status-select');

    if (countElement) {
        countElement.textContent = selectedMobileOrders.size;
    }

    const hasSelection = selectedMobileOrders.size > 0;
    if (applyButton) {
        applyButton.disabled = !hasSelection;
    }
    if (selectElement) {
        selectElement.disabled = !hasSelection;
    }
}

/**
 * Initialize mobile bulk actions
 */
function initializeMobileBulkActions() {
    const applyButton = document.getElementById('mobile-apply-bulk-status');
    if (applyButton) {
        applyButton.addEventListener('click', function () {
            applyMobileBulkStatus();
        });
    }
}

/**
 * Apply bulk status change on mobile
 */
function applyMobileBulkStatus() {
    const selectElement = document.getElementById('mobile-bulk-status-select');
    const newStatus = selectElement.value;

    if (!newStatus) {
        showMobileNotification('Veuillez sélectionner un statut', 'warning');
        return;
    }

    if (selectedMobileOrders.size === 0) {
        showMobileNotification('Aucune commande sélectionnée', 'warning');
        return;
    }

    if (!confirm(bulkStatusChangeConfirmText)) {
        return;
    }

    const applyButton = document.getElementById('mobile-apply-bulk-status');
    const originalText = applyButton.textContent;
    applyButton.textContent = processingText;
    applyButton.disabled = true;

    // Use existing bulk processing function
    if (typeof processBulkStatusChange === 'function') {
        const orderIds = Array.from(selectedMobileOrders);
        processBulkStatusChange(orderIds, newStatus)
            .then(results => {
                const successCount = results.filter(r => r.success).length;
                const errorCount = results.length - successCount;

                if (successCount > 0) {
                    showMobileNotification(`${successCount} ${successStatusText}`, 'success');
                }
                if (errorCount > 0) {
                    showMobileNotification(`${errorCount} ${errorStatusText}`, 'error');
                }

                setTimeout(() => window.location.reload(), 2000);
            })
            .catch(error => {
                console.error('Bulk status change error:', error);
                showMobileNotification('Erreur lors de la mise à jour', 'error');
            })
            .finally(() => {
                applyButton.textContent = originalText;
                applyButton.disabled = false;
            });
    } else {
        showMobileNotification('Fonction de mise à jour groupée non disponible', 'error');
        applyButton.textContent = originalText;
        applyButton.disabled = false;
    }
}

/**
 * Initialize mobile MPN scanner
 */
function initializeMobileMPNScanner() {
    const mpnInput = document.getElementById('mobile-global-mpn-input');
    if (mpnInput) {
        mpnInput.addEventListener('input', function () {
            handleMobileMPNScan(this.value);
        });

        mpnInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    }
}

/**
 * Handle mobile MPN scan
 */
function handleMobileMPNScan(mpnValue) {
    const statusMessage = document.getElementById('mobile-mpn-status-message');

    if (!mpnValue.trim()) {
        if (statusMessage) {
            statusMessage.textContent = 'Prêt à scanner';
            statusMessage.className = 'mv-mobile-status-message';
        }
        return;
    }

    const orderItems = document.querySelectorAll('.mv-mobile-order-item');
    let found = false;

    orderItems.forEach(item => {
        const productMPN = item.dataset.productMpn;
        if (productMPN && productMPN.toLowerCase().includes(mpnValue.toLowerCase())) {
            item.scrollIntoView({ behavior: 'smooth', block: 'center' });
            item.style.border = '3px solid #10b981';
            item.style.backgroundColor = '#f0fdf4';

            setTimeout(() => {
                item.style.border = '';
                item.style.backgroundColor = '';
            }, 3000);

            found = true;
        }
    });

    if (statusMessage) {
        if (found) {
            statusMessage.textContent = `Produit trouvé: ${mpnValue}`;
            statusMessage.className = 'mv-mobile-status-message success';
        } else {
            statusMessage.textContent = `Aucun produit trouvé: ${mpnValue}`;
            statusMessage.className = 'mv-mobile-status-message error';
        }
    }
}

/**
 * Initialize mobile status selects
 */
function initializeMobileStatusSelects() {
    const statusSelects = document.querySelectorAll('.mv-mobile-status-select.order-line-status-select');

    statusSelects.forEach(select => {
        select.addEventListener('change', function () {
            const orderDetailId = this.dataset.orderDetailId;
            const newStatus = this.value;
            const originalStatus = this.dataset.originalStatusTypeId;

            if (newStatus && newStatus !== originalStatus) {
                updateMobileOrderLineStatus(orderDetailId, newStatus, this);
            }
        });
    });
}

/**
 * Update order line status on mobile
 */
function updateMobileOrderLineStatus(orderDetailId, newStatusId, selectElement) {
    // Use existing desktop function
    if (typeof updateOrderLineStatus === 'function') {
        selectElement.disabled = true;
        const loadingOption = document.createElement('option');
        loadingOption.value = '';
        loadingOption.text = 'Mise à jour...';
        loadingOption.selected = true;
        selectElement.insertBefore(loadingOption, selectElement.firstChild);

        updateOrderLineStatus(orderDetailId)
            .then(() => {
                showMobileNotification('Statut mis à jour avec succès', 'success');
                setTimeout(() => window.location.reload(), 1000);
            })
            .catch(error => {
                console.error('Error:', error);
                showMobileNotification('Erreur lors de la mise à jour', 'error');
                selectElement.removeChild(loadingOption);
                selectElement.disabled = false;
                selectElement.value = selectElement.dataset.originalStatusTypeId;
            });
    } else {
        showMobileNotification('Fonction de mise à jour non disponible', 'error');
    }
}

/**
 * Initialize mobile manifest functionality
 */
function initializeMobileManifest() {
    const printButton = document.getElementById('mobile-print-manifest-btn');
    if (printButton) {
        printButton.addEventListener('click', function () {
            // Call the same function as desktop version
            if (typeof printPickupManifest === 'function') {
                printPickupManifest();
            } else {
                showMobileNotification('Fonction d\'impression non disponible', 'warning');
            }
        });
    }

    syncMobileManifest();
}

function syncMobileManifest() {
    const mobileManifestItems = document.getElementById('mobile-manifest-items');
    const mobileManifestCount = document.getElementById('mobile-manifest-count');
    const mobileManifestBlock = document.getElementById('mobile-pickup-manifest-block');

    if (!mobileManifestItems) return;

    // Clear existing mobile manifest
    mobileManifestItems.innerHTML = '';

    // Sync with desktop manifest data
    if (verifiedOrderDetails && verifiedOrderDetails.size > 0) {
        verifiedOrderDetails.forEach(orderDetailId => {
            const desktopRow = document.querySelector(`#manifest-items tr[data-order-detail-id="${orderDetailId}"]`);
            if (desktopRow) {
                addToMobileManifest(orderDetailId, {
                    order_reference: desktopRow.cells[0].textContent,
                    product_name: desktopRow.cells[1].textContent,
                    product_mpn: desktopRow.cells[2].textContent,
                    product_quantity: desktopRow.cells[3].textContent,
                    timestamp: desktopRow.cells[4].textContent
                });
            }
        });

        // Update count and show block
        if (mobileManifestCount) {
            mobileManifestCount.textContent = verifiedOrderDetails.size;
        }
        if (mobileManifestBlock) {
            mobileManifestBlock.style.display = 'block';
        }
    } else {
        // Hide mobile manifest block if empty
        if (mobileManifestBlock) {
            mobileManifestBlock.style.display = 'none';
        }
        if (mobileManifestCount) {
            mobileManifestCount.textContent = '0';
        }
    }
}


function addToMobileManifest(orderDetailId, itemData) {
    const mobileManifestItems = document.getElementById('mobile-manifest-items');
    if (!mobileManifestItems) return;

    // Check if item already exists in mobile manifest
    const existingItem = mobileManifestItems.querySelector(`[data-order-detail-id="${orderDetailId}"]`);
    if (existingItem) return;

    const manifestItem = document.createElement('div');
    manifestItem.className = 'mv-mobile-manifest-item';
    manifestItem.setAttribute('data-order-detail-id', orderDetailId);

    manifestItem.innerHTML = `
        <div class="mv-mobile-manifest-header">
            <span class="mv-mobile-manifest-ref">${itemData.order_reference}</span>
            <span class="mv-mobile-manifest-qty">Qty: ${itemData.product_quantity}</span>
        </div>
        <div class="mv-mobile-manifest-product">${itemData.product_name}</div>
        <div class="mv-mobile-manifest-mpn">${itemData.product_mpn}</div>
        <div class="mv-mobile-manifest-time" style="font-size: 12px; color: #999; margin-top: 8px;">
            Vérifié: ${itemData.timestamp}
        </div>
    `;

    mobileManifestItems.appendChild(manifestItem);
}

function removeFromMobileManifest(orderDetailId) {
    const mobileManifestItems = document.getElementById('mobile-manifest-items');
    if (!mobileManifestItems) return;

    const existingItem = mobileManifestItems.querySelector(`[data-order-detail-id="${orderDetailId}"]`);
    if (existingItem) {
        existingItem.remove();
    }

    // Update count
    const mobileManifestCount = document.getElementById('mobile-manifest-count');
    if (mobileManifestCount && verifiedOrderDetails) {
        mobileManifestCount.textContent = verifiedOrderDetails.size;
    }

    // Hide block if empty
    const mobileManifestBlock = document.getElementById('mobile-pickup-manifest-block');
    if (mobileManifestBlock && (!verifiedOrderDetails || verifiedOrderDetails.size === 0)) {
        mobileManifestBlock.style.display = 'none';
    }
}
$(document).ready(function () {
    if (window.innerWidth <= 768) {
        initializeMobileManifest();
    }
});

// Update mobile manifest when window is resized
$(window).resize(function () {
    if (window.innerWidth <= 768) {
        syncMobileManifest();
    }
});



// Add mobile initialization to existing DOMContentLoaded
$(document).ready(function () {
    // Your existing initialization code here...

    // Add mobile initialization
    initializeMobileFunctionality();

    // Make functions globally available
    window.toggleSelectAll = toggleSelectAll;
});

function changePerPage(value) {

    const url = new URL(window.location.href);
    url.searchParams.delete('page');
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}