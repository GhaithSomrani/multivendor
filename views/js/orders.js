/**
 * Complete orders.js file with global MPN input functionality and single manifest generation
 */

const verifiedOrderDetails = new Set();
let currentProductName = null;
let currentStatusName = null;
let currentStatusColor = null;
let filter = [];
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


                if (newStatus === response.status.id_order_line_status_type) {
                    if (!verifiedOrderDetails.has(parseInt(orderDetailId))) {

                        showNotification('success', 'Added to pickup manifest');
                    }
                }
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
                html += '<pre class="mv-history-comment">' + entry.comment + ' </pre>';
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

    var filter_data = {};
    $('.mv-filter-input').each(function () {
        var value = $(this).val();
        var name = $(this).data('filter');
        if (name == 'datefilter') {
            value = value.split(' - ');
            filter_data['date_from'] = value[0];
            filter_data['date_to'] = value[1];
        }
        filter_data[name] = value;
    })

    $.ajax({
        url: ordersAjaxLink,
        type: 'POST',
        data: {
            ajax: true,
            filter: filter_data,
            action: 'getExportFilteredCSV',
            token: ordersAjaxToken
        },
        xhrFields: {
            responseType: 'blob' // Important for file download
        },
        success: function (response, status, xhr) {
            // Create download link
            var blob = new Blob([response], { type: 'text/csv' });
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = 'vendor_orders_' + new Date().toISOString().split('T')[0] + '.csv';
            link.click();
        },
        error: function (xhr, status, error) {
        }
    });
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

    // Check if this order detail is changeable
    if (!window.mvChangeableInfo || !window.mvChangeableInfo[orderDetailId]) {
        return;
    }

    // Get allowed status transitions for this specific order detail
    const allowedTransitions = window.mvAllowedTransitions[orderDetailId] || {};

    if (Object.keys(allowedTransitions).length === 0) {
        showNotification('warning', changeableTranslations.noStatusAvailable);
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


    if (Object.keys(allowedTransitions).length === 0) {
        // No transitions available
        select.disabled = true;
        noStatusDiv.style.display = 'block';
        statusInfoDiv.style.display = 'none';
        submitBtn.disabled = true;
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
    });

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


                // Update the action button for this order detail if needed
                updateActionButtonForOrderDetail(orderDetailId);
            }
        },
        error: function (xhr, status, error) {
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



    if (window.mvAllowedTransitions) {
        const totalOrderDetails = Object.keys(window.mvAllowedTransitions).length;
        const changeableOrderDetails = Object.values(window.mvChangeableInfo || {}).filter(Boolean).length;
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
        initializeMobileMPNScanner();
        initializeMobileStatusSelects();
        initializeMobileHistoryButtons();
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
 * Initialize mobile MPN scanner
 */


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
                showMobileNotification('Erreur lors de la mise à jour', 'error');
                selectElement.removeChild(loadingOption);
                selectElement.disabled = false;
                selectElement.value = selectElement.dataset.originalStatusTypeId;
            });
    } else {
        showMobileNotification('Fonction de mise à jour non disponible', 'error');
    }
}

function getcurrentorderdetails(orderDetailId) {
    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'GetOrderDetail',
            id_order_detail: orderDetailId,
            token: ordersAjaxToken
        },
        success: function (data) {
            if (data.success) {
                $('#currentoutodstock-image').attr('src', data.orderDetail.imageUrl);
                $('#currentoutodstock-name').html(data.orderDetail.product_name + ' (X' + data.orderDetail.product_quantity + ')');
                $('#currentoutodstock-brand').html(data.orderDetail.brand);
                $('#currentoutodstock-price').html("Prix Public : " + parseFloat(data.orderDetail.product_price).toFixed(2));
                $('#currentoutodstock-sku').html("Reférence : " + data.orderDetail.product_reference);
                $('#currentoutodstock-mpn').html("Code-barre : " + data.orderDetail.product_mpn);

            }
        }
    })
}

function closeOutOfStockModal() {
    document.getElementById('outofstock-modal').classList.remove('mv-modal-open');
    selectedSuggestions = [];
    let variantSelected = $('#variant-selected');
    variantSelected.empty();
    $('#product-search-input').val('');

}
$(document).ready(function () {
    $('#no-suggestion').on('change', function () {
        if (this.checked) {
            noSuggestion = true;
            $('#outofstock-btn').attr('disabled', false);
        } else {
            noSuggestion = false;
            if (selectedSuggestions.length < 1) {
                $('#outofstock-btn').attr('disabled', true);
            }
        }
    })


})

