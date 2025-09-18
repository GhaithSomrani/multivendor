// Global variables
var availableOrders = [];
var selectedOrders = [];
var manifests = [];
var currentManifestId = null;
var vendorAddresses = [];
var currentAction = null;

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
    $('#printBtn').on('click', printManifest);
    $('#confirmAddressBtn').on('click', confirmAddress);

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
            renderAvailableOrders();
        },
        error: function () {
            showError('Failed to load available orders');
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
            showError('Failed to load manifest orders');
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
            showError('Failed to load manifest list');
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
        container.html('<div class="no-orders">No available orders</div>');
        return;
    }

    $.each(availableOrders, function (index, order) {
        if (!order.disabled) {
            var orderDiv = $('<div>')
                .addClass('order-item')
                .toggleClass('selected', order.checked)
                .attr('data-id', order.id);

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
                text: order.name
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
    console.log(selectedOrders);
    $.each(selectedOrders, function (index, order) {
        var orderDiv = $('<div>', { class: 'order-item' });

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
            text: order.name
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
            class: 'mv-payment-reference',
            text: manifest.address
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
                click: () => loadManifest(manifest.id),
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
    renderAvailableOrders();
    renderSelectedOrders();
}

function removeSelectedItem(index) {
    var removedItem = selectedOrders[index];
    selectedOrders.splice(index, 1);

    var availableOrder = availableOrders.find(function (o) { return o.id === removedItem.id; });
    if (availableOrder && !selectedOrders.find(function (s) { return s.id === removedItem.id; })) {
        availableOrder.checked = false;
    }

    renderAvailableOrders();
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
    renderAvailableOrders();
    renderSelectedOrders();
}

function cancelSelection() {
    selectedOrders = [];
    currentManifestId = null;
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

    $('#totalAmount').text(total.toFixed(2));
}

function saveManifest() {
    if (selectedOrders.length === 0) {
        alert(window.manifestConfig.translations.noItemsSelected);
        return;
    }
    showAddressModal('save');
}

function printManifest() {
    if (selectedOrders.length === 0) {
        alert(window.manifestConfig.translations.noItemsSelected);
        return;
    }
    showAddressModal('print');
}

function showAddressModal(action) {
    currentAction = action;

    var select = $('#addressSelect');

    $('#confirmAddressBtn').text(action === 'save' ? 'Save Manifest' : 'Print Manifest');
    $('#addressModal').show();
}

function confirmAddress() {
    var addressId = $('#addressSelect').val();
    if (!addressId) {
        alert(window.manifestConfig.translations.selectAddress);
        return;
    }

    var orderDetailIds = selectedOrders.map(function (order) {
        return order.id;
    });

    if (currentAction === 'save') {
        executeManifestSave(addressId, orderDetailIds);
    } else if (currentAction === 'print') {
        executeManifestPrint(addressId, orderDetailIds);
    }

    closeAddressModal();
}

function executeManifestSave(addressId, orderDetailIds) {
    var ajaxData = {
        ajax: true,
        action: currentManifestId ? 'updateManifest' : 'createManifest',
        order_details: orderDetailIds
    };

    if (currentManifestId) {
        ajaxData.id_manifest = currentManifestId;
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
            alert(window.manifestConfig.translations.manifestSaved);
            cancelSelection();
            loadManifestList();
        },
        error: function () {
            showError('Failed to save manifest');
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
        window.manifestConfig.ajaxUrl + '?ajax=1&action=printManifest&id_manifest=' + manifestId,
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
        },
        error: function () {
            showError('Failed to create and print manifest');
        }
    });
}

function loadManifest(manifestId) {
    currentManifestId = manifestId;
    loadAvailableOrders();
    loadManifestOrders(manifestId);
    alert('Manifest #' + manifestId + ' loaded successfully');
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
            alert(window.manifestConfig.translations.manifestDeleted);
            loadManifestList();
        },
        error: function () {
            showError('Failed to delete manifest');
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

function closeAddressModal() {
    $('#addressModal').hide();
    currentAction = null;
}

function closeModal() {
    $('#manifestModal').hide();
}

function showError(message) {
    console.error(message);
    alert(message);
}