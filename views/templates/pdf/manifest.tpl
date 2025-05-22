<style>
    .pickup-section { 
        margin-bottom: 15px; 
    }
    .pickup-label { 
        font-weight: bold; 
        color: #333;
        margin-bottom: 5px;
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
        padding: 10px;
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
        border: 1px solid #ddd;
    }
    .info-table th,
    .info-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    .info-table th {
        background-color: #f0f0f0;
        font-weight: bold;
    }
    .text-center {
        text-align: center;
    }
</style>


<table>
    <tr>
        <td width="50%">
            <div class="pickup-box">
                <div class="pickup-label">PICKUP FROM:</div>
                <strong>{$vendor->shop_name|escape:'html':'UTF-8'}</strong><br>
                {if $supplier->name}
                    Supplier: {$supplier->name|escape:'html':'UTF-8'}<br>
                {/if}
                {if $shop_address.address1}
                    {$shop_address.address1|escape:'html':'UTF-8'}<br>
                {/if}
                {if $shop_address.city && $shop_address.postcode}
                    {$shop_address.city|escape:'html':'UTF-8'}, {$shop_address.postcode|escape:'html':'UTF-8'}<br>
                {/if}
                {if $shop_address.country}
                    {$shop_address.country|escape:'html':'UTF-8'}<br>
                {/if}
                {if $shop_address.phone}
                    Phone: {$shop_address.phone|escape:'html':'UTF-8'}<br>
                {/if}
            </div>
        </td>
        <td width="50%">
            <div class="pickup-box">
                <div class="pickup-label">DELIVERY TO:</div>
                {if $customer}
                    <strong>{$customer->firstname|escape:'html':'UTF-8'} {$customer->lastname|escape:'html':'UTF-8'}</strong><br>
                {/if}
                {if $address}
                    {$address->address1|escape:'html':'UTF-8'}<br>
                    {if $address->address2}
                        {$address->address2|escape:'html':'UTF-8'}<br>
                    {/if}
                    {$address->city|escape:'html':'UTF-8'}, {$address->postcode|escape:'html':'UTF-8'}<br>
                    {if $address->phone}
                        Phone: {$address->phone|escape:'html':'UTF-8'}<br>
                    {/if}
                {/if}
            </div>
        </td>
    </tr>
</table>

<div class="pickup-box">
    <div class="pickup-label">PICKUP DETAILS:</div>
    <table>
        <tr>
            <td width="25%"><strong>Date:</strong></td>
            <td width="25%">{$date}</td>
            <td width="25%"><strong>Time:</strong></td>
            <td width="25%">{$time}</td>
        </tr>
        <tr>
            <td><strong>Order Reference:</strong></td>
            <td>{$order->reference|escape:'html':'UTF-8'}</td>
            <td><strong>Pickup ID:</strong></td>
            <td>{$pickup_id|escape:'html':'UTF-8'}</td>
        </tr>
    </table>
</div>

<div class="pickup-barcode">
    <div style="font-size: 20px; font-weight: bold; font-family: monospace;">{$pickup_id|escape:'html':'UTF-8'}</div>
    <div style="font-size: 12px; margin-top: 5px;">Scan this barcode for tracking</div>
</div>

<div class="pickup-box">
    <div class="pickup-label">ITEM DETAILS:</div>
    <table class="info-table">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th width="20%"><strong>SKU</strong></th>
                <th width="40%"><strong>Product Description</strong></th>
                <th width="15%"><strong>Quantity</strong></th>
                <th width="15%"><strong>Weight (kg)</strong></th>
                <th width="10%"><strong>Value</strong></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{$orderDetail->product_reference|escape:'html':'UTF-8'}</td>
                <td>{$orderDetail->product_name|escape:'html':'UTF-8'}</td>
                <td class="text-center">{$orderDetail->product_quantity}</td>
                <td class="text-center">
                    {if $orderDetail->product_weight}
                        {($orderDetail->product_weight * $orderDetail->product_quantity)|string_format:"%.2f"}
                    {else}
                        N/A
                    {/if}
                </td>
                <td class="text-center">
                    {if $orderDetail->total_price_tax_incl}
                        {$orderDetail->total_price_tax_incl|string_format:"%.2f"}
                    {else}
                        N/A
                    {/if}
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="pickup-box">
    <div class="pickup-label">PACKAGING INSTRUCTIONS:</div>
    <p>• Please ensure all items are properly packaged and labeled.<br>
    • Fragile items must be marked accordingly.<br>
    • All packages must have order reference: <strong>{$order->reference|escape:'html':'UTF-8'}</strong><br>
    • Handle with care and keep upright during transport.</p>
</div>

<div class="pickup-box">
    <div class="pickup-label">TRANSPORTER NOTES:</div>
    <div style="height: 60px; border: 1px solid #ccc; background-color: #f9f9f9; margin-top: 10px;">
        <p style="margin: 5px; font-size: 11px; color: #666;">Use this space for any special instructions or notes...</p>
    </div>
</div>

<table style="margin-top: 40px;">
    <tr>
        <td width="33%" style="text-align: center;">
            <div style="height: 60px; border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
            <div class="signature-box">
                Vendor Signature & Date
            </div>
        </td>
        <td width="33%" style="text-align: center;">
            <div style="height: 60px; border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
            <div class="signature-box">
                Transporter Signature & Date
            </div>
        </td>
        <td width="33%" style="text-align: center;">
            <div style="height: 60px; border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
            <div class="signature-box">
                Pickup Time & Date
            </div>
        </td>
    </tr>
</table>

