// Global variables
var availableOrders = [];
var selectedOrders = [];
var manifests = [];
var currentManifestId = null;
var vendorAddresses = [];
var currentAction = null;
var currentManifestType = null;
var manifestPagination = {
    currentPage: 1,
    totalPages: 1,
    totalCount: 0,
    perPage: 10
};
var manifestFilters = {};

// Initialize when document is ready
$(document).ready(function () {
    initManifest();
    bindEvents();
});

function initManifest() {
    loadAvailableOrders();
    loadManifestList();
    loadVendorAddresses();
}

function bindEvents() {
    $('#selectAllBtn').on('click', selectAll);
    $('#cancelBtn').on('click', cancelSelection);
    $('#saveBtn').on('click', saveManifest);
    $('#justSaveBtn').on('click', justSaveManifest);
    $('#printBtn').on('click', printManifest);

    // Filter and pagination buttons
    $('#apply-manifest-filter').on('click', applyManifestFilter);
    $('#reset-manifest-filter').on('click', resetManifestFilter);

    // Enable Enter key to apply filters
    $('.manifest-filter').on('keypress', function (e) {
        if (e.which === 13 || e.keyCode === 13) {
            e.preventDefault();
            applyManifestFilter();
        }
    });

    // MPN barcode scanner
    $('.mv-form-control[placeholder*="Scannez"]').on('input keypress', function (e) {
        if (e.type === 'keypress' && e.which === 13) {
            e.preventDefault();
            processMpnScan($(this).val().trim());
        } else if (e.type === 'input' && $(this).val().length > 0) {
            setTimeout(() => {
                if ($(this).val().trim().length > 0) {
                    processMpnScan($(this).val().trim());
                }
            }, 1000);
        }
    });

    // Close modals when clicking outside
    $(window).on('click', function (event) {
        if (event.target.id === 'manifestModal') {
            closeModal();
        } else if (event.target.id === 'addressModal') {
            closeAddressModal();
        }
    });
}

function loadAvailableOrders() {
    $.ajax({
        url: window.manifestConfig.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ajax: true,
            action: 'getAvailableOrders',
            id_manifest: currentManifestId || 0
        },
        success: function (response) {
            if (response.error) {
                showError(response.error);
                return;
            }
            availableOrders = response.orders;
            if (currentManifestType != 2) {
                renderAvailableOrders();
            }
        },
        error: function () {
        }
    });
}

function loadManifestOrders(manifestId) {
    if (!manifestId) {
        selectedOrders = [];
        renderSelectedOrders();
        return;
    }

    $.ajax({
        url: window.manifestConfig.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ajax: true,
            action: 'getManifestOrders',
            id_manifest: manifestId
        },
        success: function (response) {
            if (response.error) {
                showError(response.error);
                return;
            }
            selectedOrders = response.orders;
            renderSelectedOrders(response.total);
        },
        error: function () {
        }
    });
}

function loadManifestList(page = 1) {
    var requestData = {
        ajax: true,
        action: 'getManifestList',
        page: page
    };

    // Add filters to request
    Object.assign(requestData, manifestFilters);

    $.ajax({
        url: window.manifestConfig.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: requestData,
        success: function (response) {
            if (response.error) {
                showError(response.error);
                return;
            }
            manifests = response.manifests;

            // Normalize pagination keys from snake_case to camelCase
            manifestPagination = {
                currentPage: parseInt(response.pagination.current_page) || 1,
                totalPages: parseInt(response.pagination.total_pages) || 1,
                totalCount: parseInt(response.pagination.total_count) || 0,
                perPage: parseInt(response.pagination.per_page) || 10
            };

            renderManifestTable();
            renderManifestPagination();
        },
        error: function () {
            showError('Erreur lors du chargement des manifestes');
        }
    });
}

function loadVendorAddresses() {
    // Simulate vendor addresses - replace with actual AJAX call
    vendorAddresses = [
        { id: 1, alias: 'Main Warehouse' },
        { id: 2, alias: 'Secondary Location' }
    ];
}

