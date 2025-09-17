{*
* Modèle de Manifeste Vendeur avec Détection Mobile
*}

{extends file='page.tpl'}

{block name='page_title'}
    {l s='Gérer les Manifestes' mod='multivendor'}
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
                            <a class="mv-nav-link" href="{$vendor_orders_url}">
                                <i class="mv-icon">🛒</i>
                                <span>{l s='Commandes' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link mv-nav-link-active" href="{$vendor_manifest_url}">
                                <i class="mv-icon">📋</i>
                                <span>{l s='Manifestes' mod='multivendor'}</span>
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
                {* Toujours afficher les KPI en premier *}
                <div class="mv-stats-grid">
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Total des Manifestes' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value" id="totalManifests">0</h3>
                            <p class="mv-stat-description">{l s='Depuis toujours' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Manifestes Brouillons' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value" id="draftManifests">0</h3>
                            <p class="mv-stat-description">{l s='Prêt à traiter' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Articles en Cours' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value" id="itemsInProgress">0</h3>
                            <p class="mv-stat-description">{l s='En cours de traitement' mod='multivendor'}</p>
                        </div>
                    </div>
                </div>

                {* Détection Mobile/Bureau pour le Contenu *}
                {if Context::getContext()->isMobile() == 1}
                    {* Charger le Modèle Mobile *}
                    {include file="module:multivendor/views/templates/front/manifest/mobile.tpl"}
                {else}
                    {* Charger le Modèle Bureau *}
                    {include file="module:multivendor/views/templates/front/manifest/desktop.tpl"}
                {/if}
            </main>
        </div>
    </div>

    {* Modales Partagées *}
    <div class="mv-modal" id="addressModal">
        <div class="mv-modal-backdrop" onclick="closeAddressModal()"></div>
        <div class="mv-modal-content">
            <div class="mv-modal-header">
                <h5 class="mv-modal-title">{l s='Sélectionner l\'Adresse' mod='multivendor'}</h5>
                <button class="mv-modal-close" onclick="closeAddressModal()">×</button>
            </div>
            <div class="mv-modal-body">
                <div class="mv-form-group">
                    <label for="addressSelect">{l s='Sélectionner l\'Adresse :' mod='multivendor'}</label>
                    <select id="addressSelect" class="mv-form-control">
                        <option value="">{l s='-- Sélectionner une adresse --' mod='multivendor'}</option>
                        {foreach from=$address_list item=address}
                            <option value="{$address.id_address}"> {$address.address}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="mv-modal-footer">
                <button class="mv-btn mv-btn-secondary"
                    onclick="closeAddressModal()">{l s='Annuler' mod='multivendor'}</button>
                <button class="mv-btn mv-btn-primary" id="confirmAddressBtn">{l s='Confirmer' mod='multivendor'}</button>
            </div>
        </div>
    </div>

    <div class="mv-modal" id="manifestModal">
        <div class="mv-modal-backdrop" onclick="closeModal()"></div>
        <div class="mv-modal-content">
            <div class="mv-modal-header">
                <h5 class="mv-modal-title" id="modalTitle">{l s='Détails du Manifeste' mod='multivendor'}</h5>
                <button class="mv-modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="mv-modal-body" id="modalBody">
                {* Le contenu de la modale sera rempli par JavaScript *}
            </div>
        </div>
    </div>

    <script>
        // Passer les variables PHP à JavaScript
        window.manifestConfig = {
            ajaxUrl: '{$manifest_ajax_url}',
            vendorId: {$vendor_id},
            isMobile: {if Context::getContext()->isMobile()}true{else}false{/if},
            translations: {
                loading: '{l s="Chargement..." mod="multivendor"}',
                noItemsSelected: '{l s="Aucun article sélectionné" mod="multivendor"}',
                manifestSaved: '{l s="Manifeste enregistré avec succès" mod="multivendor"}',
                manifestDeleted: '{l s="Manifeste supprimé avec succès" mod="multivendor"}',
                confirmDelete: '{l s="Êtes-vous sûr de vouloir supprimer ce manifeste ?" mod="multivendor"}',
                selectAddress: '{l s="Veuillez sélectionner une adresse" mod="multivendor"}',
                noOrders: '{l s="Aucune commande disponible" mod="multivendor"}',
                noManifests: '{l s="Aucun manifeste trouvé" mod="multivendor"}',
                manifestLoaded: '{l s="Manifeste chargé avec succès" mod="multivendor"}'
            }
        };
    </script>
{/block}