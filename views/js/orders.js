
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
    setTimeout(function() {
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

    // Apply bulk status change
    $('#apply-bulk-status').on('click', function () {
        const newStatus = $('#bulk-status-select').val();

        if (!newStatus || selectedOrders.length === 0) {
            return;
        }

        // Show confirmation dialog
        if (!confirm(bulkStatusChangeConfirmText)) {
            return;
        }

        // Disable controls during processing
        $('#apply-bulk-status').prop('disabled', true).text(processingText);

        // Send the bulk update request
        $.ajax({
            url: ordersAjaxUrl,
            type: 'POST',
            data: {
                ajax: true,
                action: 'bulkUpdateVendorStatus', // Use this action name
                order_detail_ids: selectedOrders,
                status: newStatus,
                comment: bulkChangeComment,
                token: ordersAjaxToken
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    // Update statuses in the UI for successfully updated rows
                    $.each(response.results, function (id, success) {
                        if (success) {
                            updateRowStatus(id, newStatus);
                        }
                    });

                    showNotification('success', response.message);
                } else {
                    showNotification('error', response.message || 'Error updating statuses');
                }

                // Reset controls
                resetBulkControls();
            },
            error: function () {
                showNotification('error', errorStatusText);
                resetBulkControls();
            }
        });
    });

    // Update controls based on selection state
    function updateBulkControls() {
        const count = selectedOrders.length;

        // Update count display
        $('#selected-count').text(count + ' ' + selectedText);

        // Enable/disable bulk controls
        $('#bulk-status-select, #apply-bulk-status').prop('disabled', count === 0);
    }

    // Check if all bulk operations are complete
    function checkBulkCompletion(completed, total, success, error) {
        if (completed === total) {
            // All requests finished - show results
            let message = '';

            if (success > 0) {
                message += success + ' ' + successStatusText + '\n';
            }

            if (error > 0) {
                message += error + ' ' + errorStatusText;
            }

            // Show completion message
            showNotification(success > 0 ? 'success' : 'error', message);

            // Reset the bulk controls
            resetBulkControls();
        }
    }

    // Update a row's status in the UI
    function updateRowStatus(id, newStatus) {
        const row = $(`tr[data-id="${id}"]`);
        const select = row.find('.order-line-status-select');

        // Update select value if present
        if (select.length) {
            select.val(newStatus);

            // Also update the data attribute for filtering
            row.attr('data-status', newStatus.toLowerCase());
        }

        // Uncheck the row
        row.find('.mv-row-checkbox').prop('checked', false);
    }

    // Reset bulk controls after operation
    function resetBulkControls() {
        // Reset UI elements
        $('#apply-bulk-status').prop('disabled', true).text(applyText);
        $('#bulk-status-select').val('');
        $('.mv-row-checkbox, #select-all-orders').prop('checked', false);

        // Clear selected orders
        selectedOrders = [];
        updateBulkControls();
    }

    // Show notification message
    function showNotification(type, message) {
        const alertClass = type === 'success' ? 'mv-alert-success' : 'mv-alert-danger';
        const alert = $(`<div class="mv-alert ${alertClass}">${message}</div>`);

        $('.mv-card-body').first().prepend(alert);

        // Auto-remove after delay
        setTimeout(function () {
            alert.fadeOut(function () {
                $(this).remove();
            });
        }, 5000);
    }
});