function renderAvailableOrders() {
    var container = $('#availableOrders');
    container.empty();

    if (availableOrders.length === 0) {
        container.html('<div class="no-orders">Aucune commande disponible</div>');
        return;
    }

    $.each(availableOrders, function (index, order) {
        if (!order.disabled) {
            var orderDiv = $('<div>')
                .addClass('order-item')
                .toggleClass('selected', order.checked)
                .attr('data-id', order.id).attr('data-mpn', order.mpn);

            // --- Left ID block
            var id = $('<span>');

            $('<strong>', {
                class: 'item-order-detail',
                text: `${order.id_order}`
            }).appendTo(id);

            $('<br>').appendTo(id);

            $('<small>', {
                class: 'item-order-id',
                text: `${order.id}`
            }).appendTo(id);

            id.appendTo(orderDiv);

            var productInfo = $('<span>', { class: 'item-name' });

            $('<span>', {
                text: order.name + ' (X' + order.quantity + ')'
            }).appendTo(productInfo);
            $('<br>').appendTo(productInfo);

            $('<p>', {
                class: 'mv-mobile-product-sku',
                text: order.status,
                style: `font-weight: bold; background :${order.status_color}; color: #fff`
            }).appendTo(productInfo);

            $('<p>', {
                class: 'mv-mobile-product-sku',
                text: `Marque: ${order.reference}`
            }).appendTo(productInfo);

            $('<p>', {
                class: 'mv-mobile-product-sku',
                text: `SKU: ${order.reference}`
            }).appendTo(productInfo);

            $('<p>', {
                class: 'mv-mobile-product-sku',
                text: `MPN: ${order.mpn}`
            }).appendTo(productInfo);

            $('<p>', {
                class: 'mv-mobile-product-sku',
                text: `Prix Public: ${parseFloat(order.public_price).toFixed(2)}`
            }).appendTo(productInfo);

            productInfo.appendTo(orderDiv);

            // --- Vendor amount / price
            let vendorAmount = parseFloat(order.total).toFixed(2);
            $('<span>', {
                class: 'item-price',
                text: vendorAmount
            }).appendTo(orderDiv);

            // --- Click handler for selection
            orderDiv.on('click', function (e) {
                if (e.target.type !== 'checkbox' && !order.disabled) {
                    toggleOrderSelection(order.id);
                }
            });

            container.append(orderDiv);
        }
    });
}


function renderSelectedOrders(total) {
    var container = $('#selectedOrders');
    container.empty();

    if (selectedOrders.length === 0) {
        container.html('<div class="no-orders">No orders selected</div>');
        $('#totalAmount').text('0');
        return;
    }
    $.each(selectedOrders, function (index, order) {
        var orderDiv = $('<div>', { class: 'order-item', 'data-id': order.id, 'data-mpn': order.mpn });

        var id = $('<span>');

        $('<strong>', {
            class: 'item-order-detail',
            text: `${order.id_order}`
        }).appendTo(id);

        $('<br>').appendTo(id);

        $('<small>', {
            class: 'item-order-id',
            text: `${order.id}`
        }).appendTo(id);

        id.appendTo(orderDiv);

        var productInfo = $('<span>', { class: 'item-name' });

        $('<span>', {
            text: order.name + ' (X' + order.quantity + ')'
        }).appendTo(productInfo);
        $('<br>').appendTo(productInfo);
        $('<p>', {
            class: 'mv-mobile-product-sku',
            text: `Marque: ${order.reference}`
        }).appendTo(productInfo);

        $('<p>', {
            class: 'mv-mobile-product-sku',
            text: `SKU: ${order.reference}`
        }).appendTo(productInfo);

        $('<p>', {
            class: 'mv-mobile-product-sku',
            text: `MPN: ${order.mpn}`
        }).appendTo(productInfo);

        $('<p>', {
            class: 'mv-mobile-product-sku',
            text: `Prix Public: ${parseFloat(order.public_price).toFixed(2)}`
        }).appendTo(productInfo);

        productInfo.appendTo(orderDiv);

        $('<span>', {
            class: 'item-price',
            text: parseFloat(order.total).toFixed(2)
        }).appendTo(orderDiv);

        $('<button>', {
            class: 'action-btn delete-btn',
            text: '🗑️',
            click: () => removeSelectedItem(index)
        }).appendTo(orderDiv);

        container.append(orderDiv);
    });

    if (total !== null && total !== undefined) {
        $('#totalAmount').text(parseFloat(total).toFixed(2));
    } else {
        updateTotal();
    }
}

