/**
 * Complete Manage Orders JavaScript - Drag and Drop functionality with single manifest generation
 */

$(document).ready(function () {
    // Initialize drag and drop
    initializeDragAndDrop();

    // Handle checkbox selections
    handleCheckboxes();

    // Handle print actions
    handlePrintActions();

    // Update panel counts on load
    updatePanelCounts();

    // Initialize tooltips or other UI elements if needed
    initializeUIElements();
});

/**
 * Initialize drag and drop functionality
 */
function initializeDragAndDrop() {
    // Make order cards draggable (only from pending and ready panels)
    $('.draggable').each(function () {
        $(this).attr('draggable', true);
    });

    // Make cancelled/refunded orders non-draggable
    $('#noCommissionPanel .mv-order-card').each(function () {
        $(this).attr('draggable', false);
        $(this).removeClass('draggable');
    });

    // Drag start
    $(document).on('dragstart', '.draggable', function (e) {
        $(this).addClass('dragging');
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        e.originalEvent.dataTransfer.setData('text/html', this.innerHTML);
        e.originalEvent.dataTransfer.setData('orderDetailId', $(this).data('order-detail-id'));
        
        // Add visual feedback
        $(this).css('opacity', '0.5');
    });

    // Drag end
    $(document).on('dragend', '.draggable', function () {
        $(this).removeClass('dragging');
        $(this).css('opacity', '1');
    });

    // Drag over - only allow on pending and ready zones
    $('.mv-order-zone[data-status="pending"], .mv-order-zone[data-status="ready"]').on('dragover', function (e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
        $(this).addClass('drag-over');
    });

    // Prevent drop on no_commission zone
    $('.mv-order-zone[data-status="no_commission"]').on('dragover', function (e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'none';
    });

    // Drag leave
    $('.mv-order-zone').on('dragleave', function (e) {
        // Only remove class if we're actually leaving the zone (not entering a child)
        if (!$(this).has(e.relatedTarget).length) {
            $(this).removeClass('drag-over');
        }
    });

    // Drop - only on pending and ready zones
    $('.mv-order-zone[data-status="pending"], .mv-order-zone[data-status="ready"]').on('drop', function (e) {
        e.preventDefault();
        $(this).removeClass('drag-over');

        const orderDetailId = e.originalEvent.dataTransfer.getData('orderDetailId');
        const targetStatus = $(this).data('status');
        const draggedCard = $('.draggable[data-order-detail-id="' + orderDetailId + '"]');

        // Don't do anything if dropped in the same zone
        if (draggedCard.closest('.mv-order-zone').data('status') === targetStatus) {
            return;
        }

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

    // Show loading indicator on the card
    const $card = $('.mv-order-card[data-order-detail-id="' + orderDetailId + '"]');
    $card.addClass('updating');

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
        success: function (response) {
            $card.removeClass('updating');
            
            if (response.success) {
                showSuccessMessage('Status updated successfully');
                updatePanelCounts();

                // Update the card appearance
                updateCardAppearance(orderDetailId, newStatus);
                
                // Add visual confirmation
                $card.addClass('status-updated');
                setTimeout(function() {
                    $card.removeClass('status-updated');
                }, 2000);
            } else {
                showErrorMessage(response.message || 'Error updating status');
                // Reload page to reset positions
                setTimeout(function() {
                    location.reload();
                }, 1500);
            }
        },
        error: function (xhr, status, error) {
            $card.removeClass('updating');
            showErrorMessage('Error communicating with server: ' + error);
            // Reload page to reset positions
            setTimeout(function() {
                location.reload();
            }, 1500);
        }
    });
}

/**
 * Update card appearance after status change
 */
