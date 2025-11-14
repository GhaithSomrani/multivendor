<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{if isset($pdf_title)}{$pdf_title}{else}BON DE LIVRAISON - FACTURE{/if}</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 5px;
            background: white;
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 5px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }

        .return {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 5px;
            background: #00ba54;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }

        .return:hover {
            background: #008722;

        }

        .no-print:hover {
            background: #005a87;
        }

        .print-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding-bottom: 5px;
        }

        .logo {
            flex: 0 0 200px;
        }

        .logo img {
            max-width: 150px;
            height: auto;
        }

        .website-info {
            text-align: left;
            padding-top: 20px;
        }

        .address {
            font-size: 11px;
            line-height: 1.5;
        }

        .vendor-info {
            flex: 0 0 200px;
            text-align: right;
        }

        .vendor-info h3 {
            font-size: 14px;
            margin: 0 0 10px 0;
            text-transform: uppercase;
        }

        .vendor-info .details {
            font-size: 11px;
            line-height: 1.5;
        }

        .payment-header {
            text-align: center;
            margin: 5px 0;
        }

        .payment-header h2 {
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            padding: 5px 0px;
            /* text-decoration: underline; */
        }

        .payment-info-row {
            display: flex;
            justify-content: space-around;
            background: #f5f5f5;
            padding: 2px;
            border: 1px solid #ddd;
            /* margin-bottom: 20px; */
        }

        .payment-info {
            display: flex;
            justify-content: space-around;
            margin: 5px 0px;
            /* margin-bottom: 20px; */
        }

        .payment-detail {
            text-align: center;
            font-size: 12px;
        }

        @media print {
            .no-print {
                display: none;
            }

            .return {
                display: none;
            }
        }

        .address-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .address-box {
            width: 45%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .address-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }


        .receipt-table th {
            background: #f0f0f0;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #c1c1c1;
        }

        .receipt-table tbody td {
            padding: 5px;
            font-size: 10px;
            border: 1px solid #c5c5c5;
            vertical-align: middle;
        }

        .receipt-table tfoot td {
            padding: 10px 8px;
            font-size: 10px;
            border: 1px solid #000;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .total-section {
            background: #f9f9f9;
            border: 1px solid #8b8b8b;
        }

        .total-row {
            font-weight: bold;
            background: #f0f0f0;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
            height: 80px;
        }

        .signature-label {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }

        .signature-info {
            height: 60px;
        }
    </style>
</head>

