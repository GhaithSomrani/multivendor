{*
* Single Manifest Template with Multiple Order Lines
*}

<style>
    .pickup-header { 
        background-color: #f8f9fa; 
        padding: 15px; 
        margin-bottom: 20px; 
        border: 2px solid #333;
        text-align: center;
    }
    .pickup-title { 
        font-size: 28px; 
        font-weight: bold; 
        margin-bottom: 5px;
    }
    .pickup-subtitle {
        font-size: 16px;
        color: #666;
    }
    .pickup-section { 
        margin-bottom: 15px; 
    }
    .pickup-label { 
        font-weight: bold; 
        color: #333;
        margin-bottom: 5px;
        font-size: 14px;
    }
    .pickup-box { 
        border: 1px solid #ddd; 
        padding: 15px; 
        margin-bottom: 15px; 
        border-radius: 5px;
        background-color: #fafafa;
    }
    .pickup-barcode { 
        text-align: center; 
        margin: 20px 0; 
        padding: 15px;
        background-color: #f0f0f0;
        border: 2px dashed #999;
    }
    table { 
        width: 100%; 
        border-collapse: collapse;
    }
    td { 
        padding: 8px; 
        vertical-align: top;
    }
    .signature-box { 
        border-top: 1px solid #000; 
        width: 200px; 
        margin: 0 auto; 
        padding-top: 5px;
        text-align: center;
        font-size: 12px;
    }
    .info-table {
        width: 100%;
        border: 1px solid #333;
        margin-bottom: 20px;
    }
    .info-table th,
    .info-table td {
        border: 1px solid #333;
        padding: 8px;
        text-align: left;
        font-size: 11px;
    }
    .info-table th {
        background-color: #e9ecef;
        font-weight: bold;
        text-align: center;
    }
    .text-center {
        text-align: center;
    }
    .text-right {
        text-align: right;
    }
    .manifest-summary {
        background-color: #f8f9fa;
        padding: 10px;
        border: 1px solid #ddd;
        margin-bottom: 15px;
    }
    .total-row {
        background-color: #f0f0f0;
        font-weight: bold;
    }
</style>

<div class="pickup-header">
    <div class="pickup-title">PICKUP MANIFEST</div>
    <div class="pickup-subtitle">Multi-Item Collection Document</div>
    <div style="margin-top: 10px;">
        <strong>Manifest ID:</strong> MANIFEST-{$manifest_id|escape:'html':'UTF-8'}
    </div>
</div>

{* Vendor and Basic Info *}
<table>
    <tr>
        <td width="50%">
            <div class="pickup-box">
                <div class="pickup-label">PICKUP FROM (VENDOR):</div>
                <strong>{$vendor_info.shop_name|escape:'html':'UTF-8'}</strong><br>
                {if $vendor_info.description}
                    {$vendor_info.description|escape:'html':'UTF-8'|truncate:100}<br>
                {/if}
                {if $warehouse_address.address1}
                    {$warehouse_address.address1|escape:'html':'UTF-8'}<br>
                {/if}
                {if $warehouse_address.city && $warehouse_address.postcode}
                    {$warehouse_address.city|escape:'html':'UTF-8'}, {$warehouse_address.postcode|escape:'html':'UTF-8'}<br>
                {/if}
                {if $warehouse_address.country}
                    {$warehouse_address.country|escape:'html':'UTF-8'}<br>
                {/if}
                {if $warehouse_address.phone}
                    Phone: {$warehouse_address.phone|escape:'html':'UTF-8'}<br>
                {/if}
            </div>
        </td>
        <td width="50%">
            <div class="pickup-box">
                <div class="pickup-label">PICKUP DETAILS:</div>
                <table>
                    <tr>
                        <td width="30%"><strong>Date:</strong></td>
                        <td>{$pickup_date}</td>
                    </tr>
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td>{$pickup_time}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Items:</strong></td>
                        <td>{$total_items}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Orders:</strong></td>
                        <td>{$total_orders}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

{* Barcode Section *}
<div class="pickup-barcode">
    <div style="font-size: 24px; font-weight: bold; font-family: monospace;">
        MANIFEST-{$manifest_id|escape:'html':'UTF-8'}
    </div>
    <div style="font-size: 12px; margin-top: 5px;">Scan this barcode for tracking</div>
</div>

{* Summary Section *}
<div class="manifest-summary">
    <div class="pickup-label">MANIFEST SUMMARY:</div>
    <table style="width: 100%;">
        <tr>
            <td width="25%"><strong>Total Packages:</strong> {$summary.total_packages}</td>
            <td width="25%"><strong>Total Weight:</strong> {$summary.total_weight|string_format:"%.2f"} kg</td>
            <td width="25%"><strong>Total Value:</strong> {$summary.total_value|string_format:"%.2f"}</td>
            <td width="25%"><strong>Status:</strong> Ready for Pickup</td>
        </tr>
    </table>
</div>

