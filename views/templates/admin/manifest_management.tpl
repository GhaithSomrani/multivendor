{*
* Manifest Management Template
* File: views/templates/admin/manifest_management.tpl
*}

<div class="manifest-management-container">
    <div class="row">
        {* Status Type Filter *}
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <h4>{l s='Select Order Status Type' mod='multivendor'}</h4>
                </div>
                <div class="card-body">
                    <select id="status-type-select" class="form-control">
                        <option value="">{l s='-- Select Status Type --' mod='multivendor'}</option>
                        {foreach $available_status_types as $status_type}
                            <option value="{$status_type.id_order_line_status_type}">
                                {$status_type.name}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row manifest-workspace" style="display: none;">
        {* Left Block - Available Order Lines *}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{l s='Available Order Lines' mod='multivendor'}</h4>
                    <button type="button" class="btn btn-sm btn-secondary" id="select-all-available">
                        {l s='Select All' mod='multivendor'}
                    </button>
                </div>
                <div class="card-body">
                    <div id="available-orders-list" class="order-list">
                        <div class="text-center p-4">
                            <p>{l s='Select a status type to load order lines' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <span id="available-total" class="badge badge-info">
                            {l s='Total: 0' mod='multivendor'}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {* Right Block - Selected for Printing *}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{l s='Selected for Printing' mod='multivendor'}</h4>
                    <div>
                        <button type="button" class="btn btn-sm btn-warning" id="clear-selected">
                            {l s='Clear All' mod='multivendor'}
                        </button>
                        <button type="button" class="btn btn-sm btn-success" id="save-manifest">
                            {l s='Save' mod='multivendor'}
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="print-manifest">
                            {l s='Print' mod='multivendor'}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="selected-orders-list" class="order-list">
                        <div class="empty-state text-center p-4">
                            <i class="material-icons" style="font-size: 48px; color: #ccc;">inbox</i>
                            <p>{l s='No items selected' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <span id="selected-total" class="badge badge-success">
                            {l s='Total: 0' mod='multivendor'}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {* Saved Manifests Table *}
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>{l s='Saved Manifests' mod='multivendor'}</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="saved-manifests-table">
                            <thead>
                                <tr>
                                    <th>{l s='Reference' mod='multivendor'}</th>
                                    <th>{l s='Status' mod='multivendor'}</th>
                                    <th>{l s='Date' mod='multivendor'}</th>
                                    <th>{l s='Items Count' mod='multivendor'}</th>
                                    <th>{l s='Total' mod='multivendor'}</th>
                                    <th>{l s='Action' mod='multivendor'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {if $saved_manifests}
                                    {foreach $saved_manifests as $manifest}
                                        <tr>
                                            <td>#{$manifest.reference}</td>
                                            <td>
                                                <span class="badge badge-{if $manifest.status == 'draft'}secondary{elseif $manifest.status == 'verified'}info{elseif $manifest.status == 'printed'}primary{else}success{/if}">
                                                    {$manifest.status|ucfirst}
                                                </span>
                                            </td>
                                            <td>{$manifest.date_add|date_format:"%d/%m/%Y %H:%M"}</td>
                                            <td>{$manifest.total_items}</td>
                                            <td>999</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary load-manifest" 
                                                        data-manifest-id="{$manifest.id_manifest}" 
                                                        title="{l s='Load Manifest' mod='multivendor'}">
                                                    <i class="material-icons">arrow_upward</i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info view-manifest" 
                                                        data-manifest-id="{$manifest.id_manifest}" 
                                                        title="{l s='View Details' mod='multivendor'}">
                                                    <i class="material-icons">visibility</i>
                                                </button>
                                            </td>
                                        </tr>
                                    {/foreach}
                                {else}
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            {l s='No saved manifests found' mod='multivendor'}
                                        </td>
                                    </tr>
                                {/if}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{* Address Selection Modal *}
<div class="modal fade" id="address-selection-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{l s='Select Shipping Address' mod='multivendor'}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="address-list">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> {l s='Loading addresses...' mod='multivendor'}
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <h6>{l s='Or Add New Address' mod='multivendor'}</h6>
                        <form id="new-address-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{l s='Company' mod='multivendor'}</label>
                                        <input type="text" name="company" class="form-control" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{l s='Contact Person' mod='multivendor'}</label>
                                        <input type="text" name="firstname" class="form-control" required />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{l s='Address' mod='multivendor'} *</label>
                                        <input type="text" name="address1" class="form-control" required />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{l s='City' mod='multivendor'} *</label>
                                        <input type="text" name="city" class="form-control" required />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{l s='Postal Code' mod='multivendor'} *</label>
                                        <input type="text" name="postcode" class="form-control" required />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{l s='Phone' mod='multivendor'}</label>
                                        <input type="text" name="phone" class="form-control" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{l s='Country' mod='multivendor'} *</label>
                                        <select name="id_country" class="form-control" required>
                                            <option value="">{l s='Select Country' mod='multivendor'}</option>
                                            {* Will be populated by JavaScript *}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    {l s='Cancel' mod='multivendor'}
                </button>
                <button type="button" class="btn btn-primary" id="confirm-address">
                    {l s='Confirm Address' mod='multivendor'}
                </button>
            </div>
        </div>
    </div>
</div>

{* Manifest Details Modal *}
<div class="modal fade" id="manifest-details-modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{l s='Manifest Details' mod='multivendor'}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="manifest-details-content">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> {l s='Loading manifest details...' mod='multivendor'}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.manifest-management-container {
    padding: 20px;
}

.order-list {
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.order-item {
    border-bottom: 1px solid #eee;
    padding: 10px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.order-item:hover {
    background-color: #f8f9fa;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item.selected {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.order-item-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 5px;
}

.order-item-details {
    font-size: 0.9em;
    color: #666;
}

.empty-state {
    color: #999;
}

.address-option {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.address-option:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.address-option.selected {
    border-color: #007bff;
    background-color: #e3f2fd;
}

.manifest-details-table {
    font-size: 0.9em;
}

.status-badge {
    font-size: 0.8em;
}
</style>

<script>
$(document).ready(function() {
    let selectedOrderDetails = [];
    let availableOrderDetails = [];
    let selectedAddress = null;
    let currentStatusType = null;
    
    // Status type selection
    $('#status-type-select').on('change', function() {
        const statusTypeId = $(this).val();
        currentStatusType = statusTypeId;
        
        if (statusTypeId) {
            loadAvailableOrderDetails(statusTypeId);
            $('.manifest-workspace').show();
        } else {
            $('.manifest-workspace').hide();
            clearWorkspace();
        }
    });

    // Load available order details
    function loadAvailableOrderDetails(statusTypeId) {
        $('#available-orders-list').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin"></i> {l s="Loading..." mod="multivendor"}</div>');
        
        $.ajax({
            url: '{$controller_url}',
            method: 'POST',
            data: {
                ajax: 1,
                action: 'getAvailableOrderDetails',
                id_status_type: statusTypeId
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    availableOrderDetails = data.data;
                    renderAvailableOrderDetails();
                } else {
                    showError(data.error);
                }
            },
            error: function() {
                showError('{l s="Error loading order details" mod="multivendor"}');
            }
        });
    }

    // Render available order details
    function renderAvailableOrderDetails() {
        const container = $('#available-orders-list');
        container.empty();
        
        if (availableOrderDetails.length === 0) {
            container.html('<div class="text-center p-4"><p>{l s="No order lines available" mod="multivendor"}</p></div>');
            return;
        }
        
        availableOrderDetails.forEach(function(item) {
            const orderItem = $(`
                <div class="order-item" data-order-detail-id="${item.id_order_detail}">
                    <div class="order-item-header">
                        <strong>${item.product_name}</strong>
                        <span class="badge badge-info">${item.product_quantity}</span>
                    </div>
                    <div class="order-item-details">
                        <div>Order: #${item.order_reference} | ${item.firstname} ${item.lastname}</div>
                        <div>Ref: ${item.product_reference || 'N/A'} | Date: ${formatDate(item.order_date)}</div>
                    </div>
                </div>
            `);
            container.append(orderItem);
        });
        
        updateAvailableCount();
    }

    // Handle order item selection
    $(document).on('click', '.order-item', function() {
        const orderDetailId = $(this).data('order-detail-id');
        const isInAvailable = $(this).closest('#available-orders-list').length > 0;
        
        if (isInAvailable) {
            // Move from available to selected
            if (isOrderAlreadySelected(orderDetailId)) {
                showError('{l s="Item already selected" mod="multivendor"}');
                return;
            }
            
            const orderDetail = availableOrderDetails.find(item => item.id_order_detail == orderDetailId);
            if (orderDetail) {
                selectedOrderDetails.push(orderDetail);
                renderSelectedOrderDetails();
                $(this).remove();
                updateCounts();
            }
        } else {
            // Remove from selected
            removeFromSelected(orderDetailId);
        }
    });

    // Check if order is already selected
    function isOrderAlreadySelected(orderDetailId) {
        return selectedOrderDetails.some(item => item.id_order_detail == orderDetailId);
    }

    // Render selected order details
    function renderSelectedOrderDetails() {
        const container = $('#selected-orders-list');
        container.empty();
        
        if (selectedOrderDetails.length === 0) {
            container.html(`
                <div class="empty-state text-center p-4">
                    <i class="material-icons" style="font-size: 48px; color: #ccc;">inbox</i>
                    <p>{l s="No items selected" mod="multivendor"}</p>
                </div>
            `);
            return;
        }
        
        selectedOrderDetails.forEach(function(item) {
            const orderItem = $(`
                <div class="order-item selected" data-order-detail-id="${item.id_order_detail}">
                    <div class="order-item-header">
                        <strong>${item.product_name}</strong>
                        <div>
                            <span class="badge badge-info">${item.product_quantity}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger ml-2 remove-item" data-order-detail-id="${item.id_order_detail}">
                                <i class="material-icons">close</i>
                            </button>
                        </div>
                    </div>
                    <div class="order-item-details">
                        <div>Order: #${item.order_reference} | ${item.firstname} ${item.lastname}</div>
                        <div>Ref: ${item.product_reference || 'N/A'} | Date: ${formatDate(item.order_date)}</div>
                    </div>
                </div>
            `);
            container.append(orderItem);
        });
    }

    // Remove from selected
    function removeFromSelected(orderDetailId) {
        selectedOrderDetails = selectedOrderDetails.filter(item => item.id_order_detail != orderDetailId);
        renderSelectedOrderDetails();
        updateCounts();
    }

    // Handle remove button
    $(document).on('click', '.remove-item', function(e) {
        e.stopPropagation();
        const orderDetailId = $(this).data('order-detail-id');
        removeFromSelected(orderDetailId);
    });

    // Select all available
    $('#select-all-available').on('click', function() {
        $('#available-orders-list .order-item').each(function() {
            const orderDetailId = $(this).data('order-detail-id');
            if (!isOrderAlreadySelected(orderDetailId)) {
                const orderDetail = availableOrderDetails.find(item => item.id_order_detail == orderDetailId);
                if (orderDetail) {
                    selectedOrderDetails.push(orderDetail);
                }
            }
        });
        
        $('#available-orders-list').empty().html('<div class="text-center p-4"><p>{l s="All items selected" mod="multivendor"}</p></div>');
        renderSelectedOrderDetails();
        updateCounts();
    });

    // Clear selected
    $('#clear-selected').on('click', function() {
        selectedOrderDetails = [];
        renderSelectedOrderDetails();
        if (currentStatusType) {
            loadAvailableOrderDetails(currentStatusType);
        }
        updateCounts();
    });

    // Update counts
    function updateCounts() {
        $('#available-total').text('{l s="Total:" mod="multivendor"} ' + $('#available-orders-list .order-item').length);
        $('#selected-total').text('{l s="Total:" mod="multivendor"} ' + selectedOrderDetails.length);
    }

    function updateAvailableCount() {
        $('#available-total').text('{l s="Total:" mod="multivendor"} ' + availableOrderDetails.length);
    }

    // Save manifest
    $('#save-manifest').on('click', function() {
        if (selectedOrderDetails.length === 0) {
            showError('{l s="Please select at least one item" mod="multivendor"}');
            return;
        }
        showAddressModal('save');
    });

    // Print manifest
    $('#print-manifest').on('click', function() {
        if (selectedOrderDetails.length === 0) {
            showError('{l s="Please select at least one item" mod="multivendor"}');
            return;
        }
        showAddressModal('print');
    });

    // Show address selection modal
    function showAddressModal(action) {
        selectedAddress = null;
        $('#address-selection-modal').data('action', action).modal('show');
        loadVendorAddresses();
    }

    // Load vendor addresses
    function loadVendorAddresses() {
        $('#address-list').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> {l s="Loading addresses..." mod="multivendor"}</div>');
        
        $.ajax({
            url: '{$controller_url}',
            method: 'POST',
            data: {
                ajax: 1,
                action: 'getVendorAddresses'
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    renderAddresses(data.addresses);
                } else {
                    showError(data.error);
                }
            }
        });
    }

    // Render addresses
    function renderAddresses(addresses) {
        const container = $('#address-list');
        container.empty();
        
        if (addresses.length === 0) {
            container.html('<div class="text-center p-3"><p>{l s="No saved addresses found" mod="multivendor"}</p></div>');
            return;
        }
        
        addresses.forEach(function(address) {
            const addressOption = $(`
                <div class="address-option" data-address-id="${address.id_address}">
                    <div><strong>${address.company || address.firstname + ' ' + address.lastname}</strong></div>
                    <div>${address.address1}</div>
                    <div>${address.city}, ${address.postcode} - ${address.country_name}</div>
                    ${address.phone ? '<div>Tel: ' + address.phone + '</div>' : ''}
                </div>
            `);
            container.append(addressOption);
        });
    }

    // Handle address selection
    $(document).on('click', '.address-option', function() {
        $('.address-option').removeClass('selected');
        $(this).addClass('selected');
        selectedAddress = $(this).data('address-id');
    });

    // Confirm address
    $('#confirm-address').on('click', function() {
        const action = $('#address-selection-modal').data('action');
        let addressData = null;
        
        // Check if existing address is selected
        if (selectedAddress) {
            const selectedAddressElement = $(`.address-option[data-address-id="${selectedAddress}"]`);
            addressData = {
                type: 'existing',
                id_address: selectedAddress,
                display: selectedAddressElement.text().trim()
            };
        } else {
            // Validate new address form
            const form = $('#new-address-form')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            addressData = {
                type: 'new',
                company: $('[name="company"]').val(),
                firstname: $('[name="firstname"]').val(),
                address1: $('[name="address1"]').val(),
                city: $('[name="city"]').val(),
                postcode: $('[name="postcode"]').val(),
                phone: $('[name="phone"]').val(),
                id_country: $('[name="id_country"]').val()
            };
        }
        
        $('#address-selection-modal').modal('hide');
        
        if (action === 'save') {
            saveManifest(addressData);
        } else {
            printManifest(addressData);
        }
    });

    // Save manifest
    function saveManifest(addressData) {
        const orderDetailIds = selectedOrderDetails.map(item => item.id_order_detail);
        
        $.ajax({
            url: '{$controller_url}',
            method: 'POST',
            data: {
                ajax: 1,
                action: 'saveManifest',
                order_detail_ids: JSON.stringify(orderDetailIds),
                address_data: JSON.stringify(addressData),
                id_status_type: currentStatusType
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    showSuccess(data.message);
                    clearWorkspace();
                    location.reload(); // Reload to update saved manifests
                } else {
                    showError(data.error);
                }
            },
            error: function() {
                showError('{l s="Error saving manifest" mod="multivendor"}');
            }
        });
    }

    // Print manifest
    function printManifest(addressData) {
        const orderDetailIds = selectedOrderDetails.map(item => item.id_order_detail);
        
        $.ajax({
            url: '{$controller_url}',
            method: 'POST',
            data: {
                ajax: 1,
                action: 'printManifest',
                order_detail_ids: JSON.stringify(orderDetailIds),
                address_data: JSON.stringify(addressData),
                id_status_type: currentStatusType
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    // Open PDF in new window
                    window.open(data.pdf_url, '_blank');
                    showSuccess('{l s="Manifest created and ready for printing" mod="multivendor"}');
                    clearWorkspace();
                    location.reload();
                } else {
                    showError(data.error);
                }
            },
            error: function() {
                showError('{l s="Error printing manifest" mod="multivendor"}');
            }
        });
    }

    // Load manifest
    $(document).on('click', '.load-manifest', function() {
        const manifestId = $(this).data('manifest-id');
        
        $.ajax({
            url: '{$controller_url}',
            method: 'POST',
            data: {
                ajax: 1,
                action: 'loadManifest',
                id_manifest: manifestId
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    // Set status type
                    $('#status-type-select').val(data.manifest.id_order_line_status_type).trigger('change');
                    
                    // Load order details into selected
                    selectedOrderDetails = data.order_details;
                    renderSelectedOrderDetails();
                    updateCounts();
                    
                    showSuccess('{l s="Manifest loaded successfully" mod="multivendor"}');
                } else {
                    showError(data.error);
                }
            }
        });
    });

    // View manifest details
    $(document).on('click', '.view-manifest', function() {
        const manifestId = $(this).data('manifest-id');
        
        $('#manifest-details-modal').modal('show');
        $('#manifest-details-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> {l s="Loading manifest details..." mod="multivendor"}</div>');
        
        $.ajax({
            url: '{$controller_url}',
            method: 'POST',
            data: {
                ajax: 1,
                action: 'getManifestDetails',
                id_manifest: manifestId
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    renderManifestDetails(data);
                } else {
                    showError(data.error);
                }
            }
        });
    });

    // Render manifest details
    function renderManifestDetails(data) {
        const manifest = data.manifest;
        const orderDetails = data.order_details;
        
        let html = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6>{l s="Manifest Information" mod="multivendor"}</h6>
                    <table class="table table-sm">
                        <tr><td><strong>{l s="Reference:" mod="multivendor"}</strong></td><td>#${manifest.reference}</td></tr>
                        <tr><td><strong>{l s="Status:" mod="multivendor"}</strong></td><td><span class="badge badge-info">${manifest.status}</span></td></tr>
                        <tr><td><strong>{l s="Total Items:" mod="multivendor"}</strong></td><td>${manifest.total_items}</td></tr>
                        <tr><td><strong>{l s="Date:" mod="multivendor"}</strong></td><td>${formatDate(manifest.date_add)}</td></tr>
                    </table>
                </div>
            </div>
        `;
        
        if (orderDetails.length > 0) {
            html += `
                <div class="table-responsive">
                    <table class="table table-striped manifest-details-table">
                        <thead>
                            <tr>
                                <th>{l s="Product" mod="multivendor"}</th>
                                <th>{l s="Order" mod="multivendor"}</th>
                                <th>{l s="Customer" mod="multivendor"}</th>
                                <th>{l s="Qty" mod="multivendor"}</th>
                                <th>{l s="Address" mod="multivendor"}</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            orderDetails.forEach(function(item) {
                html += `
                    <tr>
                        <td>
                            <div><strong>${item.product_name}</strong></div>
                            <small>Ref: ${item.product_reference || 'N/A'}</small>
                        </td>
                        <td>#${item.order_reference}</td>
                        <td>${item.firstname} ${item.lastname}</td>
                        <td>${item.product_quantity}</td>
                        <td>
                            <div>${item.address1}</div>
                            <small>${item.city}, ${item.postcode} - ${item.country_name}</small>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
        }
        
        $('#manifest-details-content').html(html);
    }

    // Utility functions
    function clearWorkspace() {
        selectedOrderDetails = [];
        availableOrderDetails = [];
        renderSelectedOrderDetails();
        $('#available-orders-list').empty();
        updateCounts();
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    function showError(message) {
        // Use PrestaShop's native notification system or simple alert
        if (typeof show_error !== 'undefined') {
            show_error(message);
        } else {
            alert('{l s="Error:" mod="multivendor"} ' + message);
        }
    }

    function showSuccess(message) {
        // Use PrestaShop's native notification system or simple alert
        if (typeof show_success !== 'undefined') {
            show_success(message);
        } else {
            alert('{l s="Success:" mod="multivendor"} ' + message);
        }
    }
});
</script>