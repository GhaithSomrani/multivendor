{* Contenu du Manifeste Bureau - Mise en Page Traditionnelle *}
<div class="manifest-container">
    <div class="top-section">
        {* Panneau Gauche - Commandes Disponibles *}
        <div class="left-panel">
            <div class="panel-header">
                <button class="mv-btn mv-btn-primary"
                    id="selectAllBtn">{l s='Sélectionner TOUT' mod='multivendor'}</button>
                <input type="text" class="mv-form-control" placeholder="Scannez le code-barres MPN ici..."
                    autocomplete="off" />
            </div>
            <div class="order-list" id="availableOrders">
                <div class="loading">{l s='Chargement des commandes disponibles...' mod='multivendor'}</div>
            </div>
        </div>

        {* Panneau Droit - Commandes Sélectionnées *}
        <div class="right-panel">
            <div class="right-panel-header">
                <div class="button-row">
                    <button class="mv-btn mv-btn-secondary" id="cancelBtn">🚫 {l s='Annuler Les modification' mod='multivendor'}</button>
                    <button class="mv-btn mv-btn-info" id="justSaveBtn">💾 {l s='Sauvegarder en brouillon' mod='multivendor'}</button>
                    <button class="mv-btn mv-btn-success" id="saveBtn"
                        data-validation-name="">✅ {l s='Prêt pour ramassage' mod='multivendor'}</button>
                </div>
                <div>
                    <select id="addressSelect" class="mv-form-control">
                        {foreach from=$address_list item=address key=index}
                            <option value="{$address.id_address}" {if index == 1}selected {/if}> {$address.address}</option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <div class="order-list" id="selectedOrders">
                <div class="no-orders">{l s='Aucune commande sélectionnée' mod='multivendor'}</div>
            </div>
            <div class="total-section">
                <span>{l s='Total :' mod='multivendor'} <span id="totalAmount">0</span></span>
                <span>{l s='Quantité :' mod='multivendor'} <span id="totalQty">0</span></span>
            </div>
        </div>
    </div>

    {* Section Liste des Manifestes *}
    <div class="manifest-section">
        <div class="mv-card">
            <div class="mv-card-header">
                <h3 class="mv-card-title">{l s='Liste des Manifestes' mod='multivendor'}</h3>
                <div class="mv-count-badge">
                    <span id="manifestCount">0</span> {l s='manifestes' mod='multivendor'}
                </div>
            </div>
            <div class="mv-card-body">
                <div class="mv-table-container">
                    <table class="mv-table" id="manifestTable">
                        <thead>
                            <tr>
                                <th>{l s='Référence' mod='multivendor'}</th>
                                <th>{l s='Type' mod='multivendor'}</th>
                                <th>{l s='Date' mod='multivendor'}</th>
                                <th>{l s='Adresse' mod='multivendor'}</th>
                                <th>{l s='Articles' mod='multivendor'}</th>
                                <th>{l s='Quantité' mod='multivendor'}</th>
                                <th>{l s='Total' mod='multivendor'}</th>
                                <th>{l s='Statut' mod='multivendor'}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="manifestTableBody">
                            <tr>
                                <td colspan="9">
                                    <div class="mv-empty-state">{l s='Chargement des manifestes...' mod='multivendor'}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {* Pagination *}
                <nav class="mv-pagination" id="manifestPagination" style="display: none;">
                    <ul class="mv-pagination-list" id="manifestPaginationList">
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>