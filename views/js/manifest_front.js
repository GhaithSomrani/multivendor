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
            class: 'btn btn-sm remove-btn',
            text: '×',
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
    var tbody = $('#manifestTableBody');
    tbody.empty();

    if (manifests.length === 0) {
        tbody.html('<tr><td colspan="8">No manifests found</td></tr>');
        return;
    }

    $.each(manifests, function (index, manifest) {
        var deleteButton = manifest.editable ?
            '<button class="action-btn delete-btn" onclick="deleteManifest(' + manifest.id + ')" title="Delete">×</button>' : '';

        var row = $('<tr>');

        $('<td>', { text: manifest.reference }).appendTo(row);
        $('<td>', { text: manifest.address }).appendTo(row);
        $('<td>', { text: manifest.date }).appendTo(row);
        $('<td>', { text: manifest.nbre }).appendTo(row);
        $('<td>', { text: manifest.qty }).appendTo(row);
        $('<td>', { text: parseFloat(manifest.total).toFixed(2) }).appendTo(row);
        $('<td>', { text: manifest.status }).appendTo(row);

        var actionTd = $('<td>');

        $('<button>', {
            class: 'action-btn load-btn',
            title: 'Load Manifest',
            text: '↑',
            click: () => loadManifest(manifest.id),
            disabled: !manifest.editable
        }).appendTo(actionTd);

        $('<button>', {
            class: 'action-btn view-btn',
            title: 'View Details',
            text: '👁',
            click: () => viewManifest(manifest.id)
        }).appendTo(actionTd);

        $('<button>', {
            class: 'action-btn print-btn',
            title: 'Print Manifest',
            text: '👁',
            click: () => printExistingManifest(manifest.id)
        }).appendTo(actionTd);


        // assuming deleteButton is HTML string
        actionTd.append(deleteButton);

        row.append(actionTd);
        tbody.append(row);
    });
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