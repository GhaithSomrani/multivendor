{*
* Mobile Orders Content - Card Layout
*}

{* Mobile Status Filter Dropdown *}
{if $order_summary.status_breakdown}
    <div class="mv-card mv-mobile-filter-card">
        <div class="mv-card-header">
            <h5 class="mv-card-title">{l s='Filtrer par statut' mod='multivendor'}</h5>
        </div>
        <div class="mv-card-body">
            <select class="mv-filter-dropdown-mobile" onchange="window.location.href=this.value">
                <option value="{$link->getModuleLink('multivendor', 'orders')}" {if $filter_status == 'all'}selected{/if}>
                    {l s='Tout' mod='multivendor'} ({$order_summary.total_lines})
                </option>
                {foreach from=$order_summary.status_breakdown item=statusData}
                    <option
                        value="{$link->getModuleLink('multivendor', 'orders', ['status' => $statusData.id_order_line_status_type])}"
                        {if $filter_status == $statusData.id_order_line_status_type}selected{/if}>
                        {$statusData.status|capitalize} ({$statusData.count})
                    </option>
                {/foreach}
            </select>
        </div>
    </div>
{/if}

{* Mobile Actions Card *}
<div class="mv-card mv-mobile-actions-card">
    <div class="mv-card-header">
        <h5 class="mv-card-title">{l s='Actions' mod='multivendor'}</h5>
    </div>
    <div class="mv-card-body">
        <div class="mv-mobile-actions-grid">
            <button class="mv-btn-mobile mv-btn-export-mobile" onclick="exportTableToCSV()">
                <i class="mv-icon">📥</i>
                {l s='Exporter CSV' mod='multivendor'}
            </button>
            <button class="mv-btn-mobile mv-btn-select-mobile" onclick="toggleSelectAll()">
                <i class="mv-icon">☑️</i>
                {l s='Sélectionner' mod='multivendor'}
            </button>
        </div>

        {* Mobile Bulk Actions *}
        <div class="mv-mobile-bulk-actions" id="mobileBulkActions" style="display: none;">
            <div class="mv-selected-info">
                <span id="mobile-selected-count">0</span> {l s='sélectionné(s)' mod='multivendor'}
            </div>
            <div class="mv-bulk-controls-mobile">
                <select id="mobile-bulk-status-select" class="mv-status-select-mobile">
                    <option value="">{l s='Changer le statut à...' mod='multivendor'}</option>
                    {foreach from=$vendor_statuses key=status_key item=status_label}
                        <option value="{$status_key}">
                            {$status_label|escape:'html':'UTF-8'|capitalize}
                        </option>
                    {/foreach}
                </select>
                <button id="mobile-apply-bulk-status" class="mv-btn-mobile mv-btn-primary-mobile">
                    {l s='Appliquer' mod='multivendor'}
                </button>
            </div>
        </div>
    </div>
</div>

{* Global MPN Scanner - Mobile *}
<div class="mv-card mv-mobile-mpn-card">
    <div class="mv-card-header">
        <h5 class="mv-card-title">{l s='Scanner MPN' mod='multivendor'}</h5>
    </div>

</div>

