<style>
.mv-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
}

.mv-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.mv-card-title {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
}

.mv-search-bar {
    display: flex;
    gap: 12px;
    align-items: center;
    flex: 1;
    max-width: 500px;
}

.mv-search-input {
    flex: 1;
    padding: 10px 16px;
    font-size: 14px;
    border-radius: 8px;
    border: 1px solid #ddd;
    background-color: white;
}

.mv-search-input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.mv-btn-reset {
    background-color: #ef4444;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: background-color 0.3s;
}

.mv-btn-reset:hover {
    background-color: #dc2626;
}

.mv-card-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

.mv-status-select {
    padding: 10px 16px;
    font-size: 14px;
    border-radius: 8px;
    border: 1px solid #ddd;
    background-color: white;
    cursor: pointer;
    min-width: 180px;
}

.mv-btn-export {
    background-color: #10b981;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.3s;
}

.mv-btn-export:hover {
    background-color: #059669;
}

.mv-card-body {
    padding: 24px;
}

.mv-table-container {
    overflow-x: auto;
}

.mv-table {
    width: 100%;
    border-collapse: collapse;
}

.mv-table thead {
    background-color: #f9fafb;
}

.mv-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
}

.mv-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s;
}

.mv-table tbody tr:hover {
    background-color: #f9fafb;
}

.mv-table td {
    padding: 14px 16px;
    font-size: 14px;
    color: #374151;
}

.mv-collapse-btn {
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    cursor: pointer;
    font-weight: bold;
    font-size: 18px;
    transition: all 0.2s;
}

.mv-collapse-btn:hover {
    background: #4f46e5;
    transform: scale(1.1);
}

.mv-product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
}

.mv-link {
    color: #6366f1;
    text-decoration: none;
    font-weight: 500;
}

.mv-link:hover {
    text-decoration: underline;
}

.mv-status-badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: white;
    display: inline-block;
}

/* Column Filters */
.mv-filter-row {
    background-color: #f9fafb;
}

.mv-filter-input {
    width: 100%;
    padding: 8px 10px;
    font-size: 13px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background-color: white;
}

.mv-filter-input::placeholder {
    color: #9ca3af;
    font-size: 12px;
}

/* Row Details (Collapsed Section) */
.mv-row-details {
    display: none;
}

.mv-row-details.open {
    display: table-row;
}

.mv-row-details td {
    background: #f8f9fa;
    border-top: none !important;
    padding: 0 !important;
}

/* === Split Layout for Row Details === */
.mv-details-split {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 24px;
    background: #f8f9fa;
    padding: 24px;
    border-top: 2px solid #e5e7eb;
}