function populateStatusFilter() {
    var statusSelect = $('#filter-status');
    if (statusSelect.length === 0) return; // Skip if filter doesn't exist (mobile view)

    var existingValue = statusSelect.val();
    var uniqueStatuses = {};

    // Collect unique statuses from manifests
    $.each(manifests, function(index, manifest) {
        if (manifest.status && !uniqueStatuses[manifest.status]) {
            uniqueStatuses[manifest.status] = true;
        }
    });

    // Keep the "Tous" option and add unique statuses
    var currentOptions = statusSelect.find('option:first').clone();
    statusSelect.empty().append(currentOptions);

    // Add status options
    $.each(Object.keys(uniqueStatuses), function(index, status) {
        $('<option>', {
            value: status,
            text: status
        }).appendTo(statusSelect);
    });

    // Restore previous selection if it exists
    if (existingValue) {
        statusSelect.val(existingValue);
    }
}

function renderManifestTable() {
    var tbody = $('#manifestTableBody');
    tbody.empty();

    // Update manifest count - show total count from pagination
    var countText = manifestPagination.totalCount || manifests.length;
    $('#manifestCount').text(countText);

    // Populate status filter dropdown with unique statuses
    populateStatusFilter();

    if (manifests.length === 0) {
        tbody.html('<tr><td colspan="9"><div class="mv-empty-state">Aucun manifeste trouvé</div></td></tr>');
        return;
    }

    $.each(manifests, function (index, manifest) {
        // Main row
        var mainRow = $('<tr>').attr('onclick', 'toggleManifestRow(this)').css('cursor', 'pointer');

        // Reference
        $('<td>').html('<strong>' + manifest.reference + '</strong>').appendTo(mainRow);

        // Type with badge
        var typeBadge = $('<span>', {
            class: manifest.type == 1 ? 'mv-manifest-badge mv-manifest-pickup' : 'mv-manifest-badge mv-manifest-return',
            text: manifest.type_name
        });
        $('<td>').append(typeBadge).appendTo(mainRow);

        // Date
        $('<td>', { text: manifest.date }).appendTo(mainRow);

        // Address (truncated)
        $('<td>', {
            text: manifest.address,
            title: manifest.address
        }).appendTo(mainRow);

        // Number of items
        $('<td>', {
            text: manifest.nbre,
            class: 'mv-text-center'
        }).appendTo(mainRow);

        // Total quantity
        $('<td>', {
            text: manifest.qty,
            class: 'mv-text-center'
        }).appendTo(mainRow);

        // Total amount
        $('<td>').html('<strong>' + parseFloat(manifest.total).toFixed(3) + ' TND</strong>').appendTo(mainRow);

        // Status
        var statusBadge = $('<span>', {
            class: 'mv-status-badge mv-status-completed',
            text: manifest.status
        });
        $('<td>').append(statusBadge).appendTo(mainRow);

        // Actions
        var actionsCell = $('<td>');
        var actionsDiv = $('<div>', {
            style: 'display: flex; gap: 0.5rem; justify-content: end; align-items: center;'
        });

        // Edit button
        if (manifest.editable == 1) {
            $('<button>', {
                class: 'mv-status-btn mv-btn-edit',
                title: 'Modifier le manifeste',
                text: '✏️',
                click: function(e) {
                    e.stopPropagation();
                    loadManifest(manifest.id, manifest.type);
                }
            }).appendTo(actionsDiv);
        }

        // Print button
        $('<button>', {
            class: 'mv-status-btn mv-btn-print',
            title: 'Imprimer le manifeste',
            text: '🖨️',
            click: function(e) {
                e.stopPropagation();
                printExistingManifest(manifest.id);
            }
        }).appendTo(actionsDiv);

        // Delete button
        if (manifest.deletable == 1) {
            $('<button>', {
                class: 'mv-status-btn mv-btn-delete',
                title: 'Supprimer',
                text: '🗑️',
                click: function(e) {
                    e.stopPropagation();
                    deleteManifest(manifest.id);
                }
            }).appendTo(actionsDiv);
        }

        // Collapse button
        $('<button>', {
            class: 'mv-collapse-btn',
            text: '+',
            click: function(e) {
                e.stopPropagation();
                toggleManifestCollapse(this);
            }
        }).appendTo(actionsDiv);

        actionsDiv.appendTo(actionsCell);
        actionsCell.appendTo(mainRow);
        mainRow.appendTo(tbody);

        // Details row (collapsible)
        var detailsRow = $('<tr>', { class: 'mv-row-details' });
        var detailsCell = $('<td>', { colspan: 9 });

        if (manifest.orderdetails && manifest.orderdetails.length > 0) {
            var detailsTable = $('<table>', { class: 'mv-table mv-manifest-details-table' });

            // Table header
            var detailsThead = $('<thead>');
            var detailsHeaderRow = $('<tr>');
            ['Commande', 'Produit', 'SKU', 'Qté', 'Montant', 'Date'].forEach(function (header) {
                $('<th>', { text: header }).appendTo(detailsHeaderRow);
            });
            detailsHeaderRow.appendTo(detailsThead);
            detailsThead.appendTo(detailsTable);

            // Table body
            var detailsTbody = $('<tbody>');
            $.each(manifest.orderdetails, function (i, order) {
                var orderRow = $('<tr>');

                $('<td>').html('<a href="#" class="mv-link"><strong>#' + order.id_order + '</strong><br><small>' + order.id_order_detail + '</small></a>').appendTo(orderRow);
                $('<td>', { text: order.product_name ? order.product_name.substring(0, 40) + (order.product_name.length > 40 ? '...' : '') : '-' }).appendTo(orderRow);
                $('<td>', { text: order.product_reference || '-' }).appendTo(orderRow);
                $('<td>', { class: 'mv-text-center', text: order.product_quantity || '-' }).appendTo(orderRow);
                $('<td>', { text: parseFloat(order.vendor_amount).toFixed(3) + ' TND' }).appendTo(orderRow);
                $('<td>', { text: order.order_date ? new Date(order.order_date).toLocaleDateString('fr-FR') : '-' }).appendTo(orderRow);

                orderRow.appendTo(detailsTbody);
            });
            detailsTbody.appendTo(detailsTable);

            // Table footer
            var detailsTfoot = $('<tfoot>');
            var totalRow = $('<tr>');
            $('<td>', { colspan: 4, class: 'mv-text-right', html: '<strong>Total :</strong>' }).appendTo(totalRow);
            $('<td>', { html: '<strong>' + parseFloat(manifest.total).toFixed(3) + ' TND</strong>' }).appendTo(totalRow);
            $('<td>').appendTo(totalRow);
            totalRow.appendTo(detailsTfoot);
            detailsTfoot.appendTo(detailsTable);

            detailsTable.appendTo(detailsCell);
        } else {
            $('<p>', {
                class: 'mv-empty-state',
                text: 'Aucun détail de commande disponible'
            }).appendTo(detailsCell);
        }

        detailsCell.appendTo(detailsRow);
        detailsRow.appendTo(tbody);
    });
}

