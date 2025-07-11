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
    <div class="mv-card-body">
        <div class="mv-mobile-mpn-container">
            <input type="text" id="mobile-global-mpn-input" class="mv-mobile-input"
                placeholder="{l s='Scannez le code-barres MPN...' mod='multivendor'}" autocomplete="off">
            <div class="mv-mobile-mpn-status">
                <span id="mobile-mpn-status-message" class="mv-mobile-status-message">
                    {l s='Prêt à scanner' mod='multivendor'}
                </span>
            </div>
        </div>
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
                                    #{$line.order_reference}#{$line.id_order_detail}
                                </a>
                            </div>
                            <div class="mv-mobile-order-date">
                                {$line.order_date|date_format:'%d/%m/%Y'}
                            </div>
                        </div>

                        <div class="mv-mobile-order-content">
                            <div class="mv-mobile-product-info">
                                <h6 class="mv-mobile-product-name">{$line.product_name}</h6>
                                <p class="mv-mobile-product-sku">SKU: {$line.product_reference}</p>
                            </div>

                            <div class="mv-mobile-order-details">
                                <div class="mv-mobile-detail-row">
                                    <span class="mv-mobile-label">{l s='Quantité' mod='multivendor'}</span>
                                    <span class="mv-mobile-value">{$line.product_quantity}</span>
                                </div>
                                <div class="mv-mobile-detail-row">
                                    <span class="mv-mobile-label">{l s='Total' mod='multivendor'}</span>
                                    <span class="mv-mobile-value mv-mobile-amount">{($line.vendor_amount)|displayPrice}</span>
                                </div>
                            </div>

                            <div class="mv-mobile-status-section">
                                <div class="mv-mobile-current-status">
                                    <span class="mv-mobile-label">{l s='Statut' mod='multivendor'}</span>
                                    <span class="mv-mobile-status-badge"
                                        style="background-color: {$status_colors[$line.line_status]|default:'#777'};">
                                        {$line.line_status|capitalize}
                                    </span>
                                </div>
                            </div>

                            <div class="mv-mobile-actions">
                                <button class="mv-mobile-btn mv-mobile-btn-comment"
                                    onclick='openStatusCommentModal({$line.id_order_detail}, "{$line.product_name}", "{$line.line_status}", "{$status_colors[$line.line_status]|default:'#777'}")'
                                    title="{l s='Ajouter commentaire' mod='multivendor'}">
                                    <i class="mv-icon">🔃</i>
                                    {l s='Modifier le statut' mod='multivendor'}
                                </button>
                                <button class="mv-mobile-btn mv-mobile-btn-history view-status-history"
                                    data-order-detail-id="{$line.id_order_detail}"
                                    title="{l s='Voir l\'historique' mod='multivendor'}">
                                    <i class="mv-icon">📜</i>
                                    {l s='Historique' mod='multivendor'}
                                </button>

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

{* Mobile Pickup Manifest *}
<div id="mobile-pickup-manifest-block" class="mv-card">
    <div class="mv-card-header">
        <h5 class="mv-card-title">{l s='Manifeste de collecte' mod='multivendor'} (<span
                id="mobile-manifest-count">0</span>)</h5>
        <button id="mobile-print-manifest-btn" class="mv-btn-mobile mv-btn-primary-mobile">
            <i class="mv-icon">🖨️</i>
            {l s='Imprimer' mod='multivendor'}
        </button>
    </div>
    <div class="mv-card-body">
        <div class="mv-mobile-manifest-items" id="mobile-manifest-items">
        </div>
    </div>
</div>