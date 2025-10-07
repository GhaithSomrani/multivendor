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
    <div class="mv-bulk-actions">
        <div class="mv-select-actions">
            <div class="mv-checkbox">
                <input type="checkbox" id="select-all-orders" class="mv-checkbox-input">
                <label for="select-all-orders"
                    class="mv-checkbox-label">{l s='Tout sélectionner' mod='multivendor'}</label>
            </div>
            <span class="mv-selected-count" id="selected-count">0 {l s='sélectionné(s)' mod='multivendor'}</span>
        </div>
        <div class="mv-bulk-controls">
            <select id="bulk-status-select" class="mv-status-select" disabled>
                <option value="">{l s='Changer le statut à...' mod='multivendor'}</option>
                {foreach from=$vendor_statuses key=status_key item=status_label}
                    <option value="{$status_key}">
                        {$status_label|escape:'html':'UTF-8'|capitalize}
                    </option>
                {/foreach}
            </select>
            <button id="apply-bulk-status" class="mv-btn mv-btn-primary" disabled>
                {l s='Appliquer' mod='multivendor'}
            </button>
        </div>
    </div>

    {* Global MPN Input *}


    <div class="mv-card-body">
        {if $order_lines}
            <div class="mv-table-container">
                <table class="mv-table">
                    <thead>
                        <tr>
                            <th class="mv-checkbox-col"></th>
                            <th>{l s='Référence' mod='multivendor'}</th>
                            <th>{l s='Image' mod='multivendor'}</th>
                            <th>{l s='Produit' mod='multivendor'}</th>
                            <th>{l s='Qté' mod='multivendor'}</th>
                            <th>{l s='Total' mod='multivendor'}</th>
                            <th>{l s='Statut' mod='multivendor'}</th>
                            <th>{l s='Date' mod='multivendor'}</th>
                            <th>{l s='Payé' mod='multivendor'}</th>
                            <th>{l s='Actions' mod='multivendor'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$order_lines item=line}
                            <tr data-id="{$line.id_order_detail}" data-status="{$line.line_status|default:'En attente'|lower}"
                                data-product-mpn="{$line.product_mpn}"
                                data-commission-action="{if isset($line.commission_action)}{$line.commission_action}{else}none{/if}">
                                <td class="mv-checkbox-col">
                                    <input type="checkbox" class="mv-row-checkbox" id="row-{$line.id_order_detail}"
                                        data-id="{$line.id_order_detail}">
                                </td>
                                <td>
                                    <a href="#" class="mv-link">
                                        {$line.id_order} #{$line.id_order_detail}
                                    </a>
                                </td>
                                <td class="mv-product-image">
                                    {assign var="product_image" value=OrderHelper::getProductImageLink($line.product_id, $line.product_attribute_id)}
                                    <img src="{$product_image}" alt="{$line.product_name|escape:'html':'UTF-8'}"
                                        class="mv-product-image">
                                </td>
                                <td class="mv-product-name">
                                    {assign var="product_link" value=VendorHelper::getProductPubliclink($line.product_id, $line.product_attribute_id)}
                                    <b>{$line.product_name}</b>

                                    {assign var="brand" value=VendorOrderDetail::getBrandByProductId($line.product_id)}

                                    <p class="mv-mobile-product-sku"> Marque: {$brand} </p>
                                    <p class="mv-mobile-product-sku"> SKU: {$line.product_reference} </p>
                                    <p class="mv-mobile-product-sku"> {if $line.product_mpn}MPN: {$line.product_mpn}{/if} </p>
                                    <p class="mv-mobile-product-sku">Prix Public: {$line.product_price|number_format:3}</p>
                                </td>
                                <td class="mv-text-center {if $line.product_quantity > 1 } flash-fast {/if}">
                                    {$line.product_quantity}</td>
                                <td>{($line.vendor_amount)|number_format:3} TND</td>
                                <td>
                                    {if $line.status_type_id == $first_status }
                                        <small style="text-align: center; display: block;"> Disponible ?</small>
                                        <div class="mv-quick-action">
                                            <button class="mv-status-btn" style="color: black" id="outofstock"
                                                onclick="openOutOfStockModal({$line.id_order_detail})">🚫 Non</button>
                                            <button class="mv-status-btn" style="color: black" id="available-product"
                                                onclick="mkAvailble({$line.id_order_detail},{$available_status_vendor->id})">✅
                                                Oui</button>
                                        </div>
                                    {else}
                                        <span class="mv-status-badge"
                                            style="background-color: {$status_colors[$line.line_status]|default:'#777'};">
                                            {$line.line_status|capitalize}
                                        </span>
                                    {/if}

                                </td>
                                <td>{$line.order_date|date_format:'%Y-%m-%d'}</td>
                                {assign var='paid' value=TransactionHelper::isOrderDetailPaid($line.id_order_detail)}
                                <td> {if $paid} ✅ {else} ❌ {/if}</td>

                                <td class="mv-actions">
                                    <button class="mv-btn-icon view-status-history"
                                        data-order-detail-id="{$line.id_order_detail}"
                                        title="{l s='Voir l\'historique' mod='multivendor'}">
                                        <i class="mv-icon">📜</i>
                                    </button>
                                    {if $line.status_type_id == $available_status_vendor->id ||
                                        $line.status_type_id == $out_of_stock_status->id}
                                    <button class="mv-btn-comment mv-btn-icon"
                                        onclick='openStatusCommentModal({$line.id_order_detail}, "{$line.product_name}", "{$line.line_status}", "{$status_colors[$line.line_status]|default:'#777'}")'
                                        title="{l s='Add status comment' mod='multivendor'}">
                                        <i class="mv-icon">🔃</i>
                                    </button>
                                {/if}

                                <button class=" mv-btn-icon" title="{l s='Voir le produit' mod='multivendor'}">
                                    <i class="mv-icon"><a href="{$product_link}" target="_blank">🔗</a></i>
                                </button>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
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
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => $page_num, 'status' => $filter_status , 'per_page' => $per_page])}">{$page_num}</a>
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
                        <option value="10" {if $per_page == 16}selected{/if}>16</option>
                        <option value="20" {if $per_page == 20}selected{/if}>20</option>
                        <option value="50" {if $per_page == 50}selected{/if}>50</option>
                        <option value="100" {if $per_page == 100}selected{/if}>100</option>
                        <option value="100" {if $per_page == 200}selected{/if}>200</option>

                    </select>
                </div>
            </nav>
        {/if}
        {else}
        <p class="mv-empty-state">
            {l s='Aucune ligne de commande trouvée.' mod='multivendor'}
        </p>
        {/if}


    </div>
</div>


{* Out of Stock Modal *}
{include file="module:multivendor/views/templates/front/orders/_outofstock_modal.tpl"}

<script>

</script>