{* Main Items Table *}
<div class="pickup-box">
    <div class="pickup-label">ITEMS FOR PICKUP:</div>
    <table class="info-table">
        <thead>
            <tr>
                <th width="8%"><strong>#</strong></th>
                <th width="15%"><strong>Order Ref</strong></th>
                <th width="12%"><strong>SKU</strong></th>
                <th width="30%"><strong>Product Description</strong></th>
                <th width="8%"><strong>Qty</strong></th>
                <th width="10%"><strong>Weight (kg)</strong></th>
                <th width="12%"><strong>Customer</strong></th>
                <th width="5%"><strong>Value</strong></th>
            </tr>
        </thead>
        <tbody>
            {assign var=item_counter value=1}
            {assign var=total_qty value=0}
            {assign var=total_weight value=0}
            {assign var=total_value value=0}
            
            {foreach from=$manifests item=manifest}
                <tr>
                    <td class="text-center">{$item_counter}</td>
                    <td class="text-center">
                        <strong>{$manifest.order.reference|escape:'html':'UTF-8'}</strong>
                        <br><small>#{$manifest.orderDetail.id_order_detail}</small>
                    </td>
                    <td class="text-center">
                        {$manifest.orderDetail.product_reference|escape:'html':'UTF-8'}
                    </td>
                    <td>
                        {$manifest.orderDetail.product_name|escape:'html':'UTF-8'|truncate:40}
                        {if $manifest.line_status}
                            <br><small style="color: #666;">Status: {$manifest.line_status}</small>
                        {/if}
                    </td>
                    <td class="text-center">{$manifest.orderDetail.product_quantity}</td>
                    <td class="text-center">
                        {assign var=item_weight value=($manifest.orderDetail.product_weight * $manifest.orderDetail.product_quantity)}
                        {if $item_weight > 0}
                            {$item_weight|string_format:"%.2f"}
                        {else}
                            0.50
                        {/if}
                    </td>
                    <td>
                        <small>
                            {$manifest.customer.firstname|escape:'html':'UTF-8'} {$manifest.customer.lastname|escape:'html':'UTF-8'}
                            <br>{$manifest.delivery_address.city|escape:'html':'UTF-8'}
                        </small>
                    </td>
                    <td class="text-right">
                        {if $manifest.orderDetail.total_price_tax_incl}
                            {$manifest.orderDetail.total_price_tax_incl|string_format:"%.2f"}
                        {else}
                            0.00
                        {/if}
                    </td>
                </tr>
                
                {* Update totals *}
                {assign var=total_qty value=$total_qty + $manifest.orderDetail.product_quantity}
                {assign var=total_weight value=$total_weight + ($manifest.orderDetail.product_weight * $manifest.orderDetail.product_quantity)}
                {assign var=total_value value=$total_value + $manifest.orderDetail.total_price_tax_incl}
                {assign var=item_counter value=$item_counter + 1}
            {/foreach}
            
            {* Totals Row *}
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>TOTALS:</strong></td>
                <td class="text-center"><strong>{$total_qty}</strong></td>
                <td class="text-center"><strong>{if $total_weight > 0}{$total_weight|string_format:"%.2f"}{else}Est. {($manifests|count * 0.5)|string_format:"%.2f"}{/if}</strong></td>
                <td class="text-center"><strong>{$manifests|count} Customers</strong></td>
                <td class="text-right"><strong>{$total_value|string_format:"%.2f"}</strong></td>
            </tr>
        </tbody>
    </table>
</div>

{* Delivery Instructions *}
<div class="pickup-box">
    <div class="pickup-label">PACKAGING & DELIVERY INSTRUCTIONS:</div>
    <table style="width: 100%;">
        <tr>
            <td width="50%" style="vertical-align: top;">
                <strong>Packaging Requirements:</strong>
                <ul style="margin: 5px 0; padding-left: 15px; font-size: 11px;">
                    <li>Each item must be individually packaged</li>
                    <li>Fragile items clearly marked</li>
                    <li>Order reference on each package</li>
                    <li>Handle with care during transport</li>
                </ul>
            </td>
            <td width="50%" style="vertical-align: top;">
                <strong>Delivery Notes:</strong>
                <ul style="margin: 5px 0; padding-left: 15px; font-size: 11px;">
                    <li>Separate deliveries for each customer</li>
                    <li>Verify customer details before delivery</li>
                    <li>Get signature confirmation</li>
                    <li>Return undelivered items to depot</li>
                </ul>
            </td>
        </tr>
    </table>
</div>

{* Special Instructions *}
<div class="pickup-box">
    <div class="pickup-label">SPECIAL INSTRUCTIONS & NOTES:</div>
    <div style="height: 80px; border: 1px solid #ccc; background-color: #fff; margin-top: 10px; padding: 5px;">
        {if $special_instructions}
            {$special_instructions|escape:'html':'UTF-8'}
        {else}
            <p style="margin: 5px; font-size: 11px; color: #666; font-style: italic;">
                No special instructions. Standard pickup and delivery procedures apply.
            </p>
        {/if}
    </div>
</div>

{* Signatures Section *}
<table style="margin-top: 30px;">
    <tr>
        <td width="33%" style="text-align: center;">
            <div style="height: 60px; border-bottom: 2px solid #000; margin-bottom: 5px;"></div>
            <div class="signature-box">
                <strong>Vendor Signature & Date</strong><br>
                <small>Items prepared and ready</small>
            </div>
        </td>
        <td width="33%" style="text-align: center;">
            <div style="height: 60px; border-bottom: 2px solid #000; margin-bottom: 5px;"></div>
            <div class="signature-box">
                <strong>Transporter Signature & Date</strong><br>
                <small>Items collected</small>
            </div>
        </td>
        <td width="33%" style="text-align: center;">
            <div style="height: 60px; border-bottom: 2px solid #000; margin-bottom: 5px;"></div>
            <div class="signature-box">
                <strong>Pickup Completion Time</strong><br>
                <small>Actual collection time</small>
            </div>
        </td>
    </tr>
</table>

{* Footer *}
<div style="margin-top: 20px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px;">
    <p>This manifest contains {$manifests|count} items from {$total_orders} orders • Generated on {$pickup_date} at {$pickup_time}</p>
    <p>For support contact: {if $warehouse_address.phone}{$warehouse_address.phone}{else}Customer Service{/if}</p>
</div>