/* LEFT SIDE */
.mv-details-left {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.mv-detail-item {
    background: #fff;
    padding: 12px 16px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.mv-detail-label {
    display: block;
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 4px;
}

.mv-detail-value {
    font-size: 14px;
    font-weight: 500;
    color: #1f2937;
}

.mv-detail-date {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
}

/* RIGHT SIDE (Timeline Scroll) */
.mv-details-right {
    background: #fff;
    padding: 16px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    max-height: 480px;
    overflow-y: auto;
}

.mv-history-title {
    margin-bottom: 10px;
    font-size: 15px;
    font-weight: 600;
    color: #1f2937;
}

/* Timeline styling */
.mv-history-list {
    position: relative;
    margin: 10px 0;
    padding-left: 30px;
    border-left: 3px solid #6366f1;
}

.mv-history-item {
    position: relative;
    margin-bottom: 20px;
}

.mv-history-item::before {
    content: '';
    position: absolute;
    left: -10px;
    top: 5px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background-color: #6366f1;
    box-shadow: 0 0 0 3px #e0e7ff;
}

.mv-history-date {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
}

.mv-history-change {
    font-size: 14px;
    color: #111827;
    background-color: #f9fafb;
    padding: 8px 12px;
    border-radius: 8px;
    display: inline-block;
}

.mv-history-old {
    color: #ef4444;
    font-weight: 600;
}

.mv-history-new {
    color: #10b981;
    font-weight: 600;
}

.mv-history-comment {
    background-color: #f3f4f6;
    padding: 6px 10px;
    border-radius: 6px;
    margin-top: 8px;
    font-size: 12px;
    white-space: pre-line;
    color: #374151;
}

.mv-history-user {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

/* Scrollbar style */
.mv-details-right::-webkit-scrollbar {
    width: 6px;
}

.mv-details-right::-webkit-scrollbar-thumb {
    background: #c7d2fe;
    border-radius: 3px;
}

.mv-details-right::-webkit-scrollbar-thumb:hover {
    background: #818cf8;
}

/* Pagination */
.mv-pagination {
    margin-top: 24px;
    display: flex;
    justify-content: center;
}

.mv-pagination-list {
    display: flex;
    list-style: none;
    gap: 8px;
}

.mv-pagination-link {
    display: block;
    padding: 8px 14px;
    color: #6366f1;
    text-decoration: none;
    border: 1px solid #ddd;
    border-radius: 6px;
    background-color: white;
    transition: all 0.2s;
    font-weight: 500;
}

.mv-pagination-link:hover {
    background-color: #f3f4f6;
    border-color: #6366f1;
}

.mv-pagination-active .mv-pagination-link {
    background-color: #6366f1;
    color: white;
    border-color: #6366f1;
}

.mv-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
    font-size: 16px;
}

/* Status Colors */
.status-pending {
    background-color: #f59e0b;
}

.status-available {
    background-color: #10b981;
}

.status-shipped {
    background-color: #3b82f6;
}

.status-delivered {
    background-color: #8b5cf6;
}

.status-cancelled {
    background-color: #ef4444;
}

/* Responsive */
@media (max-width: 1200px) {
    .mv-table {
        font-size: 13px;
    }

    .mv-table th,
    .mv-table td {
        padding: 10px 12px;
    }
}

@media (max-width: 768px) {
    body {
        padding: 10px;
    }

    .mv-card-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .mv-search-bar {
        width: 100%;
        max-width: 100%;
    }

    .mv-card-actions {
        width: 100%;
        flex-direction: column;
    }

    .mv-status-select,
    .mv-btn-export {
        width: 100%;
    }

    .mv-table-container {
        overflow-x: scroll;
    }

    .mv-table {
        min-width: 1000px;
    }

    .mv-details-split {
        grid-template-columns: 1fr;
    }

    .mv-details-right {
        max-height: 250px;
    }
}

.mv-filter-range {
    display: flex;
    align-items: center;
    gap: 0.2rem;
}

.mv-filter-range input {
    width: 100px;
}

</style>

<div class="mv-card">
    <div class="mv-card-header">
        <h3 class="mv-card-title">Lignes de commande</h3>
        <div class="mv-card-actions">
            <select id="export-type" class="mv-status-select">
                <option value="">Type d'export...</option>
                <option value="orders">📋 Commandes</option>
                <option value="payment">📊 Paiement</option>
                <option value="manifest-rm">📦 Bon de Ramassage</option>
                <option value="manifest-rt">🔄 Bon de Retour</option>
                <option value="commission">💰 Commission</option>
                <option value="refund">💳 Remboursement</option>
            </select>
            <button class="mv-btn-export" onclick="exportFiltered()">📥 Exporter</button>
        </div>
    </div>

    <div class="mv-card-body">
        {if $order_lines}
            <div class="mv-table-container">
                <table class="mv-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;"></th>
                            <th>Référence</th>
                            <th>Image</th>
                            <th>Produit</th>
                            <th>SKU</th>
                            <th>Marque</th>
                            <th>Qté</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                        <tr class="mv-filter-row">
                            <th></th>
                            <th><input type="text" id="filter-ref" class="mv-filter-input" placeholder="Ex: 254834"></th>
                            <th></th>
                            <th><input type="text" id="filter-product" class="mv-filter-input" placeholder="Ex: Tutti Délices"></th>
                            <th><input type="text" id="filter-sku" class="mv-filter-input" placeholder="Ex: COCO"></th>
                            <th><input type="text" id="filter-brand" class="mv-filter-input" placeholder="Ex: CHANEL"></th>
                            <th><input type="text" id="filter-qty" class="mv-filter-input" placeholder="Ex: 1"></th>
                            <th class="mv-filter-range">
                                <input type="text" id="filter-price-min" class="mv-filter-input" placeholder="DE: 15">
                                <input type="text" id="filter-price-max" class="mv-filter-input" placeholder="À: 30">
                            </th>
                            <th>
                                <select id="filter-status" class="mv-filter-input">
                                    <option value="">Tous</option>
                                    {foreach from=$status_types item=status}
                                        <option value="{$status.id_order_line_status_type}">{$status.name}</option>
                                    {/foreach}
                                </select>
                            </th>
                            <th><input type="text" id="filter-date" class="mv-filter-input" placeholder="2022-01-01 - 2022-01-31"></th>
                        </tr>
                    </thead>
                    <tbody id="orders-tbody">
                        {foreach from=$order_lines item=line}
                            <tr data-id="{$line.id_order_detail}" class="data-row">
                                <td><button class="mv-collapse-btn" onclick="toggleCollapse(this)">+</button></td>
                                <td data-ref="{$line.id_order} {$line.id_order_detail}">
                                    <a href="#" class="mv-link"><strong>{$line.id_order}</strong><br><small>{$line.id_order_detail}</small></a>
                                </td>
                                <td>
                                    {assign var="img" value=OrderHelper::getProductImageLink($line.product_id, $line.product_attribute_id)}
                                    <img src="{$img}" alt="{$line.product_name|escape}" class="mv-product-image">
                                </td>
                                <td data-product="{$line.product_name|escape}"><b>{$line.product_name}</b></td>
                                <td data-sku="{$line.product_reference|escape}">{$line.product_reference}</td>
                                <td data-brand="{$line.brand|escape}">{$line.brand}</td>
                                <td data-qty="{$line.product_quantity}">{$line.product_quantity}</td>
                                <td data-total="{$line.vendor_amount}">{$line.vendor_amount|number_format:3} TND</td>
                                <td data-status="{$line.line_status|lower}">
                                    <span class="mv-status-badge" style="background-color:{$line.status_color|default:'#777'}">
                                        {$line.line_status|capitalize}
                                    </span>
                                </td>
                                <td data-date="{$line.order_date|date_format:'%Y-%m-%d'}">{$line.order_date|date_format:'%Y-%m-%d'}</td>
                            </tr>
                            <tr class="mv-row-details">
                                <td colspan="10">
                                    <div class="mv-details-split">
                                        <div class="mv-details-left">
                                            <div class="mv-detail-item">
                                                <span class="mv-detail-label">Code barre</span>
                                                <span class="mv-detail-value">{$line.product_mpn|default:'-'}</span>
                                            </div>
                                            
                                            {assign var="rm" value=Manifest::getManifestByOrderDetailAndType($line.id_order_detail, 1)}
                                            <div class="mv-detail-item">
                                                <span class="mv-detail-label">Bon de Ramassage</span>
                                                <span class="mv-detail-value">
                                                    <span class="mv-detail-badge badge-warning">{if $rm}{$rm.reference}{else}-{/if}</span>
                                                </span>
                                                {if $rm}<div class="mv-detail-date">{$rm.date_add|date_format:'%Y-%m-%d %H:%M'}</div>{/if}
                                            </div>

                                            {assign var="rt" value=Manifest::getManifestByOrderDetailAndType($line.id_order_detail, 2)}
                                            <div class="mv-detail-item">
                                                <span class="mv-detail-label">Bon de Retour</span>
                                                <span class="mv-detail-value">
                                                    <span class="mv-detail-badge badge-warning">{if $rt}{$rt.reference}{else}-{/if}</span>
                                                </span>
                                                {if $rt}<div class="mv-detail-date">{$rt.date_add|date_format:'%Y-%m-%d %H:%M'}</div>{/if}
                                            </div>

                                            {assign var="pay" value=Vendorpayment::getByOrderDetailAndType($line.id_order_detail, 1)}
                                            <div class="mv-detail-item">
                                                <span class="mv-detail-label">Payment</span>
                                                <span class="mv-detail-value">
                                                    <span class="mv-detail-badge badge-info">{if $pay && $pay->id}{$pay->reference}{else}-{/if}</span>
                                                </span>
                                                {if $pay && $pay->id}<div class="mv-detail-date">{$pay->date_add|date_format:'%Y-%m-%d %H:%M'}</div>{/if}
                                            </div>

                                            {assign var="refund" value=Vendorpayment::getByOrderDetailAndType($line.id_order_detail, 2)}
                                            <div class="mv-detail-item">
                                                <span class="mv-detail-label">Payment de Retour</span>
                                                <span class="mv-detail-value">
                                                    <span class="mv-detail-badge badge-info">{if $refund && $refund->id}{$refund->reference}{else}-{/if}</span>
                                                </span>
                                                {if $refund && $refund->id}<div class="mv-detail-date">{$refund->date_add|date_format:'%Y-%m-%d %H:%M'}</div>{/if}
                                            </div>
                                        </div>

                                        <div class="mv-details-right">
                                            <h4 class="mv-history-title">Historique du statut</h4>
                                            <div class="mv-history-list" data-order-detail="{$line.id_order_detail}">
                                                {assign var="history" value=OrderLineStatusLog::getStatusHistory($line.id_order_detail)}
                                                {if $history}
                                                    {foreach from=$history item=h}
                                                        <div class="mv-history-item">
                                                            <div class="mv-history-date">{$h.date_add|date_format:'%Y-%m-%d %H:%M:%S'}</div>
                                                            <div class="mv-history-change">
                                                                <span class="mv-history-old">{$h.old_status}</span> →
                                                                <span class="mv-history-new">{$h.new_status}</span>
                                                            </div>
                                                            <div class="mv-history-user">by {$h.employee_name|default:'System'}</div>
                                                        </div>
                                                    {/foreach}
                                                {else}
                                                    <p>Aucun historique</p>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>

            {if $pages_nb > 1}
                <nav class="mv-pagination">
                    <ul class="mv-pagination-list">
                        {if $current_page > 1}
                            <li><a class="mv-pagination-link" href="{$pagination_url}&page=1">&lt;&lt;</a></li>
                            <li><a class="mv-pagination-link" href="{$pagination_url}&page={$current_page-1}">&lt;</a></li>
                        {/if}
                        
                        {assign var=start value=max(1, $current_page-2)}
                        {assign var=end value=min($pages_nb, $current_page+2)}
                        {for $i=$start to $end}
                            <li {if $i==$current_page}class="mv-pagination-active"{/if}>
                                <a class="mv-pagination-link" href="{$pagination_url}&page={$i}">{$i}</a>
                            </li>
                        {/for}

                        {if $current_page < $pages_nb}
                            <li><a class="mv-pagination-link" href="{$pagination_url}&page={$current_page+1}">&gt;</a></li>
                            <li><a class="mv-pagination-link" href="{$pagination_url}&page={$pages_nb}">&gt;&gt;</a></li>
                        {/if}
                    </ul>
                </nav>
            {/if}
        {else}
            <p class="mv-empty-state">Aucune ligne de commande trouvée.</p>
        {/if}
    </div>
</div>

<script>
function toggleCollapse(btn) {
    const row = btn.closest('tr');
    const details = row.nextElementSibling;
    const isOpen = btn.textContent === '-';
    btn.textContent = isOpen ? '+' : '-';
    details.style.display = isOpen ? 'none' : 'table-row';
}

$(function() {
    $('input[name="datefilter"], #filter-date').daterangepicker({
        autoUpdateInput: false,
        locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
    }).on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        applyFilters();
    }).on('cancel.daterangepicker', function() {
        $(this).val('');
        applyFilters();
    });

    $('.mv-filter-input, #filter-status').on('input change', applyFilters);
});

