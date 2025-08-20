<?php
/**
 * Manifest View Template
 * Path: views/templates/admin/manifest/view.tpl
 */
?>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-list"></i>
        {l s='Manifest Details' mod='multivendor'} - {$manifest->reference}
        <span class="panel-heading-action">
            <a class="list-toolbar-btn" href="{$back_url}">
                <i class="process-icon-back"></i> {l s='Back to list' mod='multivendor'}
            </a>
        </span>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-info"></i> {l s='Manifest Information' mod='multivendor'}
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr>
                            <td><strong>{l s='Reference:' mod='multivendor'}</strong></td>
                            <td>{$manifest->reference}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Status:' mod='multivendor'}</strong></td>
                            <td>
                                {if $manifest->id_manifest_status == 1}
                                    <span class="badge" style="background-color: #FF865D;">En préparation</span>
                                {elseif $manifest->id_manifest_status == 2}
                                    <span class="badge" style="background-color: #0079FF;">Prêt</span>
                                {elseif $manifest->id_manifest_status == 3}
                                    <span class="badge" style="background-color: #00DFA2;">Expédié</span>
                                {elseif $manifest->id_manifest_status == 4}
                                    <span class="badge" style="background-color: #00DFA2;">Livré</span>
                                {elseif $manifest->id_manifest_status == 5}
                                    <span class="badge" style="background-color: #FF0060;">Annulé</span>
                                {else}
                                    <span class="badge" style="background-color: #CCCCCC;">Inconnu</span>
                                {/if}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Total Items:' mod='multivendor'}</strong></td>
                            <td>{$total_items}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Created:' mod='multivendor'}</strong></td>
                            <td>{dateFormat date=$manifest->add_date full=true}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Last Update:' mod='multivendor'}</strong></td>
                            <td>{dateFormat date=$manifest->update_date full=true}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-map-marker"></i> {l s='Pickup Address' mod='multivendor'}
                </div>
                <div class="panel-body">
                    <address>
                        <strong>{$vendor_name} </strong><br>
                        {if $address->company}{$address->company}<br>{/if}
                        {$address->address1}<br>
                        {if $address->address2}{$address->address2}<br>{/if}
                        {$address->postcode} {$address->city}<br>
                        {if $address->phone}{l s='Phone:' mod='multivendor'} {$address->phone}<br>{/if}
                        {if $address->phone_mobile}{l s='Mobile:' mod='multivendor'} {$address->phone_mobile}<br>{/if}
                    </address>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <i class="icon-list-ol"></i> {l s='Order Details' mod='multivendor'} ({$total_items} {l s='items' mod='multivendor'})
        </div>
        <div class="panel-body">
            {if $manifest_details && count($manifest_details) > 0}
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{l s='Order ID' mod='multivendor'}</th>
                                <th>{l s='Supplier' mod='multivendor'}</th>
                                <th>{l s='Product' mod='multivendor'}</th>
                                <th>{l s='Reference' mod='multivendor'}</th>
                                <th class="text-center">{l s='Quantity' mod='multivendor'}</th>
                                <th class="text-right">{l s='Price' mod='multivendor'}</th>
                                <th class="text-center">{l s='Added' mod='multivendor'}</th>
                                <th class="text-center">{l s='Actions' mod='multivendor'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $manifest_details as $detail}
                                <tr>
                                    <td>
                                        <a href="{$link->getAdminLink('AdminOrders')}&id_order={$detail.id_order}&vieworder" target="_blank">
                                            {$detail.id_order}
                                        </a>
                                    </td>
                                    <td>{$detail.name}</td>
                                    <td>
                                        <strong>{$detail.product_name}</strong>
                                    </td>
                                    <td>
                                        {if $detail.product_reference}
                                            <code>{$detail.product_reference}</code>
                                        {else}
                                            <em>{l s='No reference' mod='multivendor'}</em>
                                        {/if}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{$detail.product_quantity}</span>
                                    </td>
                                    <td class="text-right">
                                        {$detail.product_price|number_format:2}  TND
                                    </td>
                                    <td class="text-center">
                                        <small>{dateFormat date=$detail.add_date full=false}</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="#" 
                                           class="btn btn-default btn-xs remove-order-detail" 
                                           data-id-manifest="{$manifest->id}" 
                                           data-id-order-detail="{$detail.id_order_details}"
                                           title="{l s='Remove from manifest' mod='multivendor'}">
                                            <i class="icon-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            {else}
                <div class="alert alert-info">
                    <i class="icon-info-circle"></i> {l s='No order details found for this manifest.' mod='multivendor'}
                </div>
            {/if}
        </div>
    </div>

    <div class="panel-footer">
        <a href="{$back_url}" class="btn btn-default">
            <i class="process-icon-back"></i> {l s='Back to list' mod='multivendor'}
        </a>
        <a href="{$link->getAdminLink('AdminManifest')}&id_manifest={$manifest->id}&updatemv_manifest" class="btn btn-primary">
            <i class="process-icon-edit"></i> {l s='Edit manifest' mod='multivendor'}
        </a>
        <button type="button" class="btn btn-info" id="print-manifest">
            <i class="icon-print"></i> {l s='Print manifest' mod='multivendor'}
        </button>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Handle remove order detail
    $('.remove-order-detail').on('click', function(e) {
        e.preventDefault();
        
        var idManifest = $(this).data('id-manifest');
        var idOrderDetail = $(this).data('id-order-detail');
        var row = $(this).closest('tr');
        
        if (confirm('{l s='Are you sure you want to remove this item from the manifest?' mod='multivendor' js=1}')) {
            $.ajax({
                url: '{$link->getAdminLink("AdminManifest")}',
                type: 'POST',
                data: {
                    ajax: true,
                    action: 'removeOrderDetail',
                    id_manifest: idManifest,
                    id_order_detail: idOrderDetail,
                    token: '{$token}'
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut('slow', function() {
                            $(this).remove();
                            // Update total items count
                            var currentTotal = parseInt($('.badge-info').length);
                            // You might want to update the total display here
                        });
                        showSuccessMessage('{l s='Item removed successfully' mod='multivendor' js=1}');
                    } else {
                        showErrorMessage(response.message || '{l s='Error removing item' mod='multivendor' js=1}');
                    }
                },
                error: function() {
                    showErrorMessage('{l s='Network error occurred' mod='multivendor' js=1}');
                }
            });
        }
    });
    
    // Handle print manifest
    $('#print-manifest').on('click', function() {
        window.print();
    });
});

function showSuccessMessage(message) {
    $.growl.notice({ message: message });
}

function showErrorMessage(message) {
    $.growl.error({ message: message });
}
</script>

<style type="text/css">
.badge {
    color: white !important;
}

@media print {
    .panel-footer, 
    .panel-heading-action,
    .remove-order-detail {
        display: none !important;
    }
    
    .panel {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>