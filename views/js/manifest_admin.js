/**
 * Manifest Admin JavaScript
 * Handles vendor selection and order details loading for manifest management
 */

// Global function for vendor order details loading
function loadVendorOrderDetailsBody(vendorId) {
    console.log('test')
    if (!vendorId || vendorId == '0') {
        $('#order-details-tbody').html(
            '<tr><td colspan="13" class="text-center text-muted">Please select a vendor to view order details.</td></tr>'
        );
        return;
    }

    $('#order-details-tbody').html(
        '<tr><td colspan="13" class="text-center"><i class="icon-spinner icon-spin"></i> Loading...</td></tr>'
    );

    var filters = {};
    $('.filter-input').each(function () {
        var val = $(this).val();
        if (val !== '') {
            filters[$(this).attr('name')] = val;
        }
    });

    // Get id_manifest from URL if not present in the form
    if (!manifestId || manifestId == 0) {
        var urlParams = new URLSearchParams(window.location.search);
        var manifestId = urlParams.get('id_manifest') || 0;
    }
    console.log(manifestId)
    $.ajax({
        url: manifestAjaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'LoadVendorOrderDetailsBody',
            vendor_id: vendorId,
            manifest_id: manifestId,
            filters: filters,
            token: manifestToken
        },
        dataType: 'json',
        success: function (response) {
            console.log($('#order-details-tbody'));
            if (response.success) {
                $('#order-details-tbody').html(response.html);
                initializeOrderDetailsHandlers();
                updateItemCount(response.count);
            } else {
                $('#order-details-tbody').html(
                    '<tr><td colspan="13" class="text-center text-danger">' +
                    (response.message || 'Error loading order details.') +
                    '</td></tr>'
                );
            }
        },
        error: function () {
            $('#order-details-tbody').html(
                '<tr><td colspan="13" class="text-center text-danger">Error loading order details. Please try again.</td></tr>'
            );
        }
    });
}
function updateItemCount(count) {
    $('#items-count').text(count + ' items');
}

function loadVendorOrderDetails(vendorId) {
    loadVendorOrderDetailsBody(vendorId);
}

function loadVendorAddress(vendorId) {
    // Get current selected address if in edit mode
    var currentAddressId = $('select[name="id_address"]').val() || 0;

    $.ajax({
        url: manifestAjaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'LoadVendorAddress',
            vendor_id: vendorId,
            current_address_id: currentAddressId,
            token: manifestToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                var addressSelect = $('select[name="id_address"]');
                addressSelect.empty();

                if (response.addresses && response.addresses.length > 0) {
                    $.each(response.addresses, function (index, addr) {
                        var option = $('<option></option>')
                            .attr('value', addr.id_address)
                            .html(addr.address_display);

                        // Select if this was the previously selected address or marked as selected
                        if (addr.selected || addr.id_address == response.current_address_id) {
                            option.prop('selected', true);
                        }

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
                alert(response.message || 'Error loading vendor address.');
            }
        },
        error: function () {
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
    initializeOrderDetailsHandlers();

    $('select[name="id_vendor"]').on('change', function () {
        var vendorId = $(this).val();
        loadVendorOrderDetailsBody(vendorId);
        loadVendorAddress(vendorId);
    });

    $(document).on('change keyup', '.filter-input', function () {
        var vendorId = $('select[name="id_vendor"]').val();
        if (vendorId) {
            loadVendorOrderDetailsBody(vendorId);
        }
    });
});


// Manifest view page specific handlers
function manifestViewHandlers() {

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