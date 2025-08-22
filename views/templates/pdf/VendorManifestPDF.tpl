{* views/templates/pdf/VendorManifestPDF.tpl - Updated version with commission table *}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{if isset($pdf_title)}{$pdf_title}{else}BON DE RAMASSAGE{/if}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 15px;
        }

        .pickup-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .pickup-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .pickup-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .pickup-box {
            padding: 10px;
            margin-bottom: 15px;
        }

        .pickup-label {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            font-size: 6px;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        .details th,
        .details td {
            border: 1px solid #ccc;
        }

        th {
            font-weight: bold;
            text-align: center;
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

        .manifest-summary {
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }

        tr td {
            font-size: 7px;
        }

        .total-row {
            font-weight: bold;
        }

        .barcode-container , th > td {
            text-align: center;
            padding: 5px;
        }

        .barcode-container div {
            margin: 2px 0;
        }
    </style>
</head>

<body>
    {* Debug: Check if manifests data exists *}
    {if !isset($manifests) || !$manifests}
        <div class="pickup-header">
            <div class="pickup-title">{if isset($pdf_title)}{$pdf_title}{else}BON DE RAMASSAGE{/if}</div>
            <div class="pickup-subtitle">Aucun article trouvé</div>
            <p>Données manifestes non disponibles</p>
        </div>
    {else}

        {* Calculate totals and unique orders *}
        {assign var=manifest_id value="MANIFESTE-"|cat:$current_date|cat:"-"|cat:($manifests|count)}
        {assign var=total_orders value=0}
        {assign var=unique_orders value=array()}

        {foreach from=$manifests item=manifest}
            {if isset($manifest.order.id) && !in_array($manifest.order.id, $unique_orders)}
                {assign var=unique_orders value=$unique_orders|array_merge:array($manifest.order.id)}
                {assign var=total_orders value=$total_orders + 1}
            {/if}
        {/foreach}

        <div class="pickup-header">
            <div class="pickup-title">{if isset($pdf_title)}{$pdf_title}{else}BON DE RAMASSAGE{/if}</div>
            <div style="margin-top: 10px;">
                <strong>ID Manifeste :</strong> {$manifest_id}
            </div>
        </div>

        {* Get first manifest for header information *}
        {assign var=first_manifest value=$manifests[0]}

        <table>
            <tr>
                <td width="50%">
                    <div class="pickup-box">
                        <strong>{$first_manifest.vendor.name}</strong><br>
                        {if isset($first_manifest.supplier_address.address) && $first_manifest.supplier_address.address}
                            {$first_manifest.supplier_address.address}<br>
                        {/if}
                        {if isset($first_manifest.supplier_address.address2) && $first_manifest.supplier_address.address2}
                            {$first_manifest.supplier_address.address2}<br>
                        {/if}
                        {if isset($first_manifest.supplier_address.city) && isset($first_manifest.supplier_address.postcode)}
                            {$first_manifest.supplier_address.city}, {$first_manifest.supplier_address.postcode}<br>
                        {/if}
                        {if isset($first_manifest.supplier_address.country) && $first_manifest.supplier_address.country}
                            {$first_manifest.supplier_address.country}<br>
                        {/if}
                        {if isset($first_manifest.supplier_address.phone) && $first_manifest.supplier_address.phone}
                            Téléphone : {$first_manifest.supplier_address.phone}<br>
                        {/if}
                    </div>
                </td>
                <td width="50%">
                    <div class="">
                        <table>
                            <tr>
                                <td width="50%"><strong>Date :</strong></td>
                                <td width="50%">{$current_date|default:$first_manifest.date}</td>
                            </tr>
                            <tr>
                                <td width="50%"><strong>Heure :</strong></td>
                                <td width="50%">{$current_time|default:$first_manifest.time}</td>
                            </tr>
                            <tr>
                                <td width="50%"><strong>Total Articles :</strong></td>
                                <td width="50%">{$manifests|count}</td>
                            </tr>
                            <tr>
                                <td width="50%"><strong>Total Commandes :</strong></td>
                                <td width="50%">{$total_orders}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {* Items table with updated structure matching your image *}
        <table style="margin-top: 20px;" class="details">
            <thead>
                <tr>
                    <th width="12.5%"  align="center"><strong>N° Commandes</strong></th>
                    <th width="30%"  align="center"><strong>Désignation</strong></th>
                    <th width="15%"  align="center"><strong>Référence</strong></th>
                    <th width="12.5%"  align="center"><strong>Code-barres</strong></th>
                    <th width="7.5%" align="center"><strong>Prix Unit Public</strong></th>
                    <th width="5%" align="center"><strong>Qté</strong></th>
                    <th width="7.5%" align="center"><strong>Prix Vendeur HT</strong></th>
                    <th width="10%" align="center"><strong>TOTAL TTC</strong></th>
                </tr>
            </thead>
            <tbody>
                {assign var=item_counter value=0}
                {assign var=total_qty value=0}
                {assign var=calculated_total_value_ht value=0}
                {assign var=calculated_total_value_ttc value=0}

                {foreach from=$manifests item=manifest}
                    <tr>
                        <td width="12.5%" class="text-center" valign="center">
                            <strong>{$manifest.order.id}#{$manifest.orderDetail.id}</strong>
                        </td>
                        <td width="30%" valign="center"  align="center">
                            {$manifest.orderDetail.product_name|default:"Produit sans nom"|escape:'html':'UTF-8'}
                        </td>
                        <td width="15%" class="text-center" valign="center">
                            {$manifest.orderDetail.product_reference|default:"SKU-"|cat:$manifest.orderDetail.id}
                        </td>
                        <td width="12.5%" class="text-center" valign="center">
                            {$manifest.orderDetail.barcode|default:""|escape:'html':'UTF-8'}
                        </td>
                        <td width="7.5%" class="text-right" valign="center">
                            {$manifest.orderDetail.unit_price_tax_incl|default:0|string_format:"%.1f"}
                        </td>
                        <td width="5%" class="text-center" valign="center">
                            {$manifest.orderDetail.product_quantity|default:0}
                            {assign var=total_qty value=$total_qty + ($manifest.orderDetail.product_quantity|default:0)}
                        </td>

                        <td width="7.5%" class="text-right" valign="center">
                            {assign var=vendor_amount_HT value=(($manifest.orderDetail.vendor_amount / $manifest.orderDetail.product_quantity ) / 1.19)}
                            {$vendor_amount_HT|string_format:"%.2f"}

                        </td>
                        <td width="10%" class="text-right" valign="center">
                            {* Calculate HT amount (assuming 19% VAT) *}
                            {assign var=line_total_ht value=(($manifest.orderDetail.product_quantity * $manifest.orderDetail.unit_price_tax_incl) / 1.19)}
                            {assign var=calculated_total_value_ht value=$calculated_total_value_ht + $line_total_ht}
                            {* Vendor amount is already calculated in the manifest *}
                            {$manifest.orderDetail.vendor_amount|string_format:"%.2f"}

                            {* Calculate TTC amount *}
                            {* Calculate TTC amount for commission calculation *}
                            {assign var=calculated_total_value_ttc value=$calculated_total_value_ttc + $manifest.orderDetail.vendor_amount}
                        </td>

                    </tr>
                    {assign var=item_counter value=$item_counter + 1}
                {/foreach}

                {* Summary rows *}
                <tr class="total-row">
                    <td colspan="5" class="text-left"><strong>TOTAL HT</strong></td>
                    <td class="text-center"><strong>{$total_qty}</strong></td>
                    <td colspan="2" class="text-right"><strong>{$calculated_total_value_ht|string_format:"%.2f"}</strong>
                    </td>
                </tr>

                <tr class="total-row">
                    <td colspan="5" class="text-left"><strong>TVA (19%)</strong></td>
                    <td class="text-center"><strong>19%</strong></td>
                    <td colspan="2" class="text-right">
                        <strong>{($calculated_total_value_ht * 0.19)|string_format:"%.4f"}</strong>
                    </td>
                </tr>

                <tr class="total-row">
                    <td colspan="5" class="text-left"><strong>Montant Total TTC</strong></td>
                    <td class="text-center"><strong>{$total_qty}</strong></td>
                    <td colspan="2" class="text-right"><strong>{$calculated_total_value_ttc|string_format:"%.2f"}</strong>
                    </td>
                </tr>
            </tbody>
        </table>

        {* Signature section *}
        <table style="margin-top: 30px;">
            <tr>
                <td width="50%">
                    <div class="pickup-box">
                        <div class="pickup-label">SIGNATURE FOURNISSEUR :</div>
                        <div style="height: 60px; border-bottom: 1px solid #666; margin-top: 10px;"></div>
                        <div style="margin-top: 5px; font-size: 10px;">
                            Nom et cachet du fournisseur
                        </div>
                    </div>
                </td>
                <td width="50%">
                    <div class="pickup-box">
                        <div class="pickup-label">SIGNATURE MAGASINIER :</div>
                        <div style="height: 60px; border-bottom: 1px solid #666; margin-top: 10px;"></div>
                        <div style="margin-top: 5px; font-size: 10px;">
                            Nom et signature du MAGASINIER
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    {/if}
</body>

</html>