// Toggle collapse button for manifest details
function toggleManifestCollapse(button) {
    var row = $(button).closest('tr');
    var detailsRow = row.next('.mv-row-details');

    detailsRow.toggleClass('open');
    button.textContent = detailsRow.hasClass('open') ? '-' : '+';
}

// Toggle row on click (for entire row)
function toggleManifestRow(row) {
    var detailsRow = $(row).next('.mv-row-details');
    var button = $(row).find('.mv-collapse-btn');

    detailsRow.toggleClass('open');
    button.text(detailsRow.hasClass('open') ? '-' : '+');
}

function toggleOrderSelection(orderId) {
    var order = availableOrders.find(function (o) { return o.id === orderId; });
    if (!order || order.disabled) return;

    var existingIndex = selectedOrders.findIndex(function (o) { return o.id === orderId; });

    if (existingIndex === -1) {
        var newOrder = $.extend({}, order);
        selectedOrders.push(newOrder);
    }

    order.checked = true;
    if (currentManifestType != 2) {
        renderAvailableOrders();
    }
    renderSelectedOrders();
}

function removeSelectedItem(index) {
    var removedItem = selectedOrders[index];
    selectedOrders.splice(index, 1);

    var availableOrder = availableOrders.find(function (o) { return o.id === removedItem.id; });
    if (availableOrder && !selectedOrders.find(function (s) { return s.id === removedItem.id; })) {
        availableOrder.checked = false;
    }

    if (currentManifestType != 2) {
        renderAvailableOrders();
    }
    renderSelectedOrders();
}

