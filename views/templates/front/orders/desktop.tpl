{*
* Desktop Orders Content
*}


{* Status Filter Section *}
{if $order_summary.status_breakdown}
    <div class="mv-card">
        <div class="mv-card-header">
            <h3 class="mv-card-title">{l s='Filtrer par statut' mod='multivendor'}</h3>
        </div>
        <div class="mv-card-body">
            <div class="mv-status-breakdown">
                <div class="mv-status-item">
                    <a href="{$link->getModuleLink('multivendor', 'orders')}"
                        class="mv-status-badge mv-filter-status {if $filter_status == 'all'}active{/if}"
                        style="background-color: #6c757d;">
                        {l s='Tout' mod='multivendor'} : {$order_summary.total_lines}
                    </a>
                </div>
                {foreach from=$order_summary.status_breakdown item=statusData}
                    <div class="mv-status-item">
                        <a href="{$link->getModuleLink('multivendor', 'orders', ['status' => $statusData.id_order_line_status_type])}"
                            class="mv-status-badge mv-filter-status {if $filter_status == $statusData.id_order_line_status_type}active{/if}"
                            style="background-color: {$status_colors[$statusData.status]|default:'#777'};">
                            {$statusData.status|capitalize} : {$statusData.count}
                        </a>
                    </div>
                {/foreach}
            </div>
        </div>
    </div>
{/if}

