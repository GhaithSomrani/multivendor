{* Contenu du Manifeste Bureau - Mise en Page Traditionnelle *}
<div class="manifest-container">
    <div class="top-section">
        {* Panneau Gauche - Commandes Disponibles *}
        <div class="left-panel">
            <div class="panel-header">
                <button class="btn btn-primary" id="selectAllBtn">{l s='Sélectionner TOUT' mod='multivendor'}</button>
                {* <div class="filter-icon" onclick="toggleFilter()"></div> *}
            </div>
            <div class="order-list" id="availableOrders">
                <div class="loading">{l s='Chargement des commandes disponibles...' mod='multivendor'}</div>
            </div>
        </div>

        {* Panneau Droit - Commandes Sélectionnées *}
        <div class="right-panel">
            <div class="right-panel-header">
                <button class="btn btn-secondary" id="cancelBtn">{l s='Annuler' mod='multivendor'}</button>
                <button class="btn btn-success" id="saveBtn">{l s='Enregistrer' mod='multivendor'}</button>
                <button class="btn btn-primary" id="printBtn">{l s='Imprimer' mod='multivendor'}</button>
            </div>
            <div class="order-list" id="selectedOrders">
                <div class="no-orders">{l s='Aucune commande sélectionnée' mod='multivendor'}</div>
            </div>
            <div class="total-section">
                <span>{l s='Total :' mod='multivendor'} </span><span id="totalAmount">0</span>
            </div>
        </div>
    </div>

    {* Section Liste des Manifestes *}
    <div class="manifest-section">
        <div class="manifest-title">{l s='Liste des Manifestes' mod='multivendor'}</div>
        <table class="manifest-table" id="manifestTable">
            <thead>
                <tr>
                    <th>{l s='Référence' mod='multivendor'}</th>
                    <th>{l s='Adresse' mod='multivendor'}</th>
                    <th>{l s='Date' mod='multivendor'}</th>
                    <th>{l s='Articles' mod='multivendor'}</th>
                    <th>{l s='QTÉ' mod='multivendor'}</th>
                    <th>{l s='Total' mod='multivendor'}</th>
                    <th>{l s='Statut' mod='multivendor'}</th>
                    <th>{l s='Action' mod='multivendor'}</th>
                </tr>
            </thead>
            <tbody id="manifestTableBody">
                <tr>
                    <td colspan="8" class="loading">{l s='Chargement des manifestes...' mod='multivendor'}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>