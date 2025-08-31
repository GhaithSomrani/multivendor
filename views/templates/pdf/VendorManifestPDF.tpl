<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{if isset($pdf_title)}{$pdf_title}{else}BON DE LIVRAISON - FACTURE{/if}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            background: white;
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
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
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .logo {
            flex: 0 0 200px;
        }

        .logo img {
            max-width: 150px;
            height: auto;
        }

        .website-info {
            flex: 1;
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
            margin: 20px 0;
        }

        .payment-header h2 {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .payment-info-row {
            display: flex;
            justify-content: space-around;
            background: #f5f5f5;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .payment-detail {
            text-align: center;
            font-size: 12px;
        }

        @media print {
            .no-print { display: none; }
        }

        .address-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .address-box {
            width: 45%;
            padding: 15px;
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
            margin-bottom: 20px;
            border: 1px solid #000;
        }

        .receipt-table th {
            background: #f0f0f0;
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
        }

        .receipt-table td {
            padding: 10px 8px;
            font-size: 10px;
            border: 1px solid #000;
            vertical-align: top;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .total-section {
            background: #f9f9f9;
            border: 2px solid #000;
        }

        .total-row {
            font-weight: bold;
            background: #f0f0f0;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
            padding: 15px;
            border: 1px solid #333;
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
    </style>
</head>

<body>
<body>
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

                <div class="vendor-info">
                    <h3>Informations Vendeur</h3>
                    <div class="details">
                        {assign var=first_manifest value=$manifests[0]}
                        {if isset($first_manifest.supplier_address) && $first_manifest.supplier_address}
                            <strong>{$first_manifest.supplier_address.name|default:$first_manifest.vendor.name}</strong><br>
                            {if $first_manifest.supplier_address.address1}{$first_manifest.supplier_address.address1}<br>{/if}
                            {if $first_manifest.supplier_address.address2}{$first_manifest.supplier_address.address2}<br>{/if}
                            {if $first_manifest.supplier_address.postcode && $first_manifest.supplier_address.city}
                                {$first_manifest.supplier_address.postcode} {$first_manifest.supplier_address.city}<br>
                            {/if}
                            {if $first_manifest.supplier_address.country}{$first_manifest.supplier_address.country}<br>{/if}
                            {if $first_manifest.supplier_address.phone}Tél: {$first_manifest.supplier_address.phone}<br>{/if}
                        {else}
                            <strong>{$first_manifest.vendor.name}</strong><br>
                        {/if}
                    </div>
                </div>
            </div>

            <div class="payment-header">
                <h2>REÇU DE PAIEMENT</h2>
            </div>

            <div class="payment-info-row">
                <div class="payment-detail">
                    <strong>Date :</strong>
                    {$current_date|default:$first_manifest.date}
                </div>
                <div class="payment-detail">
                    <strong>Heure :</strong>
                    {$current_time|default:$first_manifest.time}
                </div>
                <div class="payment-detail">
                    <strong>Total Articles :</strong>
                    {$manifests|count}
                </div>
            </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th width="12%">N° COMMANDE</th>
                    <th width="30%">PRODUIT</th>
                    <th width="12%">RÉFÉRENCE</th>
                    <th width="10%">CODE BARRE</th>
                    <th width="10%">PRIX PUBLIC</th>
                    <th width="6%">QTÉ</th>
                    <th width="10%">PRIX VENDEUR HT</th>
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
                        <td class="text-center">
                            <strong>{$manifest.order.id}#{$manifest.orderDetail.id}</strong>
                        </td>
                        <td>
                            {$manifest.orderDetail.product_name|default:"Produit sans nom"|escape:'html':'UTF-8'}
                        </td>
                        <td class="text-center">
                            {$manifest.orderDetail.product_reference|default:"SKU-"|cat:$manifest.orderDetail.id}
                        </td>
                        <td class="text-center">
                            {$manifest.orderDetail.barcode}
                        </td>
                        <td class="text-right">
                            {$manifest.orderDetail.unit_price_tax_incl|string_format:"%.2f"} TND
                        </td>
                        <td class="text-center">
                            {$manifest.orderDetail.product_quantity|default:0}
                        </td>
                        <td class="text-right">
                            {$vendor_price_ht|string_format:"%.2f"} TND
                        </td>
                        
                        <td class="text-right">
                            {$line_total|string_format:"%.2f"} TND
                        </td>
                    </tr>

                    {assign var=total_qty value=$total_qty + $manifest.orderDetail.product_quantity}
                    {assign var=total_ht value=$total_ht + ($line_total / 1.19)}
                    {assign var=total_commission value=$total_commission + $commission_amount}
                    {assign var=total_vendor_amount value=$total_vendor_amount + $line_total}
                {/foreach}
            </tbody>
            <tfoot class="total-section">
                <tr class="total-row">
                    <td colspan="5" class="text-left"><strong>TOTAL HT</strong></td>
                    <td class="text-center"><strong>{$total_qty}</strong></td>
                    <td class="text-right"><strong>{$total_ht|string_format:"%.2f"} TND</strong></td>
                    <td class="text-right"><strong>{$total_commission|string_format:"%.2f"} TND</strong></td>
                    <td class="text-right"><strong>{$total_ht|string_format:"%.2f"} TND</strong></td>
                </tr>
                <tr class="total-row">
                    <td colspan="6" class="text-left"><strong>TVA (19%)</strong></td>
                    <td class="text-right"><strong>{($total_ht * 0.19)|string_format:"%.2f"} TND</strong></td>
                    <td class="text-right"><strong>19%</strong></td>
                    <td class="text-right"><strong>{($total_ht * 0.19)|string_format:"%.2f"} TND</strong></td>
                </tr>
                <tr class="total-row" style="background: #e0e0e0;">
                    <td colspan="6" class="text-left"><strong>MONTANT TOTAL TTC</strong></td>
                    <td class="text-right"><strong>{($total_ht * 1.19)|string_format:"%.2f"} TND</strong></td>
                    <td class="text-right"><strong>{$total_commission|string_format:"%.2f"} TND</strong></td>
                    <td class="text-right"><strong>{$total_vendor_amount|string_format:"%.2f"} TND</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">📝 SIGNATURE FOURNISSEUR</div>
                <div style="margin-top: 20px; font-size: 10px; color: #666;">
                    Nom et cachet du fournisseur
                </div>
            </div>
            
            <div class="signature-box">
                <div class="signature-label">📝 SIGNATURE MAGASINIER</div>
                <div style="margin-top: 20px; font-size: 10px; color: #666;">
                    Nom et signature du magasinier
                </div>
            </div>
        </div>
    {/if}
    </div>
</body>
</html>