<body>

    <body>
        {if !$manifest_id}
            {assign var="linkretrun" value=VendorOrderDetail::getAdminLink() }
            <a class="return" href="{$linkretrun}"> ↩️ Retour</a>
        {/if}
        <button class="no-print" onclick="window.print()">🖨️ Imprimer</button>

        <div class="print-container">
            {if !isset($manifests) || !$manifests}
                <div class="payment-header">
                    <h2>{if isset($pdf_title)}{$pdf_title}{else}BON DE LIVRAISON{/if}</h2>
                    <p>Aucun article trouvé</p>
                </div>
            {else}

                <div class="logo">
                    <img src="{$shop->logo|default:'https://www.lamode.tn/img/la-mode-logo-1738122619.jpg'}" alt="Logo">
                </div>

                <div class="header">
                    <div class="website-info">
                        <div class="address">
                            LaMode enseigne de E-Market SARL<br>
                            MF: 1381766 S/A/M/000<br>
                            2° étage centre X, menzah 9<br>
                            Tunis<br>
                            +216 70 284 274<br>
                        </div>
                    </div>
                    <div id="qrcode"></div>
                    <div class="vendor-info">
                        <h3>Informations Vendeur</h3>
                        <div class="details">
                            {assign var=first_manifest value=$manifests[0]}
                            {if isset($first_manifest.supplier_address) && $first_manifest.supplier_address}
                                <strong>{$first_manifest.vendor.name}</strong><br>
                                {if $first_manifest.supplier_address.address1}{$first_manifest.supplier_address.address1}<br>{/if}
                                {if $first_manifest.supplier_address.address2}{$first_manifest.supplier_address.address2}<br>{/if}
                                {if $first_manifest.supplier_address.postcode && $first_manifest.supplier_address.city}
                                    {$first_manifest.supplier_address.postcode} {$first_manifest.supplier_address.city}<br>
                                {/if}
                                {if $first_manifest.supplier_address.country}{$first_manifest.supplier_address.country}<br>{/if}
                                {if $first_manifest.supplier_address.phone}Tél:
                                {$first_manifest.supplier_address.phone}<br>{/if}
                            {else}
                                <strong>{$first_manifest.vendor.name}</strong><br>
                            {/if}
                        </div>
                    </div>
                </div>

                <div class="payment-header payment-info-row">
                    <h2>
                        {$manifest_type}
                        {if !$manifest_id} interne
                        {/if}</h2>
                </div>

                <div class="payment-info">
                    <div class="payment-detail">
                        <strong>Ref :</strong>
                        {$maniefest_reference}
                    </div>
                    <div class="payment-detail">
                        <strong>Date :</strong>
                        {$current_date|default:$first_manifest.date}
                    </div>
                    <div class="payment-detail">
                        <strong>Heure :</strong>
                        {$current_time|default:$first_manifest.time}
                    </div>
                    <div class="payment-detail">
                        <strong>Quantité :</strong>
                        {$total_qty}
                    </div>
                </div>

                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th width="10%">Num</th>
                            <th width="36%">PRODUIT</th>
                            <th width="12%">RÉFÉRENCE</th>
                            <th width="8%">CODE BARRE</th>
                            <th width="10%">PRIX PUBLIC</th>
                            <th width="4%">QTÉ</th>
                            <th width="10%">PRIX HT</th>
                            <th width="10%">TOTAL TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        {assign var=total_qty value=0}
                        {assign var=total_ht value=0}
                        {assign var=total_commission value=0}
                        {assign var=total_vendor_amount value=0}

                        {foreach from=$manifests item=manifest}
                            {assign var=vendor_price_ht value=($manifest.orderDetail.vendor_amount / $manifest.orderDetail.product_quantity / 1.19)}
                            {assign var=line_total value=$manifest.orderDetail.vendor_amount}
                            {assign var=commission_amount value=($line_total * $commissionRate)}
                            <tr>
                                <td class="text-center" {if !$manifest_id} rowspan="2" {/if}>
                                    <strong>{$manifest.order.id}<br>#{$manifest.orderDetail.id}</strong>
                                </td>
                                <td>
                                    {$manifest.orderDetail.product_name|default:"Produit sans nom"|escape:'html':'UTF-8'}
                                </td>
                                <td class="text-center">
                                    {$manifest.orderDetail.product_reference}
                                </td>
                                <td class="text-center">
                                    <img src="{$manifest.orderDetail.barcode nofilter}" width="100px">
                                    <span>{$manifest.orderDetail.product_mpn}</span>
                                </td>
                                <td class="text-center" >
                                    {$manifest.orderDetail.unit_price_tax_incl|string_format:"%.3f"}
                                </td>
                                <td class="text-center" >
                                    {$manifest.orderDetail.product_quantity|default:0}
                                </td>
                                <td class="text-right" {if !$manifest_id} rowspan="2" {/if}>
                                    {$vendor_price_ht|string_format:"%.3f"}
                                </td>

                                <td class="text-right" {if !$manifest_id} rowspan="2" {/if}>
                                    {$line_total|string_format:"%.3f"}
                                </td>

                            </tr>
                            {if !$manifest_id}
                                {assign var="rm" value=Manifest::getManifestByOrderDetailAndType($manifest.orderDetail.id ,1)}
                                {assign var="rt" value=Manifest::getManifestByOrderDetailAndType($manifest.orderDetail.id ,2)}
                                {assign var="rms" value=ManifestStatusType::getName($rm.id_manifest_status)}
                                {assign var="rts" value=ManifestStatusType::getName($rt.id_manifest_status)}
                                <tr>
                                    <td colspan=5 class="text-center">Bon de Ramasage : {$rm.reference} - ({$rms}) / Bon de
                                        Retour :
                                        {$rt.reference} - ({$rts}) </td>
                                </tr>
                            {/if}
                            {assign var=total_qty value=$total_qty + $manifest.orderDetail.product_quantity}
                            {assign var=total_ht value=$total_ht + ($line_total / 1.19)}
                            {assign var=total_commission value=$total_commission + $commission_amount}
                            {assign var=total_vendor_amount value=$total_vendor_amount + $line_total}
                        {/foreach}
                    </tbody>
                    <tbody class="total-section">
                        <tr class="total-row">
                            <td colspan="5" class="text-left"><strong>TOTAL HT</strong></td>
                            <td class="text-center"><strong>{$total_qty}</strong></td>
                            <td class="text-right" colspan="2"><strong>{$total_ht|string_format:"%.3f"} TND</strong>
                            </td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="5" class="text-left"><strong>TVA (19%)</strong></td>
                            <td class="text-right"><strong>19%</strong></td>
                            <td class="text-right" colspan="2"><strong>{($total_ht * 0.19)|string_format:"%.3f"}
                                    TND</strong></td>
                        </tr>
                        <tr class="total-row" style="background: #e0e0e0;">
                            <td colspan="6" class="text-left"><strong>MONTANT TOTAL TTC</strong></td>

                            <td class="text-right" colspan="3"><strong>{$total_vendor_amount|string_format:"%.3f"}
                                    TND</strong></td>
                        </tr>
                    </tbody>
                </table>
                <table class="receipt-table">
                    <tbody>
                        <tr>
                            <th>Signature et Cachet {$first_manifest.vendor.name}</th>
                            <th>Signature et Cachet LAMODE</th>
                        <tr>
                        <tr>
                            <td class="signature-info">

                            </td>
                            <td class="signature-info">

                            </td>
                        <tr>
                    </tbody>
                </table>

            {/if}

        </div>
        {if $manifest_id}
            <script>
                window.addEventListener("DOMContentLoaded", () => {
                    const baseUrl = window.location.origin;
                    const fullUrl ='{$qrcodelink}';
                    QRCode.toCanvas(
                        fullUrl, { width: 90, margin: 1 },
                        (err, canvas) => {
                            if (err) return console.error(err);
                            document.getElementById("qrcode").appendChild(canvas);
                        }
                    );
                });
            </script>
        {/if}
    </body>

</html>