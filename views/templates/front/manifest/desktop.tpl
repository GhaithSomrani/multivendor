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
                    <button class="mv-btn mv-btn-secondary" id="cancelBtn">{l s='Annuler' mod='multivendor'}</button>
                    <button class="mv-btn mv-btn-success" id="saveBtn">{l s='Valider' mod='multivendor'}</button>
                    {* <button class="mv-btn mv-btn-primary" id="printBtn">{l s='Imprimer' mod='multivendor'}</button> *}
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
        <table class="manifest-table" id="manifestTable">

            <tbody id="manifestTableBody">

            </tbody>
        </table>
    </div>
</div>