function selectAll() {
    $.each(availableOrders, function (index, order) {
        if (!order.disabled && !order.checked) {
            order.checked = true;
            var newOrder = $.extend({}, order);
            newOrder.quantity = 1;
            selectedOrders.push(newOrder);
        }
    });
    if (currentManifestType != 2) {
        renderAvailableOrders();
    }
    renderSelectedOrders();
}

function cancelSelection() {
    selectedOrders = [];
    currentManifestId = null;
    currentManifestType = null;
    $.each(availableOrders, function (index, order) {
        order.checked = false;
    });
    loadAvailableOrders();
    renderSelectedOrders();
}

function updateTotal() {
    var total = selectedOrders.reduce(function (sum, order) {
        return parseFloat(sum) + parseFloat(order.total);
    }, 0);
    var totalQty = selectedOrders.reduce(function (sum, order) {

        return parseInt(sum) + parseInt(order.quantity);
    }, 0);
    $('#totalAmount').text(total.toFixed(2));
    $('#totalQty').text(totalQty);
}
function saveManifest() {
    if (selectedOrders.length === 0) {

        showNotification('info', window.manifestConfig.translations.noItemsSelected);
        return;
    }


    var addressId = $('#addressSelect').val();
    if (!addressId) {
        showNotification('info', window.manifestConfig.translations.selectAddress);
        return;
    }

    var orderDetailIds = selectedOrders.map(function (order) {
        return order.id;
    });

    executeManifestSave(addressId, orderDetailIds, true);
}

function printManifest() {
    if (selectedOrders.length === 0) {
        showNotification('info', window.manifestConfig.translations.noItemsSelected);
        return;
    }

    var addressId = $('#addressSelect').val();
    if (!addressId) {
        alert(window.manifestConfig.translations.selectAddress);
        return;
    }

    var orderDetailIds = selectedOrders.map(function (order) {
        return order.id;
    });

    executeManifestPrint(addressId, orderDetailIds);
}

function loadVendorAddresses() {
    var select = $('#addressSelect');
    var currentAddressId = select.data('current-address-id');

    var matchFound = false;
    select.find('option').each(function () {
        if ($(this).val() == currentAddressId) {
            $(this).prop('selected', true);
            matchFound = true;
        }
    });

    if (!matchFound && select.find('option').length > 0) {
        select.find('option:first').prop('selected', true);
    }
}


function executeManifestSave(addressId, orderDetailIds, validate = false) {
    var ajaxData = {
        ajax: true,
        action: currentManifestId ? 'updateManifest' : 'createManifest',
        order_details: orderDetailIds,
        validate: validate
    };

    if (currentManifestId) {
        ajaxData.id_manifest = currentManifestId;
        ajaxData.id_manifest_type = currentManifestType;
    } else {
        ajaxData.id_address = addressId;
    }

    $.ajax({
        url: window.manifestConfig.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: ajaxData,
        success: function (response) {
            if (response.error) {
                showError(response.error);
                return;
            }
            if (response.success) {
                showNotification('success', window.manifestConfig.translations.manifestSaved);
                cancelSelection();
                loadManifestList();
                location.reload();
            }
        },
        error: function () {
        }
    });
}

function executeManifestPrint(addressId, orderDetailIds) {
    if (currentManifestId) {
        // Print existing manifest
        printExistingManifest(currentManifestId);
    } else {
        // Create and print new manifest
        createAndPrintManifest(addressId, orderDetailIds);
    }
}
function printExistingManifest(manifestId) {
    window.open(
        window.manifestConfig.ajaxUrl + '&ajax=1&action=printManifest&id_manifest=' + manifestId,
        '_blank'
    );
}

function createAndPrintManifest(addressId, orderDetailIds) {
    $.ajax({
        url: window.manifestConfig.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ajax: true,
            action: 'createAndPrintManifest',
            id_address: addressId,
            order_details: orderDetailIds
        },
        success: function (response) {
            if (response.error) {
                showError(response.error);
                return;
            }
            if (response.manifest_id) {
                printExistingManifest(response.manifest_id);
                cancelSelection();
                loadManifestList();
            }
            if (response.success) {
                location.reload();

            }
        },
        error: function () {
        }
    });
}

