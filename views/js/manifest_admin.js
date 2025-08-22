/**
 * Manifest Admin JavaScript
 * Handles vendor selection and order details loading for manifest management
 */

// Global function for vendor order details loading
function loadVendorOrderDetails(vendorId) {
    if (!vendorId || vendorId == '0') {
        $('#vendor-order-details-panel .panel-body').html(
            '<div class="alert alert-info">' +
            '<i class="icon-info-circle"></i> ' +
            'Please select a vendor to view order details.' +
            '</div>'
        );
        return;
    }

    $('#vendor-order-details-panel .panel-body').html(
        '<div class="alert alert-info">' +
        '<i class="icon-spinner icon-spin"></i> ' +
        'Loading order details...' +
        '</div>'
    );

    $.ajax({
        url: manifestAjaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'LoadVendorOrderDetails',
            vendor_id: vendorId,
            token: manifestToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                $('#vendor-order-details-panel').replaceWith(response.html);
                // Reinitialize event handlers after content replacement
                initializeOrderDetailsHandlers();
            } else {
                $('#vendor-order-details-panel .panel-body').html(
                    '<div class="alert alert-danger">' +
                    '<i class="icon-warning"></i> ' +
                    (response.message || 'Error loading order details.') +
                    '</div>'
                );
            }
        },
        error: function () {
            $('#vendor-order-details-panel .panel-body').html(
                '<div class="alert alert-danger">' +
                '<i class="icon-warning"></i> ' +
                'Error loading order details. Please try again.' +
                '</div>'
            );
        }
    });
}

function loadVendorAddress(vendorId) {
    $.ajax({
        url: manifestAjaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'LoadVendorAddress',
            vendor_id: vendorId,
            token: manifestToken
        },
        dataType: 'json',
        success: function (response) {
            console.log('Vendor address response:', response);
            if (response.success) {
                var addressSelect = $('select[name="id_address"]');
                addressSelect.empty();

                if (response.address && response.address.length > 0) {
                    $.each(response.address, function (index, addr) {
                        var option = $('<option></option>')
                            .attr('value', addr.id_address)
                            .html(addr.address_display);
                        addressSelect.append(option);
                    });
                } else {
                    addressSelect.append(
                        $('<option></option>')
                            .attr('value', '0')
                            .text('No addresses available for this vendor')
                    );
                }
            } else {
                console.error('Error loading vendor address:', response.message);
                alert(response.message || 'Error loading vendor address.');
            }
        },
        error: function () {
            console.error('Error loading vendor address');
            alert('Error loading vendor address. Please try again.');
        }
    });
}


// Initialize order details table handlers
function initializeOrderDetailsHandlers() {

    // make sure hidden input exists in the form
    if ($('#mv_manifest_form input[name="selected_order_details"]').length === 0) {
        $('#mv_manifest_form').append(
            $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'selected_order_details')
                .val('')
        );
    }

    $('#select-all-order-details').off('change').on('change', function () {
        var isChecked = $(this).is(':checked');
        $('.order-detail-checkbox').prop('checked', isChecked).trigger('change');
    });

    // Individual checkbox handlers
    $('.order-detail-checkbox').off('change').on('change', function () {
        var selectedValues = []
        // collect checked values
        selectedValues.push($('.order-detail-checkbox:checked').map(function () {
            return $(this).val();
        }).get());
        $('#mv_manifest_form input[name="selected_order_details"]').val(selectedValues);
    });
}


// Document ready initialization
$(document).ready(function () {
    // Initialize handlers for existing content
    initializeOrderDetailsHandlers();

    // Handle manifest form vendor dropdown change (if not using inline onchange)
    $('select[name="id_vendor"]').on('change', function () {
        loadVendorOrderDetails($(this).val());
        loadVendorAddress($(this).val());
    });

    // Handle manifest view page actions
    if (typeof manifestViewHandlers !== 'undefined') {
        manifestViewHandlers();
    }
});

// Manifest view page specific handlers
function manifestViewHandlers() {
    // Handle remove order detail from manifest
    $('.remove-order-detail').on('click', function (e) {
        e.preventDefault();

        var idManifest = $(this).data('id-manifest');
        var idOrderDetail = $(this).data('id-order-detail');
        var row = $(this).closest('tr');

        if (confirm('Are you sure you want to remove this item from the manifest?')) {
            $.ajax({
                url: manifestAjaxUrl,
                type: 'POST',
                data: {
                    ajax: true,
                    action: 'removeOrderDetail',
                    id_manifest: idManifest,
                    id_order_detail: idOrderDetail,
                    token: manifestToken
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        row.fadeOut(function () {
                            $(this).remove();
                            updateManifestCount();
                        });
                        showSuccessMessage(response.message || 'Item removed successfully');
                    } else {
                        showErrorMessage(response.message || 'Error removing item');
                    }
                },
                error: function () {
                    showErrorMessage('Connection error. Please try again.');
                }
            });
        }
    });

    // Handle print manifest
    $('#print-manifest').on('click', function () {
        window.print();
    });
}

// Update manifest items count
function updateManifestCount() {
    var count = $('#manifest-details-table tbody tr').length;
    $('.manifest-count').text(count);

    if (count === 0) {
        $('#manifest-details-table tbody').html(
            '<tr><td colspan="6" class="text-center text-muted">No items in this manifest</td></tr>'
        );
    }
}

// Utility functions for messages
function showSuccessMessage(message) {
    if (typeof showSuccessNotification !== 'undefined') {
        showSuccessNotification(message);
    } else {
        alert(message);
    }
}

function showErrorMessage(message) {
    if (typeof showErrorNotification !== 'undefined') {
        showErrorNotification(message);
    } else {
        alert(message);
    }
}

// Make key functions globally available
window.loadVendorOrderDetails = loadVendorOrderDetails;
window.initializeOrderDetailsHandlers = initializeOrderDetailsHandlers;