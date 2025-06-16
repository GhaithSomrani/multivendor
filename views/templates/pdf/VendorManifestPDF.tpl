    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #fff;
        }

        .pickup-header {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border: 2px solid #333;
            text-align: center;
        }

        .pickup-title {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .pickup-subtitle {
            font-size: 12px;
            color: #666;
        }

        .pickup-section {
            margin-bottom: 15px;
        }

        .pickup-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            font-size: 9px;
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
            font-size: 8px;

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

    {* Debug: Check if manifests data exists *}
    {if !isset($manifests) || !$manifests}
        <div class="pickup-header">
            <div class="pickup-title">BON DE RAMASSAGE</div>
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

        {include file="pdf/header.tpl"}

        <div class="pickup-header">
            <div class="pickup-title">BON DE RAMASSAGE</div>
            <div class="pickup-subtitle">Document de Collecte Multi-Articles</div>
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
                        {/if} </div>
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
                                <td width="50%"> <strong>Total Commandes :</strong></td>
                                <td width="50%">{$total_orders}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {* Calculate summary totals *}
        {assign var=total_packages value=$manifests|count}
        {assign var=total_weight value=0}
        {assign var=total_value value=0}

        {foreach from=$manifests item=manifest}
            {assign var=item_weight value=0.5}
            {if isset($manifest.orderDetail.product_weight) && $manifest.orderDetail.product_weight > 0}
                {assign var=item_weight value=($manifest.orderDetail.product_weight * $manifest.orderDetail.product_quantity)}
            {/if}
            {assign var=total_weight value=$total_weight + $item_weight}
            {if isset($manifest.vendor_amount) && $manifest.vendor_amount}
                {assign var=total_value value=$total_value + $manifest.vendor_amount}
            {/if}
        {/foreach}

        {* Section Résumé *}
        <div class="manifest-summary">
            <div class="pickup-label">RÉSUMÉ DU MANIFESTE :</div>
            <table style="width: 100%;">
                <tr>
                    <td width="25%"><strong>Total Colis :</strong> {$total_packages}</td>
                    <td width="25%"><strong>Poids Total :</strong> {$total_weight|string_format:"%.2f"} kg</td>
                    <td width="25%"><strong>Valeur Totale :</strong> {$total_value|string_format:"%.2f"}</td>
                    <td width="25%"><strong>Statut :</strong> Prêt pour Ramassage</td>
                </tr>
            </table>
        </div>

        {* Tableau Principal des Articles *}
        <div class="pickup-box">
            <div class="pickup-label">ARTICLES À RAMASSER :</div>
            <table class="info-table">
                <thead>
                    <tr>
                        <th width="20%"><strong>Réf. Commande</strong></th>
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
                                        <div style="text-align: center; padding: 5px; height: 100%; position: relative;">
                                            {$manifest.orderDetail.barcode nofilter}
                                            <small
                                                style="font-family: monospace; font-size: 9px;">{$manifest.orderDetail.product_mpn}</small>
                                        </div>
                                    {else}
                                        <div
                                            style="font-family: monospace; font-weight: bold; font-size: 14px; border: 2px solid #000; padding: 5px; background-color: #fff; display: inline-block;">
                                            {$manifest.orderDetail.product_mpn}
                                        </div>
                                    {/if}
                                {else}
                                    <span style="color: #999; font-style: italic;">No MPN</span>
                                {/if}
                            </td>
                            <td width="5%" class="text-center" valign="center">
                                <strong style="font-size: 12px;">{$manifest.orderDetail.product_quantity|default:0}</strong>
                            </td>
                            <td width="10%" class="text-right" valign="center">
                                {if isset($manifest.vendor_amount) && $manifest.vendor_amount}
                                    <strong style="font-size: 12px;">{$manifest.vendor_amount|string_format:"%.2f"}</strong>
                                {else}
                                    <strong>0.00</strong>
                                {/if}
                            </td>
                        </tr>

                        {assign var=total_qty value=$total_qty + ($manifest.orderDetail.product_quantity|default:0)}
                        {if isset($manifest.vendor_amount) && $manifest.vendor_amount}
                            {assign var=calculated_total_value value=$calculated_total_value + $manifest.vendor_amount}
                        {/if}
                        {assign var=item_counter value=$item_counter + 1}
                    {/foreach}

                    {* Ligne des Totaux *}
                    <tr class="total-row">
                        <td colspan="4" class="text-right"><strong>TOTAUX :</strong></td>
                        <td class="text-center"><strong>{$total_qty}</strong></td>
                        <td class="text-right"><strong>{$calculated_total_value|string_format:"%.2f"}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>



        {* Section Signatures *}
        <table style="margin-top: 60px; bottom:0; position: fixed; width: 100%;">
            <tr>
                <td width="33%" style="text-align: center;">
                    <div class="signature-box">
                        <strong>Signature et Date du Fournisseur</strong><br>
                    </div>
                </td>
                <td width="33%" style="text-align: center;">
                    <div class="signature-box">
                        <strong>Signature et Date du Transporteur</strong><br>
                    </div>
                </td>
                <td width="33%" style="text-align: center;">
                    <div class="signature-box">
                        <strong>Heure de Fin de Ramassage</strong><br>
                    </div>
                </td>
            </tr>
        </table>


    {/if}
{* End of manifests check *}