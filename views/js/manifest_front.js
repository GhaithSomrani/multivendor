// Global variables
var availableOrders = [];
var selectedOrders = [];
var manifests = [];
var currentManifestId = null;
var vendorAddresses = [];
var currentAction = null;
var currentManifestType = null;

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

function loadManifestList() {
    $.ajax({
        url: window.manifestConfig.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
            ajax: true,
            action: 'getManifestList'
        },
        success: function (response) {
            if (response.error) {
                showError(response.error);
                return;
            }
            manifests = response.manifests;
            renderManifestTable();
        },
        error: function () {
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

function renderManifestTable() {
    var container = $('#manifestTableBody').parent().parent();
    container.empty();

    if (manifests.length === 0) {
        container.html('<div class="mv-empty-state">No manifests found</div>');
        return;
    }

    var manifestList = $('<div>', { class: 'mv-payments-list' });

    $.each(manifests, function (index, manifest) {
        var manifestItem = $('<div>', { class: 'mv-payment-item' });

        // Header
        var header = $('<div>', { class: 'mv-payment-header' });

        var info = $('<div>', { class: 'mv-payment-info' });

        $('<span>', {
            class: 'mv-payment-date',
            text: manifest.date
        }).appendTo(info);

        $('<span>', {
            class: 'mv-payment-amount',
            text: parseFloat(manifest.total).toFixed(2) + ' TND'
        }).appendTo(info);

        $('<span>', {
            class: 'mv-payment-method',
            text: manifest.reference
        }).appendTo(info);

        $('<span>', {
            class: manifest.type == 1 ? 'mv-manifest-rm' : 'mv-manifest-rt',
            text: manifest.type_name
        }).appendTo(info);

        $('<span>', {
            class: 'mv-payment-reference',
            text: 'Articles: ' + manifest.nbre
        }).appendTo(info);

        $('<span>', {
            class: 'mv-payment-reference',
            text: 'QTÉ: ' + manifest.qty
        }).appendTo(info);

        $('<span>', {
            class: 'mv-status-badge mv-status-completed',
            text: manifest.status
        }).appendTo(info);

        // Actions in header
        var actionButtons = $('<div>', { style: 'display: flex; gap: 8px; margin-left: auto;' });
        if (manifest.editable == 1) {
            $('<button>', {
                class: 'action-btn load-btn',
                title: 'Load Manifest',
                text: '✏️',
                click: () => loadManifest(manifest.id, manifest.type),
            }).appendTo(actionButtons);
        }



        $('<button>', {
            class: 'action-btn print-btn',
            title: 'Print Manifest',
            text: '🖨️',
            click: () => printExistingManifest(manifest.id)
        }).appendTo(actionButtons);

        if (manifest.deletable == 1) {
            $('<button>', {
                class: 'action-btn delete-btn',
                title: 'Delete',
                text: '🗑️',
                click: () => deleteManifest(manifest.id)
            }).appendTo(actionButtons);
        }

        info.appendTo(header);
        actionButtons.appendTo(header);

        var toggle = $('<button>', {
            class: 'mv-btn-toggle',
            click: () => toggleManifestDetails('manifest-' + manifest.id)
        });

        $('<i>', {
            class: 'mv-icon-chevron',
            text: '▼'
        }).appendTo(toggle);

        toggle.appendTo(header);
        header.appendTo(manifestItem);

        // Details - Order Details Table
        var details = $('<div>', {
            class: 'mv-payment-details',
            id: 'manifest-' + manifest.id,
            css: { display: 'none' }
        });

        if (manifest.orderdetails && manifest.orderdetails.length > 0) {
            var table = $('<table>', { class: 'mv-table mv-payment-details-table' });

            var thead = $('<thead>');
            var headerRow = $('<tr>');

            ['Commande', 'Produit', 'SKU', 'Qté', 'Montant', 'Date'].forEach(function (header) {
                $('<th>', { text: header }).appendTo(headerRow);
            });

            headerRow.appendTo(thead);
            thead.appendTo(table);

            var tbody = $('<tbody>');

            $.each(manifest.orderdetails, function (i, order) {
                var row = $('<tr>');

                $('<td>').html('<a href="#" class="mv-link">#' + order.id_order + '</a>').appendTo(row);
                $('<td>', { text: order.product_name }).appendTo(row);
                $('<td>', { text: order.product_reference }).appendTo(row);
                $('<td>', { class: 'mv-text-center', text: order.product_quantity }).appendTo(row);
                $('<td>', { text: parseFloat(order.vendor_amount).toFixed(3) + ' TND' }).appendTo(row);
                $('<td>', { text: order.order_date ? new Date(order.order_date).toLocaleDateString() : '' }).appendTo(row);

                row.appendTo(tbody);
            });

            tbody.appendTo(table);

            var tfoot = $('<tfoot>');
            var totalRow = $('<tr>');
            $('<td>', { colspan: '4', class: 'mv-text-right', html: '<strong>Total :</strong>' }).appendTo(totalRow);
            $('<td>', { html: '<strong>' + parseFloat(manifest.total).toFixed(3) + ' TND</strong>' }).appendTo(totalRow);
            $('<td>').appendTo(totalRow);
            totalRow.appendTo(tfoot);
            tfoot.appendTo(table);

            table.appendTo(details);
        } else {
            $('<p>', { text: 'No order details available', style: 'text-align: center; color: #666; padding: 20px;' }).appendTo(details);
        }

        details.appendTo(manifestItem);
        manifestItem.appendTo(manifestList);
    });

    manifestList.appendTo(container);
}

function toggleManifestDetails(detailsId) {
    var details = $('#' + detailsId);
    var toggle = details.prev().find('.mv-btn-toggle');
    var icon = toggle.find('.mv-icon-chevron');

    if (details.is(':visible')) {
        details.hide();
        toggle.removeClass('expanded');
        icon.text('▼');
    } else {
        details.show();
        toggle.addClass('expanded');
        icon.text('▲');
    }
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

    executeManifestSave(addressId, orderDetailIds);
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


function executeManifestSave(addressId, orderDetailIds) {
    var ajaxData = {
        ajax: true,
        action: currentManifestId ? 'updateManifest' : 'createManifest',
        order_details: orderDetailIds
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