{* Orders Table *}
<div class="mv-card">
    <div class="mv-card-header">
        <h3 class="mv-card-title">{l s='Lignes de commande' mod='multivendor'}</h3>
        <div class="mv-card-actions">

            <div class="mv-export-buttons">
                <button class="mv-btn mv-btn-export" onclick="exportTableToCSV()">
                    <i class="mv-icon">📥</i>
                    {l s='Exporter CSV' mod='multivendor'}
                </button>
            </div>
        </div>
    </div>




    <div class="mv-card-body">
        <div class="mv-table-container">
            <table class="mv-table">
                <thead>
                    <tr>
                        <th>{l s='Référence' mod='multivendor'}</th>
                        <th>{l s='Image' mod='multivendor'}</th>
                        <th>{l s='Produit' mod='multivendor'}</th>
                        <th>{l s='SKU' mod='multivendor'}</th>
                        <th>{l s='Marque' mod='multivendor'}</th>
                        <th>{l s='Qté' mod='multivendor'}</th>
                        <th>{l s='Total' mod='multivendor'}</th>
                        <th>{l s='Statut' mod='multivendor'}</th>
                        <th>{l s='Date' mod='multivendor'}</th>
                        <th> </th>

                    </tr>
                    <form id="mv-filter-form" method="POST" action="">
                        <input type="hidden" class="mv-filter-input" data-filter="status" value="{$filter_status}">
                        <tr class="mv-filter-row">
                            <th>
                                <input type="text" class="mv-filter-input" data-filter="order_id" style="width: 120px;"
                                    name="filter[order_id]" placeholder="N° Commande"
                                    value="{$filter.order_id|default:''}">
                            </th>
                            <th></th>
                            <th>
                                <input type="text" class="mv-filter-input" data-filter="product_name"
                                    name="filter[product_name]" placeholder="Designation"
                                    value="{$filter.product_name|default:''}">
                            </th>
                            <th>
                                <input type="text" class="mv-filter-input" data-filter="reference"
                                    name="filter[reference]" placeholder="SKU" value="{$filter.reference|default:''}">
                            </th>
                            <th>
                                <input type="text" class="mv-filter-input" data-filter="brand" name="filter[brand]"
                                    placeholder="Marque" value="{$filter.brand|default:''}">
                            </th>
                            <th>
                                <input type="text" style="width: 50px;" data-filter="quantity" class="mv-filter-input"
                                    name="filter[quantity]" placeholder="Qty" value="{$filter.quantity|default:''}">
                            </th>
                            <th class="mv-filter-range">
                                <input type="text" class="mv-filter-input" data-filter="vendor_amount_min"
                                    name="filter[vendor_amount_min]" placeholder="Min"
                                    value="{$filter.vendor_amount_min|default:''}">
                                <input type="text" class="mv-filter-input" data-filter="vendor_amount_max"
                                    name="filter[vendor_amount_max]" placeholder="Max"
                                    value="{$filter.vendor_amount_max|default:''}">
                            </th>
                            <th>

                                <select id="statusfilter" name="filter[commission_action]"
                                    data-filter="commission_action" class="mv-filter-input">
                                    <option value="" selected>Tous</option>
                                    <option value="add" {if $filter.commission_action|default:'' == "add" }
                                        selected{/if}>
                                        Commande Validé</option>
                                    <option value="refund" {if $filter.commission_action|default:'' == "refund"}
                                        selected{/if}>
                                        Commande Retourné</option>
                                    <option value="none" {if  $filter.commission_action|default:'' == "none" } selected
                                        {/if}>
                                        Commande Non Validé</option>
                                </select>
                            </th>
                            <th class="mv-filter-dates">
                                <input type="text" name="filter[datefilter]" data-filter="datefilter"
                                    class="mv-filter-input" placeholder="Période" autocomplete="off"
                                    value="{$filter.datefilter|default:''}" />
                            </th>
                            <th class="mv-filter-range">
                                <button type="button" class="mv-status-btn mv-btn-filter" id="apply-filter">🔍︎</button>
                                <button type="button" class="mv-status-btn mv-btn-reset" id="reset-filter">✖</button>
                            </th>
                        </tr>
                    </form>
                </thead>
                {if $order_lines}
                    <tbody id="orders-tbody">
                        {foreach from=$order_lines item=line}
                            <tr data-id="{$line.id_order_detail}" data-status="{$line.line_status|default:'En attente'|lower}"
                                data-product-mpn="{$line.product_mpn}"
                                data-commission-action="{if isset($line.commission_action)}{$line.commission_action}{else}none{/if}"
                                class="data-row">

                                <td>
                                    <a href="#" class="mv-link">
                                        <strong> {$line.id_order} </strong> <br><small>{$line.id_order_detail}</small>
                                    </a>
                                </td>

                                <td class="mv-product-image">

                                    {assign var="product_image" value=OrderHelper::getProductImageLink($line.product_id, $line.product_attribute_id)}
                                    {assign var="large_image" value=OrderHelper::getProductImageLink($line.product_id, $line.product_attribute_id, 'large_default')}

                                    <div class="zoom-container">

                                        <img src="{$product_image}" data-zoom="{$large_image}"
                                            alt="{$line.product_name|escape:'html':'UTF-8'}" class="zoomable-image mv-product-image">
                                    </div>

                                </td>
                                <td class="mv-product-name">

                                    {assign var="product_link" value=VendorHelper::getProductPubliclink($line.product_id, $line.product_attribute_id)}
                                    <b>{$line.product_name} </b> <a href="{$product_link}" target="_blank"> 🔗 </a>

                                    {assign var="brand" value=VendorOrderDetail::getBrandByProductId($line.product_id)}


                                </td>
                                <td class="mv-text-center" data-sku="{$line.product_reference}">{$line.product_reference}</td>
                                <td class="mv-text-center" data-brand="{$brand}">{$brand}</td>
                                <td class="mv-text-center {if $line.product_quantity > 1 } flash-fast {/if}">
                                    {$line.product_quantity}</td>
                                <td class="mv-text-center">{($line.vendor_amount)|number_format:3} TND</td>
                                <td>
                                    {if $line.status_type_id == $first_status }
                                        <small style="text-align: center; display: block;"> Disponible ?</small>
                                        <div class="mv-quick-action">
                                            <button class="mv-status-btn" style="color: black" id="outofstock"
                                                onclick="openOutOfStockModal({$line.id_order_detail})">🚫 Non</button>
                                            {if $id_vendor == 7}
                                                {assign var="nextstatut" value=26 }
                                            {else}
                                                {assign var="nextstatut" value=$available_status_vendor->id }
                                            {/if}

                                            <button class="mv-status-btn" style="color: black" id="available-product"
                                                onclick="mkAvailble({$line.id_order_detail},  {$nextstatut})">✅
                                                Oui</button>


                                        </div>
                                    {else}
                                        <span class="mv-status-badge"
                                            style="background-color: {$status_colors[$line.line_status]|default:'#777'};">
                                            {$line.line_status|capitalize}
                                        </span>
                                    {/if}

                                </td>
                                <td class="mv-text-center">{$line.order_date|date_format:'%Y-%m-%d'}</td>
                                <td class="mv-text-center" class="mv-checkbox-col">
                                    <button class="mv-collapse-btn" onclick="toggleCollapse(this)">+</button>

                                </td>

                            </tr>
                            <tr class="mv-row-details">
                                <td colspan="10">
                                    <div class="mv-details-split">
                                        <div class="mv-details-left">
                                            <div style=" display:flex ; justify-content:space-between; width:100%;">
                                                <div class="mv-detail-item" style="width: 33%">
                                                    <span class="mv-detail-label">Code barre</span>
                                                    <span class="mv-detail-value">{$line.product_mpn|default:'-'}</span>
                                                </div>
                                                <div class="mv-detail-item" style="width: 33%">
                                                    <span class="mv-detail-label">Prix Public</span>
                                                    <span class="mv-detail-value">{$line.product_price|number_format:3}
                                                        TND</span>
                                                </div>
                                                <div class="mv-detail-item" style="width: 33%">
                                                    <span class="mv-detail-label">Commission</span>
                                                    <span class="mv-detail-value">
                                                        {$line.commission_amount|number_format:3} TND<small><strong>
                                                                {$line.commission_rate|string_format:'%.2f'}% </strong></small>
                                                    </span>
                                                </div>
                                            </div>
                                            {assign var="rm" value=Manifest::getManifestByOrderDetailAndType($line.id_order_detail, 1)}
                                            <div class="mv-detail-item">
                                                <span class="mv-detail-label">Bon de Ramassage</span>
                                                <span class="mv-detail-value">
                                                    <span
                                                        class="mv-detail-badge badge-warning">{if $rm}{$rm.reference}{else}-{/if}</span>
                                                    {if $rm}<div class="mv-detail-date">
                                                            {$rm.date_add|date_format:'%Y-%m-%d %H:%M'}
                                                    </div>{/if}
                                                </span>

                                            </div>

                                            {assign var="rt" value=Manifest::getManifestByOrderDetailAndType($line.id_order_detail, 2)}
                                            <div class="mv-detail-item">
                                                <span class="mv-detail-label">Bon de Retour</span>
                                                <span class="mv-detail-value">
                                                    <span
                                                        class="mv-detail-badge badge-warning">{if $rt}{$rt.reference}{else}-{/if}</span>
                                                    {if $rt}<div class="mv-detail-date">
                                                            {$rt.date_add|date_format:'%Y-%m-%d %H:%M'}
                                                    </div>{/if}
                                                </span>
                                            </div>

                                            {assign var="pay" value=Vendorpayment::getByOrderDetailAndType($line.id_order_detail, 'commission')}
                                            <div class="mv-detail-item">
                                                <span class="mv-detail-label">Paiement</span>
                                                <span class="mv-detail-value">
                                                    <span
                                                        class="mv-detail-badge badge-info">{if $pay && $pay->id}{$pay->reference}{else}-{/if}</span>
                                                    {if $pay && $pay->id}<div class="mv-detail-date">
                                                        {$pay->date_add|date_format:'%Y-%m-%d %H:%M'}</div>{/if}
                                                </span>

                                            </div>

                                            {assign var="refund" value=Vendorpayment::getByOrderDetailAndType($line.id_order_detail, 'refund')}
                                            <div class="mv-detail-item">
                                                <span class="mv-detail-label">Paiement de Retour</span>
                                                <span class="mv-detail-value">
                                                    <span
                                                        class="mv-detail-badge badge-info">{if $refund && $refund->id}{$refund->reference}{else}-{/if}</span>
                                                    {if $refund && $refund->id}<div class="mv-detail-date">
                                                        {$refund->date_add|date_format:'%Y-%m-%d %H:%M'}</div>{/if}
                                                </span>

                                            </div>
                                        </div>

                                        <div class="mv-details-right">
                                            <h4 class="mv-history-title">Historique du statut</h4>
                                            <div class="mv-history-list" data-order-detail="{$line.id_order_detail}">
                                                {assign var="history" value= OrderLineStatusLog::getStatusHistory($line.id_order_detail)}
                                                {if $history}
                                                    {foreach from=$history item=h}
                                                        <div class="mv-history-item">
                                                            <div class="mv-history-date">{$h.date_add|date_format:'%Y-%m-%d %H:%M:%S'}
                                                            </div>
                                                            <div class="mv-history-change">
                                                                <span class="mv-history-old"
                                                                    style="color: {$h.old_status_color};">{$h.old_status_name}</span> →
                                                                <span class="mv-history-new"
                                                                    style="color: {$h.new_status_color};">{$h.new_status_name}</span>
                                                            </div>
                                                            {if $h.comment}
                                                                <div>
                                                                    <pre class="mv-history-change">{$h.comment}</pre>
                                                                </div>
                                                            {/if}
                                                            <div class="mv-history-user">by {$h.changed_by|default:'System'}</div>
                                                        </div>
                                                    {/foreach}
                                                {else}
                                                    <p>Aucun historique</p>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                {else}
                    <tbody>
                        <tr>
                            <td colspan="10">
                                <p class="mv-empty-state">
                                    {l s='Aucune ligne de commande trouvée.' mod='multivendor'}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                {/if}
            </table>
        </div>

        {* Pagination *}
        {if $pages_nb >= 1}
            <nav class="mv-pagination">
                <ul class="mv-pagination-list">
                    {if $current_page < $pages_nb}
                        <li class="mv-pagination-item">
                            <a class="mv-pagination-link"
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => 1, 'status' => $filter_status])}">
                                <span>
                                    << </span>
                            </a>

                        </li>
                        <li class="mv-pagination-item">
                            <a class="mv-pagination-link"
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page-1, 'status' => $filter_status])}">
                                <span>
                                    < </span>
                            </a>
                        </li>
                    {/if}

                    {assign var=p_start value=max(1, $current_page-2)}
                    {assign var=p_end value=min($pages_nb, $current_page+2)}

                    {for $page_num=$p_start to $p_end}
                        <li class="mv-pagination-item {if $page_num == $current_page}mv-pagination-active{/if}">
                            <a class="mv-pagination-link"
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => $page_num, 'status' => $filter_status , 'per_page' => $per_page , "filter" => $filter] )}">{$page_num}</a>
                        </li>
                    {/for}

                    {if $current_page < $pages_nb}
                        <li class="mv-pagination-item">
                            <a class="mv-pagination-link"
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page+1 , 'per_page' => $per_page ])}">
                                <span>></span>
                            </a>
                        </li>
                        <li class="mv-pagination-item">
                            <a class="mv-pagination-link"
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => $pages_nb , 'per_page' => $per_page])}">
                                <span>>></span>
                            </a>
                        </li>
                    {/if}
                </ul>
                {* per page selection*}
                <div class="mv-per-page-select">
                    <label for="per_page">{l s='Lignes par page' mod='multivendor'}:</label>
                    <select id="per_page" class="mv-status-select" onchange="changePerPage(this.value)">
                        <option value="10" {if $per_page == 25}selected{/if}>25</option>
                        <option value="20" {if $per_page == 20}selected{/if}>20</option>
                        <option value="50" {if $per_page == 50}selected{/if}>50</option>
                        <option value="100" {if $per_page == 100}selected{/if}>100</option>
                        <option value="200" {if $per_page == 200}selected{/if}>200</option>

                    </select>
                </div>
            </nav>
        {/if}



    </div>
</div>


{* Out of Stock Modal *}
{include file="module:multivendor/views/templates/front/orders/_outofstock_modal.tpl"}

<script>
    function toggleCollapse(btn) {
        const row = btn.closest('tr');
        const detailsRow = row.nextElementSibling;
        const isOpen = detailsRow.classList.contains('open');

        if (isOpen) {
            detailsRow.classList.remove('open');
            btn.textContent = '+';
        } else {
            detailsRow.classList.add('open');
            btn.textContent = '-';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.zoomable-image').forEach(function(img) {
            new Drift(img, {
                inlineOffsetX: 200,
                zoomFactor :6,
            });
        });
    });
</script>