function applyFilters() {
    const filters = {
        reference: $('#filter-ref').val(),
        product_name: $('#filter-product').val(),
        sku: $('#filter-sku').val(),
        brand: $('#filter-brand').val(),
        quantity: $('#filter-qty').val(),
        price_min: $('#filter-price-min').val(),
        price_max: $('#filter-price-max').val(),
        status: $('#filter-status').val(),
        date: $('#filter-date').val()
    };

    $('#orders-tbody .data-row').each(function() {
        let show = true;
        const $row = $(this);
        
        if (filters.reference && !$row.find('[data-ref]').text().toLowerCase().includes(filters.reference.toLowerCase())) show = false;
        if (filters.product_name && !$row.find('[data-product]').text().toLowerCase().includes(filters.product_name.toLowerCase())) show = false;
        if (filters.sku && !$row.find('[data-sku]').text().toLowerCase().includes(filters.sku.toLowerCase())) show = false;
        if (filters.brand && !$row.find('[data-brand]').text().toLowerCase().includes(filters.brand.toLowerCase())) show = false;
        if (filters.quantity && $row.find('[data-qty]').text() != filters.quantity) show = false;
        if (filters.status && !$row.find('[data-status]').text().toLowerCase().includes(filters.status.toLowerCase())) show = false;
        
        $row.toggle(show);
        $row.next('.mv-row-details').toggle(show);
    });
}
</script>