{* Orders Cards *}
<div class="mv-card mv-mobile-orders-card">
    <div class="mv-card-header">
        <h5 class="mv-card-title">{l s='Lignes de commande' mod='multivendor'}</h5>
    </div>
    <div class="mv-card-body">
        {if $order_lines}
            <div class="mv-mobile-orders-grid">
                {foreach from=$order_lines item=line}
                    <div class="mv-mobile-order-item" data-id="{$line.id_order_detail}"
                        data-status="{$line.line_status|default:'En attente'|lower}" data-product-mpn="{$line.product_mpn}"
                        data-commission-action="{if isset($line.commission_action)}{$line.commission_action}{else}none{/if}">

                        <div class="mv-mobile-order-header">
                            <div class="mv-mobile-order-ref">
                                <input type="checkbox" class="mv-mobile-checkbox" data-id="{$line.id_order_detail}"
                                    style="display: none;">
                                <a href="#" class="mv-mobile-link">
                                    #{$line.id_order}#{$line.id_order_detail}
                                    <span class="view-status-history" data-order-detail-id="{$line.id_order_detail}"
                                        title="{l s='Voir l\'historique' mod='multivendor'}">
                                        <i class="mv-icon">📜</i>
                                    </span>
                                </a>
                            </div>

                            <div class="mv-mobile-order-date">
                                {$line.order_date|date_format:'%d/%m/%Y'}
                            </div>
                        </div>

                        <div class="mv-mobile-order-content">
                            <div class="mv-mobile-product-info">
                                {assign var="product_image" value=OrderHelper::getProductImageLink($line.product_id, $line.product_attribute_id)}
                                <img src="{$product_image}" alt="{$line.product_name|escape:'html':'UTF-8'}"
                                    class="mv-product-image">
                                <span class="mv-mobile-product-name">{$line.product_name}
                                    {assign var="product_link" value=VendorHelper::getProductPubliclink($line.product_id, $line.product_attribute_id)}
                                    <i> <a href="{$product_link}">🔗</a></i>
                                </span>
                            </div>
                            <div class="mv-mobile-product-info">
                                <p class="mv-mobile-product-sku">SKU: {$line.product_reference}</p>
                                {if $line.product_mpn} <p class="mv-mobile-product-sku">MPN: {$line.product_mpn}</p> {/if}
                                {assign var="brand" value=VendorOrderDetail::getBrandByProductId($line.product_id)}
                                {if ($brand)}
                                    <p class="mv-mobile-product-sku">Marque: {$brand}</p>
                                {/if}
                                <p class="mv-mobile-product-sku mv-mobile-value mv-mobile-amount">Prix Public:
                                    {$line.product_price|number_format:3}</p>

                            </div>



                            <div class="mv-mobile-order-details">
                                <div class="mv-mobile-detail-row ">
                                    <span
                                        class="mv-mobile-label {if $line.product_quantity > 1 } flash-fast {/if}">{l s='Quantité' mod='multivendor'}</span>
                                    <span
                                        class="mv-mobile-value {if $line.product_quantity > 1 } flash-fast {/if}">{$line.product_quantity}</span>
                                </div>
                                <div class="mv-mobile-detail-row">
                                    <span class="mv-mobile-label">{l s='Total' mod='multivendor'}</span>
                                    <span class="mv-mobile-value mv-mobile-amount">{($line.vendor_amount)|number_format:3}
                                        TND</span>
                                </div>
                            </div>

                            <div class="mv-mobile-status-section">
                                <div class="mv-mobile-current-status">
                                    {if $line.status_type_id == $first_status}
                                        <small style="text-align: center; display: block; margin-bottom: 8px;">Disponible ?</small>
                                        <div class="mv-quick-action" style="display: flex; gap: 8px;">
                                            <button class="mv-mobile-btn" style="background-color: #ff4444; color: white;"
                                                onclick="openOutOfStockModal({$line.id_order_detail})">
                                                🚫 Non
                                            </button>
                                            <button class="mv-mobile-btn" style="background-color: #44ff44; color: black;"
                                                onclick="mkAvailble({$line.id_order_detail},{$available_status_vendor->id})">
                                                ✅ Oui
                                            </button>
                                        </div>
                                    {else}
                                        <button class="mv-mobile-status-badge mv-mobile-btn"
                                            onclick='openStatusCommentModal({$line.id_order_detail}, "{$line.product_name}", "{$line.line_status}", "{$status_colors[$line.line_status]|default:'#777'}")'
                                            style="background-color: {$status_colors[$line.line_status]|default:'#777'};">
                                            <i class="mv-icon">📃</i>
                                            <span>{$line.line_status|capitalize}</span>
                                        </button>
                                    {/if}
                                </div>
                            </div>


                        </div>
                    </div>
                {/foreach}
            </div>

            {* Mobile Pagination *}
            {if $pages_nb > 1}
                <div class="mv-mobile-pagination">
                    <div class="mv-mobile-pagination-info">
                        {l s='Page' mod='multivendor'} {$current_page} {l s='sur' mod='multivendor'} {$pages_nb}
                    </div>
                    <div class="mv-mobile-pagination-controls">
                        {if $current_page > 1}
                            <a class="mv-mobile-pagination-btn"
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page-1, 'status' => $filter_status])}">
                                ‹ {l s='Précédent' mod='multivendor'}
                            </a>
                        {/if}
                        {if $current_page < $pages_nb}
                            <a class="mv-mobile-pagination-btn"
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page+1, 'status' => $filter_status])}">
                                {l s='Suivant' mod='multivendor'} ›
                            </a>
                        {/if}
                    </div>
                </div>
            {/if}
        {else}
            <div class="mv-mobile-empty-state">
                <div class="mv-mobile-empty-icon">📦</div>
                <p>{l s='Aucune ligne de commande trouvée.' mod='multivendor'}</p>
            </div>
        {/if}
    </div>
</div>

{include file="module:multivendor/views/templates/front/orders/_outofstock_modal.tpl"}