function confirmOutOfStock() {
    let comment = '';
    let orderDetailId = currentOrderDetailId;
    let statusTypeId = $('#outofstock-btn').val();
    if (noSuggestion) {
        selectedSuggestions = [];
        comment = $('#no-suggestion').val()
    } else {
        let suggestion = selectedSuggestions.join('\n \t');
        let customComment = $('#input-comment').val();
        if (customComment != '') {
            comment += 'Notes : ' + customComment + '\n \n';
        }
        if (suggestion != '') {
            comment += 'Suggestion de mon catalogue : \n \t' + suggestion;
        }
    }


    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'updateVendorStatus',
            id_order_detail: orderDetailId,
            id_status_type: statusTypeId,
            comment: comment,
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (updateResponse) {
            if (updateResponse.success) {
                showNotification('success', updateResponse.message);
                closeOutOfStockModal();
                setTimeout(() => window.location.reload(), 1000);
            }
        }
    })
    // closeOutOfStockModal();

}

function mkAvailble(orderDetailId, statusTypeId) {
    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'updateVendorStatus',
            id_order_detail: orderDetailId,
            id_status_type: statusTypeId,
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (updateResponse) {
            if (updateResponse.success) {
                showNotification('success', updateResponse.message);
                closeOutOfStockModal();
                setTimeout(() => window.location.reload(), 1000);
            }
        }
    })
}





// Add mobile initialization to existing DOMContentLoaded
$(document).ready(function () {
    // Your existing initialization code here...

    // Add mobile initialization
    initializeMobileFunctionality();

    // Make functions globally available
});

function changePerPage(value) {

    const url = new URL(window.location.href);
    url.searchParams.delete('page');
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}


let currentOrderDetailId = null;
let selectedSuggestions = [];
let noSuggestion = false;
function openOutOfStockModal(orderDetailId) {
    currentOrderDetailId = orderDetailId;
    selectedSuggestions = [];
    document.getElementById('generated-comment').value = '';
    document.getElementById('search-results').innerHTML = '';
    // document.getElementById('variants-container').innerHTML = '';
    getcurrentorderdetails(orderDetailId);
    document.getElementById('outofstock-modal').classList.add('mv-modal-open');

    searchProducts();
    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            currentOrderDetailId: currentOrderDetailId,
            action: 'getGefilterDetails',
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                $('#filter-pricefrom').html('Min : ' + data.priceFrom);
                $('#filter-priceto').html('Max : ' + data.priceTo);
                $('#filter-category').html('Categorie : ' + data.category);
            }
        }
    })
}
function searchProducts(page = 1, limit = 18) {
    const search = document.getElementById('product-search-input').value;

    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'searchOutOfStockProducts',
            currentOrderDetailId: currentOrderDetailId,
            search: search,
            token: ordersAjaxToken,
            page: page,
            limit: limit
        },
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                $('#search-results').html(data.html);

                let paginationHtml = '';
                if (data.pagination && data.pagination.total_pages > 1) {
                    paginationHtml += '<div class="mv-pagination">';

                    if (data.pagination.page > 1) {
                        paginationHtml += `<button class="mv-page-btn" data-page="${data.pagination.page - 1}">← Précédent</button>`;
                    }

                    paginationHtml += `<span class="mv-page-info">Page ${data.pagination.page} / ${data.pagination.total_pages}</span>`;

                    if (data.pagination.page < data.pagination.total_pages) {
                        paginationHtml += `<button class="mv-page-btn" data-page="${data.pagination.page + 1}">Suivant →</button>`;
                    }

                    paginationHtml += '</div>';
                }

                $('#search-outofstock-pagination').html(paginationHtml);

                $('.mv-page-btn').on('click', function () {
                    const newPage = $(this).data('page');
                    searchProducts(newPage, limit);
                });
            } else {
                $('#search-results').html('<div class="mv-no-results">Aucun produit trouvé.</div>');
            }
        },
        error: function () {
            $('#search-results').html('<div class="mv-error">Erreur lors de la recherche.</div>');
        }
    });
}


