$(document).ready(function() {
    // Handle status change dropdown
      $('.order-line-status-select').each(function() {
        const selectedOption = $(this).find('option:selected');
        const color = selectedOption.css('background-color');
        $(this).css('background-color', color);
    });
    
    $('.order-line-status-select').on('change', function() {
        const $select = $(this);
        const orderDetailId = $select.data('order-detail-id');
        const newStatus = $select.val();
        const originalStatus = $select.data('original-status');
        
        // Show loading state
        $select.prop('disabled', true);
        
        // Make AJAX request
        $.ajax({
            url: ordersAjaxUrl,
            type: 'POST',
            data: {
                action: 'updateOrderLineStatus',
                id_order_detail: orderDetailId,
                status: newStatus,
                token: ordersAjaxToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the UI
                    $select.data('original-status', newStatus);
                    showSuccessMessage('Status updated successfully');
                    
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
            error: function() {
                // Revert to original status
                $select.val(originalStatus);
                showErrorMessage('Connection error. Please try again.');
            },
            complete: function() {
                // Re-enable the select
                $select.prop('disabled', false);
            }
        });
    });

    // Handle status history view
    $('.view-status-history').on('click', function() {
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
        success: function(response) {
            if (response.success) {
                displayStatusHistory(response.history);
            } else {
                $('#statusHistoryContent').html('<p class="mv-error">Could not load status history</p>');
            }
        },
        error: function() {
            $('#statusHistoryContent').html('<p class="mv-error">Connection error. Please try again.</p>');
        }
    });
}

function displayStatusHistory(history) {
    let html = '<div class="mv-history-list">';
    
    if (history.length === 0) {
        html += '<p class="mv-empty-state">No status history available</p>';
    } else {
        history.forEach(function(entry) {
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
    setTimeout(function() {
        $alert.fadeOut(function() {
            $(this).remove();
        });
    }, 3000);
}

function showErrorMessage(message) {
    const $alert = $('<div class="mv-alert mv-alert-danger mv-alert-dismissible">' + message + '</div>');
    $('.mv-card-body').first().prepend($alert);
    setTimeout(function() {
        $alert.fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}