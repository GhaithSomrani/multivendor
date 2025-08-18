{*
* Vendor Orders Template with Mobile Detection
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
                {* Always show KPIs first *}
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
                            <h3 class="mv-stat-value">{$order_summary.total_revenue|number_format:2} TND</h3>
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

                {* Mobile/Desktop Detection for Content *}
                {if Context::getContext()->isMobile() == 1}
                    {* Load Mobile Template *}
                    {include file="module:multivendor/views/templates/front/orders/mobile.tpl"}
                {else}
                    {* Load Desktop Template *}
                    {include file="module:multivendor/views/templates/front/orders/desktop.tpl"}
                {/if}

            </main>
        </div>
    </div>

    {* Shared Modals *}
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

    <div class="mv-status-comment-modal" id="statusCommentModal">
        <div class="mv-status-comment-content">
            <div class="mv-status-comment-header">
                <h5 class="mv-status-comment-title">{l s='Mettre à jour le statut avec commentaire' mod='multivendor'}</h5>
                <button class="mv-status-comment-close" onclick="closeStatusCommentModal()">×</button>
            </div>
            <div class="mv-status-comment-body">
                <div class="mv-current-status">
                    <div class="mv-current-status-label">{l s='Statut actuel' mod='multivendor'}</div>
                    <span id="currentStatusBadge" class="mv-status-badge">
                        {l s='En attente' mod='multivendor'}
                    </span>
                </div>
                <div class="mv-form-group">
                    <label class="mv-form-label">{l s='Produit' mod='multivendor'}</label>
                    <div id="productInfo" style="font-weight: 500; color: #333;">
                        {l s='Nom du produit' mod='multivendor'}
                    </div>
                </div>
                <div class="mv-form-group">
                    <label for="newStatusSelect" class="mv-form-label">{l s='Nouveau statut' mod='multivendor'} *</label>
                    <select id="newStatusSelect" class="mv-form-control" required>
                        <option value="">{l s='Sélectionnez un nouveau statut...' mod='multivendor'}</option>
                    </select>
                    <div id="noStatusAvailable" class="mv-status-warning" style="display: none;">
                        <small class="text-warning">
                            <i class="material-icons" style="font-size: 16px; vertical-align: middle;">warning</i>
                            {l s='Aucun changement de statut disponible pour cette ligne de commande.' mod='multivendor'}
                        </small>
                    </div>
                    <div id="statusInfo" class="mv-status-info" style="display: none;">
                        <small class="text-muted">
                            <i class="material-icons" style="font-size: 14px; vertical-align: middle;">info</i>
                            <span id="statusInfoText"></span>
                        </small>
                    </div>
                </div>
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
        const bulkStatusChangeConfirmText = "{l s='Êtes-vous sûr de vouloir changer le statut des commandes sélectionnées ?' mod='multivendor'}";
        const bulkChangeComment = "{l s='Statut modifié via l\'action groupée' mod='multivendor'}";
        const processingText = "{l s='Traitement...' mod='multivendor'}";
        const applyText = "{l s='Appliquer' mod='multivendor'}";
        const selectedText = "{l s='sélectionné(s)' mod='multivendor'}";
        const successStatusText = "{l s='commandes mises à jour avec succès.' mod='multivendor'}";
        const errorStatusText = "{l s='commandes n\'ont pas pu être mises à jour.' mod='multivendor'}";

        window.mvChangeableInfo = {$changeable_info|json_encode nofilter};
        window.mvAllowedTransitions = {$allowed_status_transitions|json_encode nofilter};
        window.mvStatusColors = {$status_colors|json_encode nofilter};
        window.mvVendorStatuses = {$vendor_statuses|json_encode nofilter};

        const changeableTranslations = {
            noStatusAvailable: "{l s='Aucun changement de statut disponible' mod='multivendor'}",
            statusNotChangeable: "{l s='Ce statut ne peut pas être modifié' mod='multivendor'}",
            selectNewStatus: "{l s='Veuillez sélectionner un nouveau statut' mod='multivendor'}",
            availableTransitions: "{l s='Transitions disponibles pour cette ligne' mod='multivendor'}",
            noTransitionsAvailable: "{l s='Aucune transition disponible depuis le statut actuel' mod='multivendor'}"
        };
    </script>
{/block}