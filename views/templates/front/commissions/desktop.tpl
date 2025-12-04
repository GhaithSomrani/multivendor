{*
* Desktop Commissions Template  
* views/templates/front/commissions/desktop.tpl
*}

{* Commission Transactions Card *}

{if $view == 'transactions'}
    <div class="mv-card">
        <div class="mv-card-header">
            <h3 class="mv-card-title">{l s='Détails des commandes' mod='multivendor'}</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                {if $total_order_details > 0}
                    <div class="mv-count-badge">
                        <span>{$total_order_details}
                            {if $total_order_details > 1}{l s='commandes' mod='multivendor'}{else}{l s='commande' mod='multivendor'}{/if}</span>
                    </div>
                    <button type="button" class="mv-btn mv-btn-primary" id="export-csv-btn" style="padding: 5px 15px; font-size: 14px;">
                        📥 {l s='Exporter CSV' mod='multivendor'}
                    </button>
                {/if}
            </div>
        </div>
        <div class="mv-card-body">
            {if $order_details || $filter}
                <div class="mv-table-container">
                    <table class="mv-table">
                        <thead>

                            <tr>
                                <th>
                                    <a href="{$link->getModuleLink('multivendor', 'commissions', array_merge($filter, ['order_by' => 'id_order', 'order_way' => (isset($filter.order_by) && $filter.order_by == 'id_order' && isset($filter.order_way) && $filter.order_way == 'ASC') ? 'DESC' : 'ASC']))}"
                                        class="mv-sort-header">
                                        {l s='Commande' mod='multivendor'}
                                        {if isset($filter.order_by) && $filter.order_by == 'id_order'}
                                            {if isset($filter.order_way) && $filter.order_way == 'ASC'}▲{else}▼{/if}
                                        {else}
                                            <span class="mv-sort-both">⬍</span>
                                        {/if}
                                    </a>
                                </th>
                                <th>{l s='Image' mod='multivendor'}</th>
                                <th>
                                    <a href="{$link->getModuleLink('multivendor', 'commissions', array_merge($filter, ['order_by' => 'product_name', 'order_way' => (isset($filter.order_by) && $filter.order_by == 'product_name' && isset($filter.order_way) && $filter.order_way == 'ASC') ? 'DESC' : 'ASC']))}"
                                        class="mv-sort-header">
                                        {l s='Produit' mod='multivendor'}
                                        {if isset($filter.order_by) && $filter.order_by == 'product_name'}
                                            {if isset($filter.order_way) && $filter.order_way == 'ASC'}▲{else}▼{/if}
                                        {else}
                                            <span class="mv-sort-both">⬍</span>
                                        {/if}
                                    </a>
                                </th>
                                <th>
                                    <a href="{$link->getModuleLink('multivendor', 'commissions', array_merge($filter, ['order_by' => 'vendor_amount', 'order_way' => (isset($filter.order_by) && $filter.order_by == 'vendor_amount' && isset($filter.order_way) && $filter.order_way == 'ASC') ? 'DESC' : 'ASC']))}"
                                        class="mv-sort-header">
                                        {l s='Montant à payer' mod='multivendor'}
                                        {if isset($filter.order_by) && $filter.order_by == 'vendor_amount'}
                                            {if isset($filter.order_way) && $filter.order_way == 'ASC'}▲{else}▼{/if}
                                        {else}
                                            <span class="mv-sort-both">⬍</span>
                                        {/if}
                                    </a>
                                </th>
                                <th>
                                    <a href="{$link->getModuleLink('multivendor', 'commissions', array_merge($filter, ['order_by' => 'line_status', 'order_way' => (isset($filter.order_by) && $filter.order_by == 'line_status' && isset($filter.order_way) && $filter.order_way == 'ASC') ? 'DESC' : 'ASC']))}"
                                        class="mv-sort-header">
                                        {l s='Statut ligne' mod='multivendor'}
                                        {if isset($filter.order_by) && $filter.order_by == 'line_status'}
                                            {if isset($filter.order_way) && $filter.order_way == 'ASC'}▲{else}▼{/if}
                                        {else}
                                            <span class="mv-sort-both">⬍</span>
                                        {/if}
                                    </a>
                                </th>
                                <th>
                                    <a href="{$link->getModuleLink('multivendor', 'commissions', array_merge($filter, ['order_by' => 'order_date', 'order_way' => (isset($filter.order_by) && $filter.order_by == 'order_date' && isset($filter.order_way) && $filter.order_way == 'ASC') ? 'DESC' : 'ASC']))}"
                                        class="mv-sort-header">
                                        {l s='Date Commande' mod='multivendor'}
                                        {if isset($filter.order_by) && $filter.order_by == 'order_date'}
                                            {if isset($filter.order_way) && $filter.order_way == 'ASC'}▲{else}▼{/if}
                                        {else}
                                            <span class="mv-sort-both">⬍</span>
                                        {/if}
                                    </a>
                                </th>
                                <th></th>
                            </tr>
                            <tr class="mv-filter-row">
                                <th>
                                    <input type="text" class="mv-filter-input" name="order_id" placeholder="N° Commande"
                                        value="{$filter.order_id|default:''}">
                                </th>
                                <th></th>
                                <th>
                                    <input type="text" class="mv-filter-input" name="product_name" placeholder="Produit"
                                        value="{$filter.product_name|default:''}">
                                </th>
                                <th class="mv-filter-range">
                                    <input type="text" class="mv-filter-input" name="amount_min" placeholder="Min"
                                        value="{$filter.amount_min|default:''}">
                                    <input type="text" class="mv-filter-input" name="amount_max" placeholder="Max"
                                        value="{$filter.amount_max|default:''}">
                                </th>
                                <th>
                                    <select class="mv-filter-input" name="line_status">
                                        <option value="">{l s='Tous' mod='multivendor'}</option>
                                        {foreach from=$order_line_statuses item=status}
                                            <option value="{$status.id_order_line_status_type}"
                                                {if isset($filter.line_status) && $filter.line_status == $status.id_order_line_status_type}selected{/if}>
                                                {$status.name}
                                            </option>
                                        {/foreach}
                                    </select>
                                </th>
                                <th>
                                    <input type="text" class="mv-filter-input" name="datefilter" placeholder="Période"
                                        value="{$filter.datefilter|default:''}">
                                </th>
                                <th class="mv-filter-range">
                                    <button type="button" class="mv-status-btn mv-btn-filter" id="apply-filter">🔍︎</button>
                                    <button type="button" class="mv-status-btn mv-btn-reset" id="reset-filter">✖</button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {if $order_details}
                                {foreach from=$order_details item=detail}
                                    {assign var="rm" value=Manifest::getManifestByOrderDetailAndType($detail.id_order_detail, 1)}
                                    {assign var="rt" value=Manifest::getManifestByOrderDetailAndType($detail.id_order_detail, 2)}
                                    <tr onclick="toggledown(this)">
                                        <td>
                                            <a href="" class="mv-link">{$detail.id_order}</a>
                                            <br><small>{$detail.id_order_detail}</small>
                                        </td>
                                        <td>
                                            {assign var="product_image" value=OrderHelper::getProductImageLink($detail.product_id, $detail.product_attribute_id)}
                                            {assign var="large_image" value=OrderHelper::getProductImageLink($detail.product_id, $detail.product_attribute_id, 'large_default')}
                                            <div class="zoom-container">
                                                <img src="{$product_image}" data-zoom="{$large_image}"
                                                    alt="{$detail.product_name|escape:'html':'UTF-8'}"
                                                    class="zoomable-image mv-product-image">
                                            </div>
                                        </td>
                                        <td>{$detail.product_name}
                                            {if $detail.product_quantity > 0}(x{$detail.product_quantity}){/if}</td>
                                        <td>{$detail.vendor_amount|number_format:3} TND</td>
                                        <td>
                                            <span class="mv-status-badge" style="background-color: {$detail.status_color};">
                                                {$detail.line_status|capitalize}
                                            </span>
                                        </td>
                                        <td>{$detail.order_date|date_format:'%Y-%m-%d'}</td>
                                        <td>
                                            <button class="mv-collapse-btn" onclick="toggleCollapse(this)">+</button>
                                        </td>
                                    </tr>
                                    <tr class="mv-row-details" data-detail-id="{$detail.id_order_detail}">
                                        <td colspan="7" style="padding: 0; border: none;">


                                            {assign var="commissions" value=[]}
                                            {assign var="refunds"     value=[]}

                                            {foreach from=$detail.transactions item=t}
                                                {if $t.transaction_type == 'commission'}
                                                    {append var="commissions" value=$t}

                                                {else}
                                                    {append var="refunds" value=$t}

                                                {/if}
                                            {/foreach}
                                            <div class="mv-details-split" style="grid-template-columns:1fr 1fr;">
                                                {* ----- LEFT: commissions ----- *}
                                                <div class="mv-transactions-section">
                                                    {if $commissions}
                                                        {foreach from=$commissions item=t}
                                                            <div class="mv-transaction-item"
                                                                style="padding:8px;margin-bottom:8px;background:#ecfdf5;border-radius:4px;">
                                                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                                                    <div>
                                                                        <span class="mv-action-type mv-action-commission"
                                                                            style="font-weight:600">Commission</span>
                                                                        <span
                                                                            style="margin-left:10px;font-weight:700">{$t.vendor_amount|number_format:3}
                                                                            TND</span>
                                                                    </div>
                                                                    <div>
                                                                        <span
                                                                            style="color:{if $t.status=='paid'}#10b981{else}#f59e0b{/if};font-weight:700">
                                                                            {if $t.status=='paid'}Payé{else}En cours{/if}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div style="font-size:.85em;color:#666;margin-top:4px">
                                                                    <span> <small> Date de valitaion :</small>
                                                                        {$t.transaction_date|date_format:'%Y-%m-%d %H:%M'}</span>
                                                                    {if $t.payment_reference}
                                                                        <span style="margin-left:15px">Paiement:
                                                                            <strong>{$t.payment_reference}</strong></span>
                                                                        <span style="margin-left:10px">(
                                                                            <small> Date de Paiement :</small>
                                                                            {$t.payment_date|date_format:'%Y-%m-%d'})</span>
                                                                    {/if}
                                                                </div>
                                                            </div>
                                                        {/foreach}

                                                    {/if}
                                                </div>

                                                {* ----- RIGHT: refunds (non-commission) shown as commission ----- *}
                                                <div class="mv-transactions-section">
                                                    {if $refunds}
                                                        {foreach from=$refunds item=t}
                                                            <div class="mv-transaction-item"
                                                                style="padding:8px;margin-bottom:8px;background:#fef2f2;border-radius:4px;">
                                                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                                                    <div>
                                                                        <span class="mv-action-type mv-action-{$t.transaction_type}"
                                                                            style="font-weight:600">Remboursement</span>
                                                                        <span
                                                                            style="margin-left:10px;font-weight:700">{$t.vendor_amount|number_format:3}
                                                                            TND</span>
                                                                    </div>
                                                                    <div>
                                                                        <span
                                                                            style="color:{if $t.status=='paid'}#10b981{else}#f59e0b{/if};font-weight:700">
                                                                            {if $t.status=='paid'}Remboursé{else}En cours{/if}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div style="font-size:.85em;color:#666;margin-top:4px">
                                                                    <span>Date: {$t.transaction_date|date_format:'%Y-%m-%d'}</span>
                                                                    {if $t.payment_reference}
                                                                        <span style="margin-left:15px">Paiement:
                                                                            <strong>{$t.payment_reference}</strong></span>
                                                                        <span
                                                                            style="margin-left:10px">({$t.payment_date|date_format:'%Y-%m-%d'})</span>
                                                                    {/if}
                                                                </div>
                                                            </div>
                                                        {/foreach}

                                                    {/if}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                {/foreach}
                            {else}
                                <tr>
                                    <td colspan="7">
                                        <p class="mv-empty-state">{l s='Aucun détail de commande trouvé.' mod='multivendor'}</p>
                                    </td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>

                {if $pages_nb > 1}
                    <nav class="mv-pagination">
                        <ul class="mv-pagination-list">
                            {if $current_page > 1}
                                <li class="mv-pagination-item">
                                    <a class="mv-pagination-link"
                                        href="{$link->getModuleLink('multivendor', 'commissions', array_merge(['page' => 1], $filter))}">
                                        <span>«</span>
                                    </a>
                                </li>
                                <li class="mv-pagination-item">
                                    <a class="mv-pagination-link"
                                        href="{$link->getModuleLink('multivendor', 'commissions', array_merge(['page' => $current_page-1], $filter))}">
                                        <span>‹</span>
                                    </a>
                                </li>
                            {/if}

                            {assign var=p_start value=max(1, $current_page-2)}
                            {assign var=p_end value=min($pages_nb, $current_page+2)}

                            {for $p=$p_start to $p_end}
                                <li class="mv-pagination-item {if $p == $current_page}mv-pagination-active{/if}">
                                    <a class="mv-pagination-link"
                                        href="{$link->getModuleLink('multivendor', 'commissions', array_merge(['page' => $p], $filter))}">{$p}</a>
                                </li>
                            {/for}

                            {if $current_page < $pages_nb}
                                <li class="mv-pagination-item">
                                    <a class="mv-pagination-link"
                                        href="{$link->getModuleLink('multivendor', 'commissions', array_merge(['page' => $current_page+1], $filter))}">
                                        <span>›</span>
                                    </a>
                                </li>
                                <li class="mv-pagination-item">
                                    <a class="mv-pagination-link"
                                        href="{$link->getModuleLink('multivendor', 'commissions', array_merge(['page' => $pages_nb], $filter))}">
                                        <span>»</span>
                                    </a>
                                </li>
                            {/if}
                        </ul>
                    </nav>
                {/if}
            {else}
                <div class="mv-empty-state">{l s='Aucun détail de commande trouvé.' mod='multivendor'}</div>
            {/if}
        </div>
    </div>
{/if}