function loadManifest(manifestId, manifestsTypes) {
    currentManifestType = manifestsTypes
    if (manifestsTypes == 2) {
        $('#saveBtn').text('✅ Valider le retour');
    }
    currentManifestId = manifestId;
    if (currentManifestType == 2) {
        var availableOrders = $('#availableOrders');
        availableOrders.empty();
        var add = $('<div>', {
            class: 'manifestrefund',
        })
        $('<Strong>', {
            text: 'Impossible d\'ajouter des articles au bon de retour'
        }).appendTo(add);
        add.appendTo(availableOrders);

    } else {
        loadAvailableOrders();

    }

    loadManifestOrders(manifestId);

    showNotification('success', 'Manifest #' + manifestId + ' loaded successfully');

}

function updateValidationButtonText() {
    var buttonText = 'Valider';

    if (currentManifestType == 1) { // Pickup
        buttonText = window.pickup_validation_name || 'Valider';
    } else if (currentManifestType == 2) { // Returns
        buttonText = window.returns_validation_name || 'Valider';
    }

    $('#saveBtn').text(buttonText);
}

function deleteManifest(manifestId) {
    if (!confirm(window.manifestConfig.translations.confirmDelete)) {
        return;
    }

    $.ajax({
        url: window.manifestConfig.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ajax: true,
            action: 'deleteManifest',
            id_manifest: manifestId
        },
        success: function (response) {
            if (response.error) {
                showError(response.error);
                return;
            }
            showNotification('success', window.manifestConfig.translations.manifestDeleted);
            loadManifestList();
            if (response.success) {
                location.reload();

            }
        },
        error: function () {
        }
    });
}

function viewManifest(manifestId) {
    var manifest = manifests.find(function (m) { return m.id == manifestId; });
    if (!manifest) return;

    $('#modalTitle').text('Manifest ' + manifest.reference + ' Details');

    var modalBody = $('#modalBody');
    modalBody.html(
        '<div class="manifest-details">' +
        '<p><strong>Reference:</strong> ' + manifest.reference + '</p>' +
        '<p><strong>Address:</strong> ' + manifest.address + '</p>' +
        '<p><strong>Date:</strong> ' + manifest.date + '</p>' +
        '<p><strong>Items:</strong> ' + manifest.nbre + '</p>' +
        '<p><strong>Total Quantity:</strong> ' + manifest.qty + '</p>' +
        '<p><strong>Total Amount:</strong> ' + manifest.total + '</p>' +
        '<p><strong>Status:</strong> ' + manifest.status + '</p>' +
        '</div>'
    );

    $('#manifestModal').show();
}


function processMpnScan(mpnValue) {
    if (!mpnValue) return;

    // Find order by MPN in available orders
    var foundOrder = availableOrders.find(order =>
        order.mpn && order.mpn.toLowerCase() == mpnValue.toLowerCase() && !order.checked && !order.disabled
    );

    if (foundOrder && !foundOrder.checked) {
        toggleOrderSelection(foundOrder.id);

        var orderElement = $('.order-item[data-mpn="' + mpnValue + '"]');
        orderElement.css('background-color', '#d4edda');
        setTimeout(function () {
            orderElement.css('background-color', '');
        }, 3000);

        // Clear the input field after successful scan
        $('.mv-form-control[placeholder*="Scannez"]').val('');

    }
}

function justSaveManifest() {
    if (selectedOrders.length === 0) {
        showNotification('info', window.manifestConfig.translations.noItemsSelected);
        return;
    }

    var addressId = $('#addressSelect').val();
    if (!addressId) {
        showNotification('info', window.manifestConfig.translations.selectAddress);
        return;
    }

    var orderDetailIds = selectedOrders.map(function (order) {
        return order.id;
    });

    executeJustSave(addressId, orderDetailIds);
}


function executeJustSave(addressId, orderDetailIds) {
    var ajaxData = {
        ajax: true,
        action: 'saveManifestOnly',
        id_address: addressId,
        order_details: orderDetailIds
    };

    if (currentManifestId) {
        ajaxData.id_manifest = currentManifestId;
        ajaxData.id_manifest_type = currentManifestType;
    }

    $.ajax({
        url: window.manifestConfig.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: ajaxData,
        success: function (response) {
            if (response.success) {
                showNotification('success', 'Manifeste enregistré avec succès');
                loadManifestList();
                location.reload();
            } else {
                showError(response.error);
                return;
            }

        },
        error: function (response) {
        }
    });
}
function showError(message) {
    showNotification('error', message);
}

