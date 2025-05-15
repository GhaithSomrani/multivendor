/**
 * Manage Orders JavaScript - Drag and Drop functionality
 */

$(document).ready(function() {
    // Initialize drag and drop
    initializeDragAndDrop();
    
    // Handle checkbox selections
    handleCheckboxes();
    
    // Handle print actions
    handlePrintActions();
});

/**
 * Initialize drag and drop functionality
 */
function initializeDragAndDrop() {
    // Make order cards draggable (only from pending and ready panels)
    $('.draggable').each(function() {
        $(this).attr('draggable', true);
    });
    
    // Make cancelled/refunded orders non-draggable
    $('#noCommissionPanel .mv-order-card').each(function() {
        $(this).attr('draggable', false);
        $(this).removeClass('draggable');
    });
    
    // Drag start
    $(document).on('dragstart', '.draggable', function(e) {
        $(this).addClass('dragging');
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        e.originalEvent.dataTransfer.setData('text/html', this.innerHTML);
        e.originalEvent.dataTransfer.setData('orderDetailId', $(this).data('order-detail-id'));
    });
    
    // Drag end
    $(document).on('dragend', '.draggable', function() {
        $(this).removeClass('dragging');
    });
    
    // Drag over - only allow on pending and ready zones
    $('.mv-order-zone[data-status="pending"], .mv-order-zone[data-status="ready"]').on('dragover', function(e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
        $(this).addClass('drag-over');
    });
    
    // Prevent drop on no_commission zone
    $('.mv-order-zone[data-status="no_commission"]').on('dragover', function(e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'none';
    });
    
    // Drag leave
    $('.mv-order-zone').on('dragleave', function() {
        $(this).removeClass('drag-over');
    });
    
    // Drop - only on pending and ready zones
    $('.mv-order-zone[data-status="pending"], .mv-order-zone[data-status="ready"]').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        
        const orderDetailId = e.originalEvent.dataTransfer.getData('orderDetailId');
        const targetStatus = $(this).data('status');
        const draggedCard = $('.draggable[data-order-detail-id="' + orderDetailId + '"]');
        
        // Move the card to new zone
        $(this).append(draggedCard);
        
        // Update status in backend
        updateOrderStatus(orderDetailId, targetStatus);
    });
}

/**
 * Update order status via AJAX
 */
function updateOrderStatus(orderDetailId, newStatus) {
    // Map panel status to actual order status
    const statusMap = {
        'pending': 'Processing',
        'ready': 'Shipped',
        'no_commission': 'Cancelled'
    };
    
    const actualStatus = statusMap[newStatus] || 'Processing';
    
    $.ajax({
        url: manageOrdersAjaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'updateOrderLineStatus',
            id_order_detail: orderDetailId,
            new_status: actualStatus,
            token: manageOrdersAjaxToken
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccessMessage('Status updated successfully');
                updatePanelCounts();
                
                // Update the card appearance
                updateCardAppearance(orderDetailId, newStatus);
            } else {
                showErrorMessage(response.message || 'Error updating status');
                // Reload page to reset positions
                location.reload();
            }
        },
        error: function() {
            showErrorMessage('Error communicating with server');
            location.reload();
        }
    });
}

/**
 * Update card appearance after status change
 */
function updateCardAppearance(orderDetailId, newStatus) {
    const card = $('.draggable[data-order-detail-id="' + orderDetailId + '"]');
    
    if (newStatus === 'ready') {
        // Add checkbox for ready status
        if (!card.find('.mv-order-select').length) {
            card.prepend('<div class="mv-order-select"><input type="checkbox" class="mv-order-checkbox" value="' + orderDetailId + '"></div>');
        }
        
        // Add print button
        if (!card.find('.mv-order-actions').length) {
            card.append('<div class="mv-order-actions"><button class="mv-btn-icon print-awb" data-order-detail-id="' + orderDetailId + '"><i class="mv-icon">🖨️</i></button></div>');
        }
    } else {
        // Remove checkbox and actions for other statuses
        card.find('.mv-order-select, .mv-order-actions').remove();
    }
    
    // Update status badge
    const statusBadge = card.find('.mv-order-status');
    if (newStatus === 'no_commission') {
        statusBadge.removeClass().addClass('mv-order-status mv-status-no-commission');
        statusBadge.text('No Commission');
    } else {
        // Keep existing status badge appearance
    }
}

/**
 * Update panel counts
 */
function updatePanelCounts() {
    $('.mv-order-zone').each(function() {
        const count = $(this).find('.mv-order-card').length;
        const status = $(this).data('status');
        $('#' + status + 'Panel .mv-count').text(count);
    });
}

/**
 * Handle checkbox selections
 */
function handleCheckboxes() {
    // Select all ready orders
    $('#selectAll').on('click', function() {
        $('.mv-order-zone[data-status="ready"] .mv-order-checkbox').prop('checked', true);
        updatePrintButtonState();
    });
    
    // Deselect all
    $('#deselectAll').on('click', function() {
        $('.mv-order-checkbox').prop('checked', false);
        updatePrintButtonState();
    });
    
    // Individual checkbox change
    $(document).on('change', '.mv-order-checkbox', function() {
        updatePrintButtonState();
    });
}

/**
 * Update print button state
 */
function updatePrintButtonState() {
    const checkedCount = $('.mv-order-checkbox:checked').length;
    $('#printSelectedAwb').prop('disabled', checkedCount === 0);
}

/**
 * Handle print actions
 */
function handlePrintActions() {
    // Single AWB print
    $(document).on('click', '.print-awb', function() {
        const orderDetailId = $(this).data('order-detail-id');
        printSingleAwb(orderDetailId);
    });
    
    // Multiple AWB print
    $('#printSelectedAwb').on('click', function() {
        const selectedOrders = [];
        $('.mv-order-checkbox:checked').each(function() {
            selectedOrders.push($(this).val());
        });
        
        if (selectedOrders.length > 0) {
            printMultipleAwb(selectedOrders);
        }
    });
}

/**
 * Print single AWB
 */
function printSingleAwb(orderDetailId) {
    $.ajax({
        url: manageOrdersAjaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'generateAwb',
            id_order_detail: orderDetailId,
            token: manageOrdersAjaxToken
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.open(response.awb_url, '_blank');
            } else {
                showErrorMessage(response.message || 'Error generating AWB');
            }
        },
        error: function() {
            showErrorMessage('Error communicating with server');
        }
    });
}

/**
 * Print multiple AWBs
 */
function printMultipleAwb(orderDetails) {
    $.ajax({
        url: manageOrdersAjaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'generateMultipleAwb',
            order_details: orderDetails,
            token: manageOrdersAjaxToken
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                window.open(response.awb_url, '_blank');
            } else {
                showErrorMessage(response.message || 'Error generating AWBs');
            }
        },
        error: function() {
            showErrorMessage('Error communicating with server');
        }
    });
}

/**
 * Show success message
 */
function showSuccessMessage(message) {
    const alert = $('<div class="mv-alert mv-alert-success">' + message + '</div>');
    $('.mv-main-content').prepend(alert);
    setTimeout(function() {
        alert.fadeOut(function() {
            $(this).remove();
        });
    }, 3000);
}

/**
 * Show error message
 */
function showErrorMessage(message) {
    const alert = $('<div class="mv-alert mv-alert-danger">' + message + '</div>');
    $('.mv-main-content').prepend(alert);
    setTimeout(function() {
        alert.fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}