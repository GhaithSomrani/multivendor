{*
* Vendor Orders Template with Custom CSS and AJAX
*}

{extends file='page.tpl'}

{block name='page_title'}
    {l s='Mes lignes de commande' mod='multivendor'}
{/block}

{block name='page_content'}
    <div class="mv-dashboard">
        <div class="mv-container">
            <aside class="mv-sidebar">
                <div class="mv-card">
                    <div class="mv-card-body">
                        <nav class="mv-nav">
                            <a class="mv-nav-link" href="{$vendor_dashboard_url}">
                                <i class="mv-icon">📊</i>
                                <span>{l s='Tableau de bord' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link mv-nav-link-active" href="{$vendor_orders_url}">
                                <i class="mv-icon">🛒</i>
                                <span>{l s='Commandes' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link" href="{$vendor_commissions_url}">
                                <i class="mv-icon">💰</i>
                                <span>{l s='Commissions' mod='multivendor'}</span>
                            </a>
                        </nav>

                    </div>
                </div>
            </aside>

            <main class="mv-main-content">
                <div class="mv-stats-grid">
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Total des lignes de commande' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{$order_summary.total_lines}</h3>
                            <p class="mv-stat-description">{l s='Tout le temps' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Revenus (28 derniers jours)' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{Tools::displayPrice($order_summary.total_revenue)}</h3>
                            <p class="mv-stat-description">{l s='Vos gains après commission' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Commandes d\'aujourd\'hui' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{$order_summary.todays_orders}</h3>
                            <p class="mv-stat-description">{l s='Nouvelles lignes de commande' mod='multivendor'}</p>
                        </div>
                    </div>
                </div>

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
                            <span class="mv-selected-count" id="selected-count">0
                                {l s='sélectionné(s)' mod='multivendor'}</span>
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

                    {* Global MPN Input at the top of the table *}
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
                        <div id="pickup-manifest-block" class="mt-4" style="display: none;">
                            <div class="mv-card">
                                <div class="mv-card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h3 class="mv-card-title">{l s='Manifeste de collecte' mod='multivendor'} (<span
                                                id="manifest-count">0</span>)</h3>
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
            </main>
        </div>
    </div>

    <div class="mv-modal" id="statusHistoryModal">
        <div class="mv-modal-backdrop" onclick="$('#statusHistoryModal').removeClass('mv-modal-open')"></div>
        <div class="mv-modal-content">
            <div class="mv-modal-header">
                <h5 class="mv-modal-title">{l s='Historique des statuts' mod='multivendor'}</h5>
                <button class="mv-modal-close" onclick="$('#statusHistoryModal').removeClass('mv-modal-open')">×</button>
            </div>
            <div class="mv-modal-body" id="statusHistoryContent">
            </div>
        </div>
    </div>

    <script>
        const bulkStatusChangeConfirmText = "{l s='Êtes-vous sûr de vouloir changer le statut des commandes sélectionnées ?' mod='multivendor'}";
        const bulkChangeComment = "{l s='Statut modifié via l\'action groupée' mod='multivendor'}";
        const processingText = "{l s='Traitement...' mod='multivendor'}";
        const applyText = "{l s='Appliquer' mod='multivendor'}";
        const selectedText = "{l s='sélectionné(s)' mod='multivendor'}";
        const successStatusText = "{l s='commandes mises à jour avec succès.' mod='multivendor'}";
        const errorStatusText = "{l s='commandes n\'ont pas pu être mises à jour.' mod='multivendor'}";
    </script>

   <div class="mv-status-comment-modal" id="statusCommentModal">
    <div class="mv-status-comment-content">
        <div class="mv-status-comment-header">
            <h5 class="mv-status-comment-title">{l s='Mettre à jour le statut avec commentaire' mod='multivendor'}</h5>
            <button class="mv-status-comment-close" onclick="closeStatusCommentModal()">×</button>
        </div>
        <div class="mv-status-comment-body">
            <!-- Current status display -->
            <div class="mv-current-status">
                <div class="mv-current-status-label">{l s='Statut actuel' mod='multivendor'}</div>
                <span id="currentStatusBadge" class="mv-status-badge">
                    {l s='En attente' mod='multivendor'}
                </span>
            </div>

            <!-- Product info -->
            <div class="mv-form-group">
                <label class="mv-form-label">{l s='Produit' mod='multivendor'}</label>
                <div id="productInfo" style="font-weight: 500; color: #333;">
                    {l s='Nom du produit' mod='multivendor'}
                </div>
            </div>

            <!-- New status selection - ONLY AVAILABLE STATUSES -->
            <div class="mv-form-group">
                <label for="newStatusSelect" class="mv-form-label">{l s='Nouveau statut' mod='multivendor'} *</label>
                <select id="newStatusSelect" class="mv-form-control" required>
                    <option value="">{l s='Sélectionnez un nouveau statut...' mod='multivendor'}</option>
                    {* Options will be populated dynamically by JavaScript based on allowed transitions for this specific order detail *}
                </select>
                
                {* Warning when no statuses are available *}
                <div id="noStatusAvailable" class="mv-status-warning" style="display: none;">
                    <small class="text-warning">
                        <i class="material-icons" style="font-size: 16px; vertical-align: middle;">warning</i>
                        {l s='Aucun changement de statut disponible pour cette ligne de commande.' mod='multivendor'}
                    </small>
                </div>
                
                {* Info message about available transitions *}
                <div id="statusInfo" class="mv-status-info" style="display: none;">
                    <small class="text-muted">
                        <i class="material-icons" style="font-size: 14px; vertical-align: middle;">info</i>
                        <span id="statusInfoText"></span>
                    </small>
                </div>
            </div>

            <!-- Comment textarea -->
            <div class="mv-form-group">
                <label for="statusComment" class="mv-form-label">{l s='Commentaire' mod='multivendor'}</label>
                <textarea id="statusComment" 
                          class="mv-form-control" 
                          placeholder="{l s='Ajoutez un commentaire sur ce changement de statut...' mod='multivendor'}"
                          rows="4"></textarea>
            </div>
        </div>
        <div class="mv-status-comment-footer">
            <button type="button" class="mv-btn mv-btn-secondary" onclick="closeStatusCommentModal()">
                {l s='Annuler' mod='multivendor'}
            </button>
            <button type="button" class="mv-btn mv-btn-primary" id="submitStatusComment" onclick="submitStatusWithComment()">
                {l s='Mettre à jour le statut' mod='multivendor'}
            </button>
        </div>
    </div>
</div>

<script>
window.mvChangeableInfo = {json_encode($changeable_info)};
window.mvAllowedTransitions = {json_encode($allowed_status_transitions)};
window.mvStatusColors = {json_encode($status_colors)};
window.mvVendorStatuses = {json_encode($vendor_statuses)};

console.log('Changeable Info:', window.mvChangeableInfo);
console.log('Allowed Transitions:', window.mvAllowedTransitions);
console.log('Status Colors:', window.mvStatusColors);

const changeableTranslations = {
    noStatusAvailable: "{l s='Aucun changement de statut disponible' mod='multivendor'}",
    statusNotChangeable: "{l s='Ce statut ne peut pas être modifié' mod='multivendor'}",
    selectNewStatus: "{l s='Veuillez sélectionner un nouveau statut' mod='multivendor'}",
    availableTransitions: "{l s='Transitions disponibles pour cette ligne' mod='multivendor'}",
    noTransitionsAvailable: "{l s='Aucune transition disponible depuis le statut actuel' mod='multivendor'}"
};
</script>
{/block}