/**
 * Show notification message
 * @param {string} type - The notification type (success, error, info, warning)
 * @param {string} message - The notification message
 */
function showNotification(type, message) {
    $('.temp-notification').remove();

    const alertClass = type === 'success' ? 'mv-alert-success' :
        type === 'info' ? 'mv-alert-info' :
            type === 'warning' ? 'mv-alert-warning' : 'mv-alert-danger';

    const $notification = $(`
    <div class="mv-alert ${alertClass} temp-notification" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;">
      ${message}
    </div>
  `);

    $('body').append($notification);

    const removeDelay = (type === 'error' || type === 'warning') ? 5000 : 3000;
    setTimeout(() => {
        $notification.fadeOut(() => $notification.remove());
    }, removeDelay);
}

// Filter handling functions
function applyManifestFilter() {
    manifestFilters = {
        reference: $('#filter-reference').val(),
        type: $('#filter-type').val(),
        date: $('#filter-date').val(),
        address: $('#filter-address').val(),
        items_min: $('#filter-items-min').val(),
        items_max: $('#filter-items-max').val(),
        qty_min: $('#filter-qty-min').val(),
        qty_max: $('#filter-qty-max').val(),
        total_min: $('#filter-total-min').val(),
        total_max: $('#filter-total-max').val(),
        status: $('#filter-status').val()
    };

    // Remove empty filters
    Object.keys(manifestFilters).forEach(key => {
        if (manifestFilters[key] === '' || manifestFilters[key] === null) {
            delete manifestFilters[key];
        }
    });

    // Reset to page 1 when applying filters
    loadManifestList(1);
}

function resetManifestFilter() {
    // Clear all filter inputs
    $('.manifest-filter').val('');
    manifestFilters = {};

    // Reload manifest list
    loadManifestList(1);
}

// Pagination handling functions
function renderManifestPagination() {
    var paginationContainer = $('#manifestPagination');
    var paginationList = $('#manifestPaginationList');

    if (manifestPagination.totalPages <= 1) {
        paginationContainer.hide();
        return;
    }

    paginationContainer.show();
    paginationList.empty();

    var currentPage = manifestPagination.currentPage;
    var totalPages = manifestPagination.totalPages;

    // First page button
    if (currentPage > 1) {
        $('<li>', { class: 'mv-pagination-item' })
            .append(
                $('<a>', {
                    class: 'mv-pagination-link',
                    text: '«',
                    href: '#',
                    click: function(e) {
                        e.preventDefault();
                        loadManifestList(1);
                    }
                })
            )
            .appendTo(paginationList);

        // Previous page button
        $('<li>', { class: 'mv-pagination-item' })
            .append(
                $('<a>', {
                    class: 'mv-pagination-link',
                    text: '‹',
                    href: '#',
                    click: function(e) {
                        e.preventDefault();
                        loadManifestList(currentPage - 1);
                    }
                })
            )
            .appendTo(paginationList);
    }

    // Page number buttons
    var startPage = Math.max(1, currentPage - 2);
    var endPage = Math.min(totalPages, currentPage + 2);

    for (var i = startPage; i <= endPage; i++) {
        (function(page) {
            var isActive = page === currentPage;
            $('<li>', {
                class: 'mv-pagination-item' + (isActive ? ' mv-pagination-active' : '')
            })
                .append(
                    $('<a>', {
                        class: 'mv-pagination-link',
                        text: page,
                        href: '#',
                        click: function(e) {
                            e.preventDefault();
                            if (!isActive) {
                                loadManifestList(page);
                            }
                        }
                    })
                )
                .appendTo(paginationList);
        })(i);
    }

    // Next page button
    if (currentPage < totalPages) {
        $('<li>', { class: 'mv-pagination-item' })
            .append(
                $('<a>', {
                    class: 'mv-pagination-link',
                    text: '›',
                    href: '#',
                    click: function(e) {
                        e.preventDefault();
                        loadManifestList(currentPage + 1);
                    }
                })
            )
            .appendTo(paginationList);

        // Last page button
        $('<li>', { class: 'mv-pagination-item' })
            .append(
                $('<a>', {
                    class: 'mv-pagination-link',
                    text: '»',
                    href: '#',
                    click: function(e) {
                        e.preventDefault();
                        loadManifestList(totalPages);
                    }
                })
            )
            .appendTo(paginationList);
    }
}
