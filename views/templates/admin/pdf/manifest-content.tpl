{*
* Manifest PDF Content Template
* File: views/templates/admin/pdf/manifest-content.tpl
*}

<style>
    body { font-family: Arial, sans-serif; font-size: 10px; }
    .info-section { margin-bottom: 20px; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table td { padding: 5px; border: 1px solid #ddd; }
    .info-table .label { background-color: #f8f9fa; font-weight: bold; width: 30%; }
    .items-table { width: 100%; border-collapse: collapse; font-size: 9px; }
    .items-table th { background-color: #007bff; color: white; padding: 8px; text-align: left; }
    .items-table td { padding: 6px; border: 1px solid #ddd; }
    .items-table tr:nth-child(even) { background-color: #f8f9fa; }
    .summary-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .summary-table td { padding: 8px; border: 1px solid #ddd; }
    .summary-table .total-row { background-color: #e3f2fd; font-weight: bold; }
</style>

{* Manifest Information Section *}
<div class="info-section">
    <h3 style="color: #007bff; margin-bottom: 10px;">{l s='Manifest Information' mod='multivendor'}</h3>
    <table class="info-table">
        <tr>
            <td class="label">{l s='Reference:' mod='multivendor'}</td>
            <td>#{$manifest->reference}</td>
            <td class="label">{l s='Status:' mod='multivendor'}</td>
            <td style="text-transform: uppercase;">{$manifest->status}</td>
        </tr>
        <tr>
            <td class="label">{l s='Creation Date:' mod='multivendor'}</td>
            <td>{$manifest->date_add|date_format:"%d/%m/%Y %H:%M"}</td>
            <td class="label">{l s='Total Items:' mod='multivendor'}</td>
            <td>{$manifest->total_items}</td>
        </tr>
    </table>
</div>

{* Vendor Information Section *}
<div class="info-section">
    <h3 style="color: #007bff; margin-bottom: 10px;">{l s='Vendor Information' mod='multivendor'}</h3>
    <table class="info-table">
        <tr>
            <td class="label">{l s='Vendor ID:' mod='multivendor'}</td>
            <td>#{$vendor->id}</td>
            <td class="label">{l s='Shop Name:' mod='multivendor'}</td>
            <td>{$vendor->shop_name}</td>
        </tr>
        <tr>
            <td class="label">{l s='Supplier ID:' mod='multivendor'}</td>
            <td>#{$vendor->id_supplier}</td>
            <td class="label">{l s='Status:' mod='multivendor'}</td>
            <td>
                {if $vendor->status == 1}
                    {l s='Active' mod='multivendor'}
                {elseif $vendor->status == 0}
                    {l s='Pending' mod='multivendor'}
                {else}
                    {l s='Inactive' mod='multivendor'}
                {/if}
            </td>
        </tr>
    </table>
</div>

{* Shipping Address Section *}
{if $shipping_address}
<div class="info-section">
    <h3 style="color: #007bff; margin-bottom: 10px;">{l s='Shipping Address' mod='multivendor'}</h3>
    <table class="info-table">
        <tr>
            <td class="label">{l s='Company/Name:' mod='multivendor'}</td>
            <td>
                {if $shipping_address.company}
                    {$shipping_address.company}
                {else}
                    {$shipping_address.firstname} {$shipping_address.lastname}
                {/if}
            </td>
        </tr>
        <tr>
            <td class="label">{l s='Address:' mod='multivendor'}</td>
            <td>
                {$shipping_address.address1}
                {if $shipping_address.address2}<br>{$shipping_address.address2}{/if}
            </td>
        </tr>
        <tr>
            <td class="label">{l s='City/Postal:' mod='multivendor'}</td>
            <td>{$shipping_address.city}, {$shipping_address.postcode}</td>
        </tr>
        {if $shipping_address.phone}
        <tr>
            <td class="label">{l s='Phone:' mod='multivendor'}</td>
            <td>{$shipping_address.phone}</td>
        </tr>
        {/if}
    </table>
</div>
{/if}

{* Order Details Section *}
<div class="info-section">
    <h3 style="color: #007bff; margin-bottom: 10px;">{l s='Order Details' mod='multivendor'}</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 25%;">{l s='Product' mod='multivendor'}</th>
                <th style="width: 12%;">{l s='Reference' mod='multivendor'}</th>
                <th style="width: 15%;">{l s='Order' mod='multivendor'}</th>
                <th style="width: 20%;">{l s='Customer' mod='multivendor'}</th>
                <th style="width: 8%;">{l s='Qty' mod='multivendor'}</th>
                <th style="width: 20%;">{l s='Delivery Address' mod='multivendor'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach $order_details as $detail}
            <tr>
                <td style="font-weight: bold;">
                    {$detail.product_name|truncate:40}
                </td>
                <td>
                    {$detail.product_reference|default:'N/A'}
                </td>
                <td>
                    #{$detail.order_reference}<br>
                    <small style="color: #666;">{$detail.order_date|date_format:"%d/%m/%Y"}</small>
                </td>
                <td>
                    {$detail.firstname} {$detail.lastname}
                </td>
                <td style="text-align: center; font-weight: bold;">
                    {$detail.product_quantity}
                </td>
                <td style="font-size: 8px;">
                    {if $detail.address2}{$detail.address2}, {/if}
                    {$detail.address1}<br>
                    {$detail.city}, {$detail.postcode}<br>
                    {$detail.country_name}
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>
</div>

{* Summary Section *}
<div class="info-section">
    <h3 style="color: #007bff; margin-bottom: 10px;">{l s='Summary' mod='multivendor'}</h3>
    <table class="summary-table" style="width: 50%;">
        <tr>
            <td class="label" style="background-color: #f8f9fa; font-weight: bold;">
                {l s='Total Items:' mod='multivendor'}
            </td>
            <td style="text-align: right; font-weight: bold;">
                {$total_quantity}
            </td>
        </tr>
        <tr>
            <td class="label" style="background-color: #f8f9fa; font-weight: bold;">
                {l s='Total Orders:' mod='multivendor'}
            </td>
            <td style="text-align: right; font-weight: bold;">
                {$order_details|@count}
            </td>
        </tr>
        {if $total_amount > 0}
        <tr class="total-row">
            <td class="label">
                {l s='Total Amount:' mod='multivendor'}
            </td>
            <td style="text-align: right; font-weight: bold;">
                {displayPrice price=$total_amount}
            </td>
        </tr>
        {/if}
    </table>
</div>

{* Notes Section *}
<div class="info-section" style="margin-top: 30px;">
    <h4 style="color: #666; margin-bottom: 10px;">{l s='Notes:' mod='multivendor'}</h4>
    <div style="border: 1px solid #ddd; padding: 10px; min-height: 40px; background-color: #f8f9fa;">
        <p style="margin: 0; color: #666; font-style: italic;">
            {l s='This manifest contains all order details ready for processing. Please verify all information before shipping.' mod='multivendor'}
        </p>
    </div>
</div>

{* Signature Section *}
<div style="margin-top: 40px;">
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; padding: 20px; border: 1px solid #ddd;">
                <div style="text-align: center;">
                    <strong>{l s='Prepared By' mod='multivendor'}</strong><br><br>
                    <div style="border-bottom: 1px solid #666; width: 200px; margin: 20px auto;"></div>
                    {l s='Name & Signature' mod='multivendor'}<br>
                    {l s='Date:' mod='multivendor'} ________________
                </div>
            </td>
            <td style="width: 50%; padding: 20px; border: 1px solid #ddd;">
                <div style="text-align: center;">
                    <strong>{l s='Verified By' mod='multivendor'}</strong><br><br>
                    <div style="border-bottom: 1px solid #666; width: 200px; margin: 20px auto;"></div>
                    {l s='Name & Signature' mod='multivendor'}<br>
                    {l s='Date:' mod='multivendor'} ________________
                </div>
            </td>
        </tr>
    </table>
</div>