function updateCardAppearance(orderDetailId, newStatus) {
    const card = $('.mv-order-card[data-order-detail-id="' + orderDetailId + '"]');

    if (newStatus === 'ready') {
        // Add checkbox for ready status
        if (!card.find('.mv-order-select').length) {
            card.prepend('<div class="mv-order-select"><input type="checkbox" class="mv-order-checkbox" value="' + orderDetailId + '"></div>');
        }

        // Add print button
        if (!card.find('.mv-order-actions').length) {
            card.append('<div class="mv-order-actions"><button class="mv-btn-icon print-awb" data-order-detail-id="' + orderDetailId + '" title="Print AWB"><i class="mv-icon">🖨️</i></button></div>');
        }

        // Make sure card is draggable
        card.addClass('draggable').attr('draggable', true);
    } else if (newStatus === 'pending') {
        // Remove checkbox and print actions for pending status
        card.find('.mv-order-select, .mv-order-actions').remove();
        
        // Make sure card is draggable
        card.addClass('draggable').attr('draggable', true);
    } else if (newStatus === 'no_commission') {
        // Remove checkbox and actions for cancelled/refunded
        card.find('.mv-order-select, .mv-order-actions').remove();
        
        // Make card non-draggable
        card.removeClass('draggable').attr('draggable', false);
        
        // Update status badge
        const statusBadge = card.find('.mv-order-status');
        statusBadge.removeClass().addClass('mv-order-status mv-status-no-commission');
        statusBadge.text('No Commission');
    }
}

/**
 * Update panel counts
 */
function updatePanelCounts() {
    $('.mv-order-zone').each(function () {
        const count = $(this).find('.mv-order-card').length;
        const status = $(this).data('status');
        const panel = $(this).closest('.mv-order-panel');
        panel.find('.mv-count').text(count);
    });
}

/**
 * Handle checkbox selections
 */
function handleCheckboxes() {
    // Select all ready orders
    $('#selectAll').on('click', function () {
        $('.mv-order-zone[data-status="ready"] .mv-order-checkbox').prop('checked', true);
        updatePrintButtonState();
        showSuccessMessage('All ready orders selected');
    });

    // Deselect all
    $('#deselectAll').on('click', function () {
        $('.mv-order-checkbox').prop('checked', false);
        updatePrintButtonState();
        showSuccessMessage('All orders deselected');
    });

    // Individual checkbox change
    $(document).on('change', '.mv-order-checkbox', function () {
        updatePrintButtonState();
        
        const checkedCount = $('.mv-order-checkbox:checked').length;
        if (checkedCount > 0) {
            showNotification('info', checkedCount + ' order(s) selected for manifest');
        }
    });
}

/**
 * Update print button state
 */
function updatePrintButtonState() {
    const checkedCount = $('.mv-order-checkbox:checked').length;
    const $printButton = $('#printSelectedAwb');
    
    $printButton.prop('disabled', checkedCount === 0);
    
    if (checkedCount > 0) {
        $printButton.text(`Print ${checkedCount} Manifest${checkedCount > 1 ? 's' : ''}`);
    } else {
        $printButton.text('Print Selected AWBs');
    }
}

/**
 * Handle print actions
 */
function handlePrintActions() {
    // Single AWB print
    $(document).on('click', '.print-awb', function (e) {
        e.stopPropagation(); // Prevent card drag events
        const orderDetailId = $(this).data('order-detail-id');
        printSingleAwb(orderDetailId);
    });

    // Multiple AWB print
    $('#printSelectedAwb').on('click', function () {
        const selectedOrders = [];
        $('.mv-order-checkbox:checked').each(function () {
            selectedOrders.push($(this).val());
        });

        if (selectedOrders.length > 0) {
            printMultipleAwb(selectedOrders);
        } else {
            showErrorMessage('Please select at least one order to print');
        }
    });
}

/**
 * Print single AWB - Updated for single manifest generation
 */
function printSingleAwb(orderDetailId) {
    showNotification('info', 'Generating manifest...');
    
    // Use the manifest controller directly for single item
    const manifestUrl = window.location.origin + window.location.pathname +
        '?fc=module&module=multivendor&controller=manifest&id_order_detail=' + orderDetailId;

    // Open in a new tab/window
    window.open(manifestUrl, '_blank');
    
    showSuccessMessage('Manifest generated successfully');
}

/**
 * Print multiple AWBs - Updated for single manifest generation
 */
