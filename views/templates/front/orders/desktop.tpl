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
                <label for="select-all-orders" class="mv-checkbox-label">{l s='Tout sélectionner' mod='multivendor'}</label>
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
    <div class="mv-global-mpn-container">
        <div class="mv-input-group">
            <input type="text" id="global-mpn-input" class="form-control mv-global-mpn-input"
                placeholder="{l s='Scannez le code-barres MPN ici...' mod='multivendor'}"
                autocomplete="off">
            <div class="mv-input-group-append">
                <span class="mv-input-group-text">
                    <i class="mv-icon">🔍</i>
                </span>
            </div>
        </div>
        <div class="mv-mpn-status">
            <span id="mpn-status-message" class="mv-status-message">
                {l s='Prêt à scanner le code-barres MPN.' mod='multivendor'}
            </span>
        </div>
    </div>

    <div class="mv-card-body">
        {if $order_lines}
            <div class="mv-table-container">
                <table class="mv-table">
                    <thead>
                        <tr>
                            <th class="mv-checkbox-col"></th>
                            <th>{l s='Référence' mod='multivendor'}</th>
                            <th>{l s='Produit' mod='multivendor'}</th>
                            <th>{l s='Qté' mod='multivendor'}</th>
                            <th>{l s='Total' mod='multivendor'}</th>
                            <th>{l s='Statut' mod='multivendor'}</th>
                            <th>{l s='Date' mod='multivendor'}</th>
                            <th>{l s='Actions' mod='multivendor'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$order_lines item=line}
                            <tr data-id="{$line.id_order_detail}"
                                data-status="{$line.line_status|default:'En attente'|lower}"
                                data-product-mpn="{$line.product_mpn}"
                                data-commission-action="{if isset($line.commission_action)}{$line.commission_action}{else}none{/if}">
                                <td class="mv-checkbox-col">
                                    <input type="checkbox" class="mv-row-checkbox" id="row-{$line.id_order_detail}"
                                        data-id="{$line.id_order_detail}">
                                </td>
                                <td>
                                    <a href="#" class="mv-link">
                                        #{$line.order_reference}#{$line.id_order_detail}
                                    </a>
                                </td>
                                <td class="mv-product-name">{$line.product_name|truncate:80:'...'}
                                    <br> (SKU : {$line.product_reference})
                                </td>
                                <td class="mv-text-center">{$line.product_quantity}</td>
                                <td>{($line.vendor_amount)|displayPrice}</td>
                                <td>
                                    {if isset($all_statuses[$line.status_type_id]) && !isset($vendor_statuses[$line.status_type_id])}
                                        <span class="mv-status-badge"
                                            style="background-color: {$status_colors[$line.line_status]|default:'#777'};">
                                            {$line.line_status|capitalize}
                                        </span>
                                    {else}
                                        <select class="mv-status-select order-line-status-select"
                                            id="status-select-{$line.id_order_detail}"
                                            data-order-detail-id="{$line.id_order_detail}"
                                            data-original-status-type-id="{$line.status_type_id}">
                                            {foreach from=$vendor_statuses key=status_type_id item=status_label}
                                                {assign var="is_changeable" value=OrderHelper::isChangableStatusType($line.id_order_detail, $status_type_id)}
                                                {if $is_changeable ||  $line.status_type_id == $status_type_id}
                                                    <option value="{$status_type_id}"
                                                        {if $line.status_type_id == $status_type_id}selected{/if}
                                                        style="background-color: {$status_colors[$status_label]}; color: white;">
                                                        {$status_label|escape:'html':'UTF-8'|capitalize}
                                                    </option>
                                                {/if}
                                            {/foreach}
                                        </select>
                                    {/if}
                                </td>
                                <td>{$line.order_date|date_format:'%Y-%m-%d'}</td>
                                <td class="mv-actions">
                                    <button class="mv-btn-icon view-status-history"
                                        data-order-detail-id="{$line.id_order_detail}"
                                        title="{l s='Voir l\'historique' mod='multivendor'}">
                                        <i class="mv-icon">📜</i>
                                    </button>
                                    <button class="mv-btn-comment mv-btn-icon"
                                        onclick='openStatusCommentModal({$line.id_order_detail}, "{$line.product_name}", "{$line.line_status}", "{$status_colors[$line.line_status]|default:'#777'}")'
                                        title="{l s='Add status comment' mod='multivendor'}">
                                        <i class="mv-icon">🔃</i>
                                    </button>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>

            {* Pagination *}
            {if $pages_nb > 1}
                <nav class="mv-pagination">
                    <ul class="mv-pagination-list">
                        {if $current_page < $pages_nb}
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page+1, 'status' => $filter_status])}">
                                    <span>›</span>
                                </a>
                            </li>
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'orders', ['page' => $pages_nb, 'status' => $filter_status])}">
                                    <span>»</span>
                                </a>
                            </li>
                        {/if}

                        {assign var=p_start value=max(1, $current_page-2)}
                        {assign var=p_end value=min($pages_nb, $current_page+2)}

                        {for $page_num=$p_start to $p_end}
                            <li class="mv-pagination-item {if $page_num == $current_page}mv-pagination-active{/if}">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'orders', ['page' => $page_num, 'status' => $filter_status])}">{$page_num}</a>
                            </li>
                        {/for}

                        {if $current_page < $pages_nb}
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page+1])}">
                                    <span>›</span>
                                </a>
                            </li>
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'orders', ['page' => $pages_nb])}">
                                    <span>»</span>
                                </a>
                            </li>
                        {/if}
                    </ul>
                </nav>
            {/if}
        {else}
            <p class="mv-empty-state">
                {l s='Aucune ligne de commande trouvée.' mod='multivendor'}
            </p>
        {/if}

        {* Pickup Manifest Block *}
        <div id="pickup-manifest-block" class="mt-4" style="display: none;">
            <div class="mv-card">
                <div class="mv-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mv-card-title">{l s='Manifeste de collecte' mod='multivendor'} (<span id="manifest-count">0</span>)</h3>
                        <button id="print-manifest-btn" class="mv-btn mv-btn-primary">
                            <i class="mv-icon">🖨️</i> {l s='Imprimer le manifeste' mod='multivendor'}
                        </button>
                    </div>
                </div>
                <div class="mv-card-body">
                    <div class="table-responsive">
                        <table class="mv-table">
                            <thead>
                                <tr>
                                    <th>{l s='Réf. commande' mod='multivendor'}</th>
                                    <th>{l s='Produit' mod='multivendor'}</th>
                                    <th>{l s='MPN' mod='multivendor'}</th>
                                    <th>{l s='Quantité' mod='multivendor'}</th>
                                    <th>{l s='Vérifié à' mod='multivendor'}</th>
                                </tr>
                            </thead>
                            <tbody id="manifest-items">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>