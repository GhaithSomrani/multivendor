/**
 * Manifest Admin JavaScript
 * Handles vendor selection and order details loading for manifest management
 */

// Global function for vendor order details loading
function loadVendorOrderDetailsBody() {
    var vendorId = $('select[name="id_vendor"]').val();
    var manifestTypeId = $('select[name="id_manifest_type"]').val();
    var manifestStatusId = $('select[name="id_manifest_status"]').val();
    var addressId = $('select[name="id_address"]').val();

    // Check if all required fields are selected
    if (!vendorId || vendorId == '0') {
        $('#order-details-tbody').html(
            '<tr><td colspan="13" class="text-center text-muted">Please select a vendor to view order details.</td></tr>'
        );
        return;
    }

    if (!manifestTypeId || manifestTypeId == '0') {
        $('#order-details-tbody').html(
            '<tr><td colspan="13" class="text-center text-muted">Please select a manifest type to view order details.</td></tr>'
        );
        return;
    }

    if (!manifestStatusId || manifestStatusId == '0') {
        $('#order-details-tbody').html(
            '<tr><td colspan="13" class="text-center text-muted">Please select a manifest status to view order details.</td></tr>'
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

    var id_manifest = $('input[name="id_manifest"]').val() ? $('input[name="id_manifest"]').val() : null;

    $.ajax({
        url: manifestAjaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'LoadVendorOrderDetailsBody',
            vendor_id: vendorId,
            id_manifest_type: manifestTypeId,
            id_manifest_status: manifestStatusId,
            id_address: addressId,
            id_manifest: id_manifest,
            filters: filters,
            token: manifestToken
        },
        dataType: 'json',
        success: function (response) {
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
                '<tr><td colspan="13" class="text-center text-danger">Error loading order details.</td></tr>'
            );
        }
    });
}

function loadManifestStatuses(manifestTypeId) {
    if (!manifestTypeId || manifestTypeId == '0') {
        var $statusSelect = $('select[name="id_manifest_status"]');
        $statusSelect.empty().append('<option value="0">Select manifest type first</option>');
        return;
    }

    $.ajax({
        url: manifestAjaxUrl,
        type: 'POST',
        data: {
            ajax: true,
            action: 'getManifestStatusesByType',
            id_manifest_type: manifestTypeId,
            token: manifestToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                var $statusSelect = $('select[name="id_manifest_status"]');
                $statusSelect.empty();
                $.each(response.statuses, function (index, status) {
                    $statusSelect.append('<option value="' + status.id_manifest_status_type + '">' + status.name + '</option>');
                });
                // Trigger order details reload if vendor is selected
                var vendorId = $('select[name="id_vendor"]').val();
                if (vendorId && vendorId != '0') {
                    loadVendorOrderDetailsBody();
                }
            }
        }
    });
}

function loadVendorAddress(vendorId) {
    if (!vendorId || vendorId == '0') {
        var addressSelect = $('select[name="id_address"]');
        addressSelect.empty().append('<option value="0">Select vendor first</option>');
        return;
    }

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
                // Trigger order details reload after address is loaded
                loadVendorOrderDetailsBody();
            }
        }
    });
}

function updateItemCount(count) {
    $('#items-count').text(count + ' items');
}

function initializeOrderDetailsHandlers() {
    var selectedValues = [];
    $('.order-detail-checkbox:checked').each(function () {
        selectedValues.push($(this).val());
    });
    $('#mv_manifest_form input[name="selected_order_details"]').val(selectedValues.join(','));

    $('.order-detail-checkbox').off('change').on('change', function () {
        var selectedValues = $('.order-detail-checkbox:checked').map(function () {
            return $(this).val();
        }).get();
        $('#mv_manifest_form input[name="selected_order_details"]').val(selectedValues.join(','));
    });

    $('#select-all-order-details').off('change').on('change', function () {
        var isChecked = $(this).is(':checked');
        $('.order-detail-checkbox:not(:disabled)').prop('checked', isChecked).trigger('change');
    });
}

// Document ready initialization
$(document).ready(function () {
    initializeOrderDetailsHandlers();

    // Vendor selection handler
    $('select[name="id_vendor"]').on('change', function () {
        var vendorId = $(this).val();
        loadVendorAddress(vendorId);
        // Order details will be loaded after address is loaded
    });

    // Manifest type selection handler
    $('select[name="id_manifest_type"]').on('change', function () {
        var manifestTypeId = $(this).val();
        loadManifestStatuses(manifestTypeId);
        // Order details will be loaded after status is loaded
    });

    // Manifest status selection handler
    $('select[name="id_manifest_status"]').on('change', function () {
        loadVendorOrderDetailsBody();
    });

    // Address selection handler
    $('select[name="id_address"]').on('change', function () {
        loadVendorOrderDetailsBody();
    });

    // Filter handlers
    $(document).on('change keyup', '.filter-input', function () {
        loadVendorOrderDetailsBody();
    });
});

// Make key functions globally available
window.loadVendorOrderDetails = loadVendorOrderDetailsBody;
window.initializeOrderDetailsHandlers = initializeOrderDetailsHandlers;