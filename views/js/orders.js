
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
    let csv = [];
    const table = document.querySelector('.mv-table');
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');

        for (let j = 0; j < cols.length - 1; j++) { // Skip last column (actions)
            let data = cols[j].innerText.replace(/,/g, '');

            // Handle status column
            if (j === 6 && i > 0) { // Status column
                const select = cols[j].querySelector('select');
                if (select) {
                    data = select.options[select.selectedIndex].text;
                }
            }

            row.push('"' + data + '"');
        }

        csv.push(row.join(','));
    }

    downloadCSV(csv.join('\n'), 'orders_export.csv');
}

function exportTableToExcel() {
    let tableHTML = '<table>';
    const table = document.querySelector('.mv-table');
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        tableHTML += '<tr>';
        const cols = rows[i].querySelectorAll('td, th');

        for (let j = 0; j < cols.length - 1; j++) {
            let data = cols[j].innerText;

            if (j === 6 && i > 0) {
                const select = cols[j].querySelector('select');
                if (select) {
                    data = select.options[select.selectedIndex].text;
                }
            }

            tableHTML += (i === 0 ? '<th>' : '<td>') + data + (i === 0 ? '</th>' : '</td>');
        }

        tableHTML += '</tr>';
    }
    tableHTML += '</table>';


    const blob = new Blob(['\ufeff', tableHTML], {
        type: 'application/vnd.ms-excel'
    });
    const url = URL.createObjectURL(blob);
    downloadFile(url, 'orders_export.xls');
}

function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    downloadFile(url, filename);
}

function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}