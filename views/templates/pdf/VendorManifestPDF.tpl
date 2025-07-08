{* views/templates/pdf/VendorManifestPDF.tpl - Fixed version without header include *}
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
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
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

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f0f0f0;
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

        .barcode-container {
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
            <div class="pickup-subtitle">{if isset($pdf_subtitle)}{$pdf_subtitle}{else}Document de Collecte Multi-Articles{/if}</div>
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
                        <div class="pickup-label">RAMASSAGE CHEZ (FOURNISSEUR) :</div>
                        <strong>{$first_manifest.supplier_address.name|default:"Nom du fournisseur"}</strong><br>
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
                    <div class="pickup-box">
                        <div class="pickup-label">DÉTAILS DU RAMASSAGE :</div>
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

        {* Items table *}
        <table style="margin-top: 20px;">
            <thead>
                <tr>
                    <th width="20%"><strong>Référence Commande</strong></th>
                    <th width="30%"><strong>Nom du Produit</strong></th>
                    <th width="15%"><strong>SKU</strong></th>
                    <th width="20%"><strong>MPN (Code-barres)</strong></th>
                    <th width="5%"><strong>Qté</strong></th>
                    <th width="10%"><strong>Valeur</strong></th>
                </tr>
            </thead>
            <tbody>
                {assign var=item_counter value=1}
                {assign var=total_qty value=0}
                {assign var=calculated_total_value value=0}

                {foreach from=$manifests item=manifest}
                    <tr>
                        <td width="20%" class="text-center" valign="center">
                            <strong>#{$manifest.order.reference|default:"N/A"}#{$manifest.orderDetail.id|default:"0"}</strong>
                        </td>
                        <td width="30%" valign="center">
                            {$manifest.orderDetail.product_name|default:"Produit sans nom"|escape:'html':'UTF-8'}
                        </td>
                        <td width="15%" class="text-center" valign="center">
                            {$manifest.orderDetail.product_reference|default:"SKU-"|cat:$manifest.orderDetail.id}
                        </td>
                        <td width="20%" class="text-center" valign="center">
                            {if isset($manifest.orderDetail.product_mpn) && $manifest.orderDetail.product_mpn}
                                {if isset($manifest.orderDetail.barcode) && $manifest.orderDetail.barcode}
                                    <div class="barcode-container">
                                        {$manifest.orderDetail.barcode nofilter}
                                        <div style="font-size: 10px; margin-top: 2px;">
                                            {$manifest.orderDetail.product_mpn}
                                        </div>
                                    </div>
                                {else}
                                    {$manifest.orderDetail.product_mpn}
                                {/if}
                            {else}
                                N/A
                            {/if}
                        </td>
                        <td width="5%" class="text-center" valign="center">
                            {$manifest.orderDetail.product_quantity|default:0}
                            {assign var=total_qty value=$total_qty + ($manifest.orderDetail.product_quantity|default:0)}
                        </td>
                        <td width="10%" class="text-right" valign="center">
                            {if isset($manifest.vendor_amount) && $manifest.vendor_amount}
                                {$manifest.vendor_amount|string_format:"%.2f"}€
                                {assign var=calculated_total_value value=$calculated_total_value + $manifest.vendor_amount}
                            {else}
                                {assign var=line_total value=($manifest.orderDetail.product_quantity * $manifest.orderDetail.unit_price_tax_incl)}
                                {$line_total|string_format:"%.2f"}€
                                {assign var=calculated_total_value value=$calculated_total_value + $line_total}
                            {/if}
                        </td>
                    </tr>
                    {assign var=item_counter value=$item_counter + 1}
                {/foreach}

                {* Total row *}
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>TOTAUX :</strong></td>
                    <td class="text-center"><strong>{$total_qty}</strong></td>
                    <td class="text-right"><strong>{$calculated_total_value|string_format:"%.2f"}€</strong></td>
                </tr>
            </tbody>
        </table>

        {* Summary section *}
        <div class="manifest-summary">
            <h3>Résumé du Manifeste</h3>
            <table>
                <tr>
                    <td width="50%"><strong>Total des articles :</strong></td>
                    <td width="50%">{$manifests|count}</td>
                </tr>
                <tr>
                    <td width="50%"><strong>Total des commandes :</strong></td>
                    <td width="50%">{$total_orders}</td>
                </tr>
                <tr>
                    <td width="50%"><strong>Quantité totale :</strong></td>
                    <td width="50%">{$total_qty}</td>
                </tr>
                <tr>
                    <td width="50%"><strong>Valeur totale :</strong></td>
                    <td width="50%"><strong>{$calculated_total_value|string_format:"%.2f"}€</strong></td>
                </tr>
            </table>
        </div>

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
                        <div class="pickup-label">SIGNATURE TRANSPORTEUR :</div>
                        <div style="height: 60px; border-bottom: 1px solid #666; margin-top: 10px;"></div>
                        <div style="margin-top: 5px; font-size: 10px;">
                            Nom et signature du transporteur
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {* Footer with shop information *}
        {if isset($shop_address)}
        <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ccc; padding-top: 10px;">
            {$shop_address}
            {if isset($shop_phone) && $shop_phone}
                <br>Téléphone : {$shop_phone}
            {/if}
        </div>
        {/if}

    {/if}
</body>
</html>