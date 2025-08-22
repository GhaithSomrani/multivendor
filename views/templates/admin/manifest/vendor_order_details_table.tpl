<!-- Vendor Order Details Table -->
<div class="panel" id="vendor-order-details-panel">
    <div class="panel-heading">
        <i class="icon-list-ol"></i> {l s='Vendor Order Details' mod='multivendor'}
        {if $vendor_id > 0}
            <span class="badge">{if $order_details}{$order_details|count}{else}0{/if} {l s='items' mod='multivendor'}</span>
        {/if}
    </div>
    <div class="panel-body">
        {if $show_vendor_selection_message}
            <div class="alert alert-info">
                <i class="icon-info-circle"></i>
                {l s='Please select a vendor to view order details.' mod='multivendor'}
            </div>
        {elseif $show_no_orders_message}
            <div class="alert alert-warning">
                <i class="icon-warning"></i>
                {l s='No order details found for this vendor.' mod='multivendor'}
            </div>
        {elseif $order_details && count($order_details) > 0}
            <div class="table-responsive">
                <table class="table table-striped" id="vendor-order-details-table">
                    <thead>
                        <tr>
                            <th class="text-center">
                                <input type="checkbox" id="select-all-order-details" />
                            </th>
                            <th>{l s='Order ID' mod='multivendor'}</th>
                            <th>{l s='Order Details'}</th>
                            <th>{l s='Product' mod='multivendor'}</th>
                            <th>{l s='Reference' mod='multivendor'}</th>
                            <th class="text-center">{l s='Quantity' mod='multivendor'}</th>
                            <th class="text-right">{l s='Price' mod='multivendor'}</th>
                            <th class="text-center">{l s='Status' mod='multivendor'}</th>
                            <th class="text-center">{l s='Order Date' mod='multivendor'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $order_details as $detail}
                            <tr data-order-detail-id="{$detail.id_order_detail}">
                                <td class="text-center">
                                    <input type="checkbox" class="order-detail-checkbox" name="selected_order_details[]"
                                        value="{$detail.id_order_detail}" data-order-id="{$detail.id_order}"
                                        data-product-name="{$detail.product_name|escape:'html':'UTF-8'}"
                                        data-product-reference="{$detail.product_reference|escape:'html':'UTF-8'}"
                                        data-quantity="{$detail.product_quantity}" />
                                </td>
                                <td>
                                    <br><small class="text-muted"> {$detail.id_order}</small>
                                </td>
                                <td>
                                    {$detail.id_order_detail}
                                </td>
                                <td>
                                    <strong>{$detail.product_name|escape:'html':'UTF-8'}</strong>
                                    {if $detail.product_mpn}
                                        <br><small class="text-muted">MPN: {$detail.product_mpn}</small>
                                    {/if}
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
                                    {if $detail.line_status}
                                        <span class="badge"
                                            style="background-color: {$detail.status_color|default:'#6c757d'}; color: white;">
                                            {$detail.line_status|escape:'html':'UTF-8'}
                                        </span>
                                    {else}
                                        <span class="badge badge-secondary">{l s='Unknown' mod='multivendor'}</span>
                                    {/if}
                                </td>
                                <td class="text-center">
                                    {if $detail.order_date}
                                        {dateFormat date=$detail.order_date full=0}
                                    {else}
                                        <span class="text-muted">-</span>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>


        {/if}
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Initialize handlers after content is loaded
        initializeOrderDetailsHandlers();
    });
</script>