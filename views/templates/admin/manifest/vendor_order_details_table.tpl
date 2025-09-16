<div class="panel" id="vendor-order-details-panel">
    <div class="panel-heading">
        <i class="icon-list-ol"></i> {l s='Vendor Order Details' mod='multivendor'}
        <span class="badge" id="items-count">0 {l s='items' mod='multivendor'}</span>

        <!-- Per Page Selection -->
        <div class="pull-right">
            <label for="per-page-select">{l s='Lignes par page' mod='multivendor'}:</label>
            <select id="per-page-select" class="form-control input-sm" style="width: auto; display: inline-block;">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>
        </div>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped" id="vendor-order-details-table">
                <thead>
                    <tr>
                        <th class="center fixed-width-xs"><input type="checkbox" id="select-all-order-details" /></th>
                        <th class="fixed-width-xs center">{l s='ID' mod='multivendor'}</th>
                        <th>{l s='ID Order' mod='multivendor'}</th>
                        <th class="fixed-width-sm center">{l s='ID Détail' mod='multivendor'}</th>
                        <th>{l s='Vendeur' mod='multivendor'}</th>
                        <th>{l s='Nom' mod='multivendor'}</th>
                        <th class="center">{l s='Référence produit' mod='multivendor'}</th>
                        <th class="fixed-width-xs center">{l s='QTÉ' mod='multivendor'}</th>
                        <th>{l s='Montant vendeur' mod='multivendor'}</th>
                        <th>{l s='Statut de paiement' mod='multivendor'}</th>
                        <th>{l s='Statut de commande' mod='multivendor'}</th>
                        <th>{l s='Statut ligne de commande' mod='multivendor'}</th>
                        <th>{l s='Date de commande' mod='multivendor'}</th>
                    </tr>
                    <tr class="filters">
                        <th></th>
                        <th><input type="text" class="filter-input" name="id_vendor_order_detail" placeholder="ID"></th>
                        <th><input type="text" class="filter-input" name="order_id" placeholder="ID Order"></th>
                        <th><input type="text" class="filter-input" name="id_order_detail" placeholder="ID Détail"></th>
                        <th><input type="text" class="filter-input" name="vendor_name" placeholder="Vendeur"></th>
                        <th><input type="text" class="filter-input" name="product_name" placeholder="Nom"></th>
                        <th><input type="text" class="filter-input" name="reference" placeholder="Référence"></th>
                        <th><input type="text" class="filter-input" name="quantity" placeholder="QTÉ"></th>
                        <th><input type="text" class="filter-input" name="vendor_amount_min" placeholder="Min Montant">
                            <input type="text" class="filter-input" name="vendor_amount_max" placeholder="Max Montant">
                        </th>
                        <th>
                            <select class="filter-input" name="payment_status">
                                <option value="">{l s='Tous' mod='multivendor'}</option>
                                <option value="paid">{l s='Payé' mod='multivendor'}</option>
                                <option value="pending">{l s='En attente' mod='multivendor'}</option>
                                <option value="cancelled">{l s='Annulé' mod='multivendor'}</option>
                            </select>
                        </th>
                        <th>
                            <select class="filter-input" name="order_status">
                                <option value="">{l s='Tous' mod='multivendor'}</option>
                                {foreach $orderStatuses as $orderStatus}
                                    <option value="{$orderStatus.id_order_state}">{$orderStatus.name}</option>
                                {/foreach}
                            </select>
                        </th>
                        <th>
                            <select class="filter-input" name="status">
                                <option value="">{l s='Tous' mod='multivendor'}</option>
                                {foreach $statuses as $status}
                                    <option value="{$status.id_order_line_status_type}">{$status.name}</option>
                                {/foreach}
                            </select>
                        </th>
                        <th>
                            <input type="date" class="filter-input date-input form-control" name="date_from">
                            <input type="date" class="filter-input date-input form-control" name="date_to">
                        </th>
                    </tr>
                </thead>
                <tbody id="order-details-tbody">
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="row" id="pagination-container" style="display: none;">
            <div class="col-sm-6">
                <div class="dataTables_info">
                    {l s='Affichage de' mod='multivendor'} <span id="showing-from">0</span> {l s='à' mod='multivendor'}
                    <span id="showing-to">0</span> {l s='de' mod='multivendor'} <span id="total-records">0</span>
                    {l s='entrées' mod='multivendor'}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="dataTables_paginate paging_simple_numbers pull-right">
                    <ul class="pagination" id="pagination-list">
                        <!-- Pagination buttons will be inserted here -->
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {

        $('.order-detail-checkbox').off('change').on('change', function() {
            var selectedValues = [];
            $('.order-detail-checkbox:checked').each(function() {
                selectedValues.push($(this).val());
            });
            $('#mv_manifest_form input[name="selected_order_details"]').val(selectedValues.join(','));
        });
        var vendorId = {$vendor_id|intval};

        if (vendorId > 0) {
            loadVendorOrderDetailsBody(vendorId);
        }

        initializeOrderDetailsHandlers();

        $(document).on('change keyup', '.filter-input', function() {
            if (vendorId > 0) {
                loadVendorOrderDetailsBody(vendorId);
            }
        });
    });
</script>