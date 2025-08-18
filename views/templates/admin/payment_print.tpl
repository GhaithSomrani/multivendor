<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - #{$payment->id}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
        }

        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .website-info {
            flex: 1;
            padding-right: 20px;
        }

        .website-info h1 {
            margin: 0 0 5px 0;
            font-size: 18px;
            color: #000;
        }

        .website-info .address {
            font-size: 11px;
            line-height: 1.3;
        }

        .vendor-info {
            flex: 1;
            text-align: right;
            padding-left: 20px;
        }

        .vendor-info h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #000;
        }

        .vendor-info .details {
            font-size: 11px;
            line-height: 1.3;
        }

        .payment-header {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }

        .payment-header h2 {
            margin: 0;
            font-size: 16px;
        }

        .payment-info-row {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
        }

        .payment-detail {
            flex: 1;
            margin: 0 10px;
        }

        .payment-detail strong {
            display: block;
            margin-bottom: 3px;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 11px;
        }

        .transactions-table th,
        .transactions-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }

        .transactions-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .transactions-table .center {
            text-align: center;
        }

        .transactions-table .right {
            text-align: right;
        }

        .totals-row {
            background: #f8f8f8;
            font-weight: bold;
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }

        .footer-left,
        .footer-right {
            flex: 1;
        }

        .footer-right {
            text-align: right;
        }

        /* Signature Section Styles */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .signature-box {
            flex: 1;
            margin: 0 15px;
            text-align: center;
        }

        .signature-box h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            font-weight: bold;
            color: #333;
        }

        .signature-area {
            height: 80px;
            border: 1px solid #000;
            margin-bottom: 10px;
            background: white;
            position: relative;
        }

        .signature-line {
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            color: #666;
        }

        .signature-info {
            font-size: 10px;
            line-height: 1.3;
            color: #555;
        }

        @media print {
            body {
                margin: 0;
                padding: 10px;
            }

            .no-print {
                display: none !important;
            }
        }

        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #007cba;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1000;
        }
        .logo {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <button class="no-print" onclick="window.print()">🖨️ Imprimer</button>

    <div class="print-container">
        <div class="logo">
            <img src="{$shop->logo|default:'https://www.lamode.tn/img/la-mode-logo-1738122619.jpg'}" alt="Logo"
                style="max-width: 150px;">
        </div>
        <div class="header">

            <div class="website-info">
                <div class="address">
                    LaMode enseigne de E-Market SARL<br>
                    MF: 1381766 S/A/M/000<br>
                    2° étage centre X, menzah 9 <br>
                    Tunis<br>
                    +216 70 284 274<br>
                </div>
            </div>

            <div class="vendor-info">
                <h3>Informations Vendeur</h3>
                <div class="details">
                    {if $vendor_address}
                        <strong>{$vendor_address.name|default:$vendor->shop_name}</strong><br>
                        {if $vendor_address.address}{$vendor_address.address}<br>{/if}
                        {if $vendor_address.address2}{$vendor_address.address2}<br>{/if}
                        {if $vendor_address.city && $vendor_address.postcode}{$vendor_address.postcode}
                        {$vendor_address.city}<br>{/if}
                        {if $vendor_address.country}{$vendor_address.country}<br>{/if}
                        {if $vendor_address.phone}Tél: {$vendor_address.phone}<br>{/if}
                    {else}
                        <strong>{$vendor->shop_name}</strong><br>
                        {if $vendor->address}{$vendor->address}<br>{/if}
                        {if $vendor->city}{$vendor->postcode} {$vendor->city}<br>{/if}
                        {if $vendor->phone}Tél: {$vendor->phone}<br>{/if}
                        {if $vendor->email}Email: {$vendor->email}<br>{/if}
                    {/if}
                </div>
            </div>
        </div>

        <!-- Payment Header -->
        <div class="payment-header">
            <h2>REÇU DE PAIEMENT</h2>
        </div>

        <!-- Payment Details Row -->
        <div class="payment-info-row">
            <div class="payment-detail">
                <strong>Date :</strong>
                {$payment->date_add|date_format:'%Y-%m-%d'}
            </div>
            <div class="payment-detail">
                <strong>Heure :</strong>
                {$payment->date_add|date_format:'%H:%M'}
            </div>
            <div class="payment-detail">
                <strong>Total Articles :</strong>
                {if $transaction_details}{$transaction_details|@count}{else}0{/if}
            </div>
          
        </div>

        <!-- Transactions Table -->
        {if $transaction_details}
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Référence<br>Commande</th>
                        <th>Nom du Produit</th>
                        <th>SKU</th>
                        <th>MPN (Code-barres)</th>
                        <th>Qté</th>
                        <th>Montant HT</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$transaction_details item=detail}
                        <tr>
                            <td>#{$detail.order_reference|default:'N/A'}</td>
                            <td>{$detail.product_name|default:'N/A'}</td>
                            <td>{$detail.product_reference|default:'-'}</td>
                            <td class="center">-</td>
                            <td class="center">{$detail.product_quantity|default:'1'}</td>
                            <td class="right">{($detail.vendor_amount*0.81)|number_format:2} TND</td>
                        </tr>
                    {/foreach}
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="4"><strong>Montant Total HT :</strong></td>
                        <td class="center">
                            <strong>{if $transaction_details}{$transaction_details|@count}{else}0{/if}</strong>
                        </td>
                        <td class="right"><strong>{($payment->amount*0.81)|number_format:2} TND</strong></td>
                    </tr>
                    <tr>
                        <td colspan="4"><strong>TVA :</strong></td>
                        <td class="center">19%</td>
                        <td class="right">{($payment->amount*0.19)|number_format:2} TND</td>
                    </tr>
                    <tr class="totals-row">
                        <td colspan="4"><strong>Montant Total TTC :</strong></td>
                        <td class="center">
                            <strong>{if $transaction_details}{$transaction_details|@count}{else}0{/if}</strong>
                        </td>
                        <td class="right"><strong>{$payment->amount|number_format:2} TND</strong></td>
                    </tr>
                </tfoot>
            </table>
        {else}
            <div style="text-align: center; padding: 20px; background: #f5f5f5; margin: 20px 0;">
                Aucune transaction trouvée pour ce paiement.
            </div>
        {/if}

        <!-- Footer -->
        <div class="footer-info">
            <div class="footer-left">
                <strong>Référence Paiement:</strong> {$payment->reference|default:'N/A'}<br>
                <strong>Méthode:</strong> {$payment->payment_method|default:'N/A'|ucfirst}<br>
                <strong>Statut:</strong> {$payment->status|ucfirst}
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <h4>SIGNATURE VENDEUR</h4>
                <div class="signature-area">
                    <div class="signature-line">Signature et Cachet</div>
                </div>
                <div class="signature-info">
                    Nom: ___________________<br>
                    Date: __________________
                </div>
            </div>

            <div class="signature-box">
                <h4>SIGNATURE LA MODE</h4>
                <div class="signature-area">
                    <div class="signature-line">Signature et Cachet</div>
                </div>
                <div class="signature-info">
                    Nom: ___________________<br>
                    Date: __________________
                </div>
            </div>
        </div>
    </div>
</body>

</html>