function addSuggestion(idProduct, btn) {
    const container = $(btn).closest('.mv-payment-header');
    const selects = container.find('.variant-select');
    let attributes = [];
    selects.each(function () {
        const text = $(this).find('option:selected').text();
        const id = $(this).find('option:selected').val();
        const group = $(this).data('group');
        if (text && text !== group) {
            attributes.push({ name: text, id: id });
        }
    });

    $.ajax({
        url: ordersAjaxUrl,
        type: 'POST',
        data: {
            action: 'addOutOfStockSuggestion',
            id_product: idProduct,
            attributes: attributes,
            id_order_detail: currentOrderDetailId,
            token: ordersAjaxToken
        },
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                if (!selectedSuggestions.includes(data.suggestions)) {
                    selectedSuggestions.push(data.suggestions);
                    updateVariantSelect();
                }
            }
        }
    });
}
function updateVariantSelect() {

    if (selectedSuggestions.length < 1 && !noSuggestion && $('#input-comment').val() == '') {
        $('#outofstock-btn').attr('disabled', true);
    } else {
        $('#outofstock-btn').attr('disabled', false);
    }
    if (selectedSuggestions.length < 1) {
        $('#variant-selected').hide();

    }
    let variantSelected = $('#variant-selected');

    variantSelected.show();
    variantSelected.empty();
    selectedSuggestions.forEach(element => {

        item = $('<div>', {
            class: 'variant-selected-item',
        }).appendTo(variantSelected);

        textspan = $('<span>', {
            text: element
        }).appendTo(item);

        $('<span>', {
            class: 'delete-btn',
            text: '❌',
            click: function () {
                selectedSuggestions = selectedSuggestions.filter(item => item !== element);
                updateVariantSelect();
            }
        }).appendTo(item);


    });

}


$(document).on('change', '.variant-select', function () {
    const container = $(this).closest('.mv-payment-header');
    const selects = container.find('.variant-select');
    const allSelected = selects.toArray().every(sel => $(sel).val() !== '');
    const button = container.find('.suggest-btn');

    if (allSelected) {
        button.prop('disabled', false);
    } else {
        button.prop('disabled', true);
    }
});

$(document).ready(function () {
    $('input[name="filter[datefilter]"]').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Effacer',
            applyLabel: 'Appliquer',
            format: 'YYYY-MM-DD',
            separator: ' à ',
        },
        opens: 'left',
        maxDate: moment()
    });

    $('input[name="filter[datefilter]"]').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format(
            'YYYY-MM-DD'));
    });

    $('input[name="filter[datefilter]"]').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });
});


$(document).ready(function () {

    $('#input-comment').on('input', function () {
        if (selectedSuggestions.length < 1 && $(this).val() == '' && !noSuggestion) {
            $('#outofstock-btn').attr('disabled', true);
        } else {
            $('#outofstock-btn').attr('disabled', false);
        }
    });

    $('.input-search-block button').on('click', function () {
        searchProducts();
    })
    $('#product-search-input').on('keypress', function (e) {
        if (e.which === 13) searchProducts();
    });

});


function applyFilters() {
    var form = $('#mv-filter-form');

    // Remove any previously added hidden inputs
    form.find('input[type="hidden"]').remove();
    var currentUrl = new URL(window.location.href);
    currentUrl.search = '';
    history.pushState({}, '', currentUrl.toString());



    // Collect and filter inputs
    $('.mv-filter-input').each(function () {
        var value = $(this).val();
        var name = $(this).attr('name');

        if (value !== '') {

            if (name === 'filter[datefilter]') {
                var parts = value.split(' - ');
                var from = parts[0] ? parts[0].trim() : '';
                var to = parts[1] ? parts[1].trim() : '';

                if (from) {
                    $('<input>', {
                        type: 'hidden',
                        name: 'filter[date_from]',
                        value: from
                    }).appendTo(form);
                }

                if (to) {
                    $('<input>', {
                        type: 'hidden',
                        name: 'filter[date_to]',
                        value: to
                    }).appendTo(form);
                }

            } else {
                $('<input>', {
                    type: 'hidden',
                    name: name,
                    value: value
                }).appendTo(form);
            }
        }
    });

    form.submit();
}

$('.mv-filter-input').keypress(function (e) {
    if (e.which === 13) {
        applyFilters();
    }
});

$('#apply-filter').on('click', function (e) {
    e.preventDefault();
    applyFilters();
});

$('#reset-filter').on('click', function (e) {
    e.preventDefault();
    $('.mv-filter-input').val('');
    $('#mv-filter-form').submit();
});