function printMultipleAwb(orderDetails) {
    if (!orderDetails || orderDetails.length === 0) {
        showErrorMessage('No orders selected for manifest generation');
        return;
    }
    
    showNotification('info', `Generating manifest for ${orderDetails.length} orders...`);
    
    // Use the manifest controller for multiple items
    const manifestUrl = window.location.origin + window.location.pathname +
        '?fc=module&module=multivendor&controller=manifest&details=' + orderDetails.join(',');

    // Open in a new tab/window
    window.open(manifestUrl, '_blank');
    
    showSuccessMessage(`Single manifest generated for ${orderDetails.length} orders`);
    
    // Optionally uncheck all checkboxes after printing
    $('.mv-order-checkbox').prop('checked', false);
    updatePrintButtonState();
}

/**
 * Initialize UI elements
 */
function initializeUIElements() {
    // Add hover effects for better UX
    $(document).on('mouseenter', '.mv-order-card.draggable', function() {
        $(this).addClass('hover-highlight');
    });
    
    $(document).on('mouseleave', '.mv-order-card.draggable', function() {
        $(this).removeClass('hover-highlight');
    });
    
    // Add loading states for buttons
    $(document).on('click', '.mv-btn-icon', function() {
        const $btn = $(this);
        const originalContent = $btn.html();
        
        $btn.html('<i class="mv-icon">⏳</i>').prop('disabled', true);
        
        setTimeout(function() {
            $btn.html(originalContent).prop('disabled', false);
        }, 2000);
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+A or Cmd+A to select all ready orders
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 65) {
            e.preventDefault();
            $('#selectAll').click();
        }
        
        // Escape to deselect all
        if (e.keyCode === 27) {
            $('#deselectAll').click();
        }
        
        // Ctrl+P or Cmd+P to print selected
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 80) {
            e.preventDefault();
            if (!$('#printSelectedAwb').prop('disabled')) {
                $('#printSelectedAwb').click();
            }
        }
    });
}

/**
 * Show success message
 */
function showSuccessMessage(message) {
    const alert = $('<div class="mv-alert mv-alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;">' + message + '</div>');
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
    const alert = $('<div class="mv-alert mv-alert-danger" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;">' + message + '</div>');
    $('body').append(alert);
    
    setTimeout(function () {
        alert.fadeOut(function () {
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
        <div class="mv-alert ${alertClass} temp-notification" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px; padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
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
 * Advanced features for power users
 */

// Auto-save selected items to localStorage
function saveSelectedItems() {
    const selected = [];
    $('.mv-order-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    localStorage.setItem('selectedManifestItems', JSON.stringify(selected));
}

// Restore selected items from localStorage
function restoreSelectedItems() {
    const saved = localStorage.getItem('selectedManifestItems');
    if (saved) {
        const selected = JSON.parse(saved);
        selected.forEach(function(id) {
            $('.mv-order-checkbox[value="' + id + '"]').prop('checked', true);
        });
        updatePrintButtonState();
    }
}

// Call restore on page load
$(document).ready(function() {
    restoreSelectedItems();
    
    // Save on checkbox change
    $(document).on('change', '.mv-order-checkbox', function() {
        saveSelectedItems();
    });
});

// Bulk operations
function selectOrdersByCustomer(customerName) {
    $('.mv-order-card').each(function() {
        const cardCustomer = $(this).find('.mv-customer-info .mv-customer-name').text().trim();
        if (cardCustomer.toLowerCase().includes(customerName.toLowerCase())) {
            $(this).find('.mv-order-checkbox').prop('checked', true);
        }
    });
    updatePrintButtonState();
}

function selectOrdersByDate(date) {
    $('.mv-order-card').each(function() {
        const cardDate = $(this).find('.mv-order-date').text().trim();
        if (cardDate.includes(date)) {
            $(this).find('.mv-order-checkbox').prop('checked', true);
        }
    });
    updatePrintButtonState();
}

// Performance optimization: Debounce drag operations
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Debounced panel count update
const debouncedPanelUpdate = debounce(updatePanelCounts, 300);

// Use debounced version for frequent operations
$(document).on('drop', '.mv-order-zone', debouncedPanelUpdate);