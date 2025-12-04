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
                            <tr class="mv-filter-row">
                                <th>
                                    <input type="text" class="mv-filter-input manifest-filter"
                                        id="filter-reference" placeholder="Référence">
                                </th>
                                <th>
                                    <select class="mv-filter-input manifest-filter" id="filter-type">
                                        <option value="">{l s='Tous' mod='multivendor'}</option>
                                        <option value="1">{l s='Ramassage' mod='multivendor'}</option>
                                        <option value="2">{l s='Retours' mod='multivendor'}</option>
                                    </select>
                                </th>
                                <th>
                                    <input type="text" class="mv-filter-input manifest-filter"
                                        id="filter-date" name="manifest_date_filter" placeholder="Période">
                                </th>
                                <th>
                                    <input type="text" class="mv-filter-input manifest-filter"
                                        id="filter-address" placeholder="Adresse">
                                </th>
                                <th class="mv-filter-range">
                                    <input type="text" class="mv-filter-input manifest-filter"
                                        id="filter-items-min" placeholder="Min">
                                    <input type="text" class="mv-filter-input manifest-filter"
                                        id="filter-items-max" placeholder="Max">
                                </th>
                                <th class="mv-filter-range">
                                    <input type="text" class="mv-filter-input manifest-filter"
                                        id="filter-qty-min" placeholder="Min">
                                    <input type="text" class="mv-filter-input manifest-filter"
                                        id="filter-qty-max" placeholder="Max">
                                </th>
                                <th class="mv-filter-range">
                                    <input type="text" class="mv-filter-input manifest-filter"
                                        id="filter-total-min" placeholder="Min">
                                    <input type="text" class="mv-filter-input manifest-filter"
                                        id="filter-total-max" placeholder="Max">
                                </th>
                                <th>
                                    <select class="mv-filter-input manifest-filter" id="filter-status">
                                        <option value="">{l s='Tous' mod='multivendor'}</option>
                                    </select>
                                </th>
                                <th class="mv-filter-range" style="justify-content: right;">
                                    <button type="button" class="mv-status-btn mv-btn-filter"
                                        id="apply-manifest-filter">🔍︎</button>
                                    <button type="button" class="mv-status-btn mv-btn-reset"
                                        id="reset-manifest-filter">✖</button>
                                </th>
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

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script>
    $(document).ready(function() {
        // Date range picker initialization
        $('input[name="manifest_date_filter"]').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Effacer',
                applyLabel: 'Appliquer',
                format: 'DD/MM/YYYY'
            }
        });

        $('input[name="manifest_date_filter"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        });

        $('input[name="manifest_date_filter"]').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });
    });
</script>