<!-- Manifest View Template -->
<div class="panel">
    <div class="panel-heading">
        <i class="icon-list"></i> {l s='Manifest Details' mod='multivendor'}
        <span class="badge manifest-count">{$total_items}</span>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <h4>{l s='Manifest Information' mod='multivendor'}</h4>
                <dl class="dl-horizontal">
                    <dt>{l s='Reference:' mod='multivendor'}</dt>
                    <dd>{$manifest->reference}</dd>

                    <dt>{l s='Vendor:' mod='multivendor'}</dt>
                    <dd>{$vendor_name}</dd>

                    <dt>{l s='Type:' mod='multivendor'}</dt>
                    <dd>
                        {if $manifest->type == 1}
                            {l s='Pickup' mod='multivendor'}
                        {else}
                            {l s='Returns' mod='multivendor'}
                        {/if}
                    </dd>

                    <dt>{l s='Status:' mod='multivendor'}</dt>
                    {* <dd>
                        <span class="badge badge-info">{$manifest->status}</span>
                    </dd> *}

                    <dt>{l s='Created:' mod='multivendor'}</dt>
                    <dd>{$manifest->add_date}</dd>
                </dl>
            </div>

            <div class="col-md-6">
                <h4>{l s='Pickup Address' mod='multivendor'}</h4>
                {if $address}
                    <address>
                        {$address->firstname} {$address->lastname}<br>
                        {$address->address1}<br>
                        {if $address->address2}{$address->address2}<br>{/if}
                        {$address->postcode} {$address->city}<br>
                        {if $address->phone}{l s='Phone:' mod='multivendor'} {$address->phone}<br>{/if}
                    </address>
                {else}
                    <p class="text-muted">{l s='No address specified' mod='multivendor'}</p>
                {/if}
            </div>
        </div>

        {if $manifest_details && count($manifest_details) > 0}
            <h4>{l s='Items in Manifest' mod='multivendor'}</h4>
            <div class="table-responsive">
                <table class="table table-striped" id="manifest-details-table">
                    <thead>
                        <tr>
                            <th>{l s='Order Reference' mod='multivendor'}</th>
                            <th>{l s='Product' mod='multivendor'}</th>
                            <th>{l s='Reference' mod='multivendor'}</th>
                            <th class="text-center">{l s='Quantity' mod='multivendor'}</th>
                            <th class="text-right">{l s='Price' mod='multivendor'}</th>
                            <th class="text-center">{l s='Actions' mod='multivendor'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $manifest_details as $detail}
                            <tr data-order-detail-id="{$detail.id_order_details}">
                                <td>
                                    <a href="{$link->getAdminLink('AdminOrders')}&id_order={$detail.id_order}&vieworder">
                                        {$detail.order_reference}
                                    </a>
                                </td>
                                <td>
                                    <strong>{$detail.product_name|escape:'html':'UTF-8'}</strong>
                                </td>
                                <td>
                                    {if $detail.product_reference}
                                        {$detail.product_reference|escape:'html':'UTF-8'}
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info">{$detail.product_quantity}</span>
                                </td>
                                <td class="text-right">
                                    {if $detail.product_price}
                                        {displayPrice price=$detail.product_price}
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-danger remove-order-detail"
                                        data-id-manifest="{$manifest->id}" data-id-order-detail="{$detail.id_order_details}"
                                        title="{l s='Remove from manifest' mod='multivendor'}">
                                        <i class="icon-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <div class="alert alert-info">
                <i class="icon-info-circle"></i>
                {l s='No items in this manifest.' mod='multivendor'}
            </div>
        {/if}
    </div>

    <div class="panel-footer">
        <a href="{$back_url}" class="btn btn-default">
            <i class="process-icon-back"></i> {l s='Back to list' mod='multivendor'}
        </a>
        <a href="{$link->getAdminLink('AdminManifest')}&id_manifest={$manifest->id}&updatemv_manifest"
            class="btn btn-primary">
            <i class="process-icon-edit"></i> {l s='Edit manifest' mod='multivendor'}
        </a>
        <button type="button" class="btn btn-info" id="print-manifest">
            <i class="icon-print"></i> {l s='Print manifest' mod='multivendor'}
        </button>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Initialize manifest view handlers
        manifestViewHandlers();
    });
</script>