{* Update the script section to handle both date filters *}
{* Update the Apply filter button script to preserve page parameter *}
<script>
    function toggledown(element) {
        const btn = element.querySelector('.mv-collapse-btn');
        if (btn) toggleCollapse(btn);
    }

    function toggleCollapse(btn) {
        const row = btn.closest('tr');
        const detailsRow = row.nextElementSibling;
        const isOpen = detailsRow.classList.contains('open');

        if (isOpen) {
            detailsRow.classList.remove('open');
            btn.textContent = '+';
        } else {
            detailsRow.classList.add('open');
            btn.textContent = '-';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // CSV Export
        $('#export-csv-btn').on('click', function() {
            const params = new URLSearchParams(window.location.search);
            params.set('ajax', '1');
            params.set('action', 'exportCommissionsCSV');

            const baseUrl = '{$link->getModuleLink('multivendor', 'commissions')}';
            const separator = baseUrl.includes('?') ? '&' : '?';
            window.location.href = baseUrl + separator + params.toString();
        });

        // Date range pickers
        $('input[name="datefilter"]').daterangepicker({
            autoUpdateInput: false,
            locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
        });

        $('input[name="datefilter"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format(
                'YYYY-MM-DD'));
        });

        $('input[name="payment_datefilter"]').daterangepicker({
            autoUpdateInput: false,
            locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
        });

        $('input[name="payment_datefilter"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format(
                'YYYY-MM-DD'));
        });

        // Enable Enter key to apply filters
        $('.mv-filter-input').on('keypress', function(e) {
            if (e.which === 13 || e.keyCode === 13) {
                e.preventDefault();
                $('#apply-filter').click();
            }
        });

        // Apply filter - always reset to page 1
        $('#apply-filter').click(function() {
            const params = new URLSearchParams(window.location.search);

            // Reset page to 1
            params.set('page', '1');

            // Preserve summary filters (transaction_status, commission_action, transaction_type, line_status_not)
            const summaryFilters = ['transaction_status', 'commission_action', 'transaction_type', 'line_status_not'];
            const preservedSummaryFilters = {};
            summaryFilters.forEach(function(filter) {
                if (params.has(filter)) {
                    preservedSummaryFilters[filter] = params.get(filter);
                }
            });

            // Clear all parameters except preserved ones
            params.delete('order_id');
            params.delete('product_name');
            params.delete('amount_min');
            params.delete('amount_max');
            params.delete('line_status');
            params.delete('datefilter');

            // Add table filter values
            $('.mv-filter-input').each(function() {
                const name = $(this).attr('name');
                const value = $(this).val();
                if (value) {
                    params.set(name, value);
                } else {
                    params.delete(name);
                }
            });

            // Restore preserved summary filters
            Object.keys(preservedSummaryFilters).forEach(function(filter) {
                params.set(filter, preservedSummaryFilters[filter]);
            });

            const baseUrl = '{$link->getModuleLink('multivendor', 'commissions')}';
            const separator = baseUrl.includes('?') ? '&' : '?';
            window.location.href = baseUrl + separator + params.toString();
        });

        // Reset filter - clear ALL filters
        $('#reset-filter').click(function() {
            const baseUrl = '{$link->getModuleLink('multivendor', 'commissions')}';
            window.location.href = baseUrl;
        });

        // Drift zoom
        document.querySelectorAll('.zoomable-image').forEach(function(img) {
            new Drift(img, { inlineOffsetX: 200, zoomFactor: 6 });
        });
        $('input[name="payment_date_filter"]').daterangepicker({
            autoUpdateInput: false,
            locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
        });

        $('input[name="payment_date_filter"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format(
                'YYYY-MM-DD'));
        });

        // Enable Enter key to apply payment filters
        $('.payment-filter').on('keypress', function(e) {
            if (e.which === 13 || e.keyCode === 13) {
                e.preventDefault();
                $('#apply-payment-filter').click();
            }
        });

        // Apply payment filter
        $('#apply-payment-filter').click(function() {
            const params = new URLSearchParams();
            params.append('view', 'payments');
            params.append('payment_page', '1');

            $('.payment-filter').each(function() {
                if ($(this).val()) {
                    params.append($(this).attr('name'), $(this).val());
                }
            });

            const baseUrl = '{$link->getModuleLink('multivendor', 'commissions')}';
            const separator = baseUrl.includes('?') ? '&' : '?';
            window.location.href = baseUrl + separator + params.toString();
        });

        // Reset payment filter
        $('#reset-payment-filter').click(function() {
            window.location.href = '{$link->getModuleLink('multivendor', 'commissions', ['view' => 'payments'])}';
        });
    });

    function printPayment(id_payment, event) {
        event.stopPropagation();

        $.ajax({
            url: '{$link->getModuleLink('multivendor', 'commissions')}',
            type: 'POST',
            data: {
                ajax: true,
                action: 'printPayment',
                id_payment: id_payment
            },
            success: function(response) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(response);
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => printWindow.print(), 250);
            },
            error: function() {
                alert('Erreur lors du chargement');
            }
        });
    }
</script>