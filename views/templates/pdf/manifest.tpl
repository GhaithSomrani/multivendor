<style>
    .pickup-header { 
        background-color: #f8f9fa; 
        padding: 10px; 
        margin-bottom: 20px; 
    }
    .pickup-title { 
        font-size: 24px; 
        font-weight: bold; 
        text-align: center; 
    }
    .pickup-section { 
        margin-bottom: 15px; 
    }
    .pickup-label { 
        font-weight: bold; 
    }
    .pickup-box { 
        border: 1px solid #ddd; 
        padding: 10px; 
        margin-bottom: 10px; 
    }
    .pickup-barcode { 
        text-align: center; 
        margin: 20px 0; 
    }
    table { 
        width: 100%; 
    }
    td { 
        padding: 5px; 
    }
    .signature-box { 
        border-top: 1px solid #000; 
        width: 200px; 
        margin: 0 auto; 
    }
</style>

<div class="pickup-header">
    <div class="pickup-title">PICKUP MANIFEST</div>
    <div style="text-align: center;">Transporter Pickup Document</div>
</div>

<table>
    <tr>
        <td width="50%">
            <div class="pickup-box">
                <div class="pickup-label">PICKUP FROM:</div>
                <strong>{$vendor->shop_name}</strong><br>
                {$address.address1}<br>
                {$address.city}, {$address.postcode}<br>
                {$address.country}<br>
                Phone: {$address.phone}<br>
                Contact: {$supplier->name}
            </div>
        </td>
        <td width="50%">
            <div class="pickup-box">
                <div class="pickup-label">PICKUP DETAILS:</div>
                <strong>Date:</strong> {$date}<br>
                <strong>Time:</strong> {$time}<br>
                <strong>Order Reference:</strong> {$order->reference}<br>
                <strong>Pickup ID:</strong> {$pickup_id}
            </div>
        </td>
    </tr>
</table>

<div class="pickup-barcode">
    <div style="font-size: 20px; font-weight: bold;">{$pickup_id}</div>
</div>

<div class="pickup-box">
    <div class="pickup-label">ITEM DETAILS:</div>
    <table border="1" cellpadding="5">
        <tr style="background-color: #f0f0f0;">
            <th width="15%"><strong>SKU</strong></th>
            <th width="45%"><strong>Product Description</strong></th>
            <th width="15%"><strong>Quantity</strong></th>
            <th width="15%"><strong>Weight (kg)</strong></th>
            <th width="10%"><strong>Pieces</strong></th>
        </tr>
        <tr>
            <td>{$orderDetail->product_reference}</td>
            <td>{$orderDetail->product_name}</td>
            <td align="center">{$orderDetail->product_quantity}</td>
            <td align="center">{$orderDetail->product_weight * $orderDetail->product_quantity|string_format:"%.2f"}</td>
            <td align="center">{$orderDetail->product_quantity}</td>
        </tr>
    </table>
</div>

<div class="pickup-box">
    <div class="pickup-label">PACKAGING INSTRUCTIONS:</div>
    <p>Please ensure all items are properly packaged and labeled.<br>
    Fragile items must be marked accordingly.<br>
    All packages must have order reference: <strong>{$order->reference}</strong></p>
</div>

<div class="pickup-box">
    <div class="pickup-label">TRANSPORTER NOTES:</div>
    <div style="height: 50px; border: 1px solid #ccc; background-color: #f9f9f9;"></div>
</div>

<table style="margin-top: 30px;">
    <tr>
        <td width="33%" style="text-align: center;">
            <div class="signature-box">
                Vendor Signature & Date
            </div>
        </td>
        <td width="33%" style="text-align: center;">
            <div class="signature-box">
                Transporter Signature & Date
            </div>
        </td>
        <td width="33%" style="text-align: center;">
            <div class="signature-box">
                Pickup Time
            </div>
        </td>
    </tr>
</table>

<div style="margin-top: 20px; text-align: center; font-size: 9px; color: #666;">
    This document confirms the pickup of the above items by the transporter from the vendor location.<br>
    Generated on: {$date} {$time}
</div>