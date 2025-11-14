{*
* Desktop Commissions Template  
* views/templates/front/commissions/desktop.tpl
*}

{* Commission Transactions Card *}
<div class="mv-card">
    <div class="mv-card-header">
        <h3 class="mv-card-title">{l s='Transactions de commission' mod='multivendor'}</h3>
    </div>
    <div class="mv-card-body">
        {if $transactions || $filter}
            <div class="mv-table-container">
                <table class="mv-table">
                    <thead>
                        {* Update table headers - use isset to check if sorting is active *}
                        {* Update table headers - fix array_merge order *}
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
                                <a href="{$link->getModuleLink('multivendor', 'commissions', array_merge($filter, ['order_by' => 'commission_action', 'order_way' => (isset($filter.order_by) && $filter.order_by == 'commission_action' && isset($filter.order_way) && $filter.order_way == 'ASC') ? 'DESC' : 'ASC']))}"
                                    class="mv-sort-header">
                                    {l s='Type de transaction' mod='multivendor'}
                                    {if isset($filter.order_by) && $filter.order_by == 'commission_action'}
                                        {if isset($filter.order_way) && $filter.order_way == 'ASC'}▲{else}▼{/if}
                                    {else}
                                        <span class="mv-sort-both">⬍</span>
                                    {/if}
                                </a>
                            </th>
                            <th>
                                <a href="{$link->getModuleLink('multivendor', 'commissions', array_merge($filter, ['order_by' => 'transaction_status', 'order_way' => (isset($filter.order_by) && $filter.order_by == 'transaction_status' && isset($filter.order_way) && $filter.order_way == 'ASC') ? 'DESC' : 'ASC']))}"
                                    class="mv-sort-header">
                                    {l s='Statut transaction' mod='multivendor'}
                                    {if isset($filter.order_by) && $filter.order_by == 'transaction_status'}
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
                            <th>
                                <a href="{$link->getModuleLink('multivendor', 'commissions', array_merge($filter, ['order_by' => 'payment_date', 'order_way' => (isset($filter.order_by) && $filter.order_by == 'payment_date' && isset($filter.order_way) && $filter.order_way == 'ASC') ? 'DESC' : 'ASC']))}"
                                    class="mv-sort-header">
                                    {l s='Date du Payment' mod='multivendor'}
                                    {if isset($filter.order_by) && $filter.order_by == 'payment_date'}
                                        {if isset($filter.order_way) && $filter.order_way == 'ASC'}▲{else}▼{/if}
                                    {else}
                                        <span class="mv-sort-both">⬍</span>
                                    {/if}
                                </a>
                            </th>
                            <th></th>
                        </tr>
                        {* In desktop.tpl - Update the filter row *}
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
                                <select class="mv-filter-input" name="commission_action">
                                    <option value="">Tous</option>
                                    <option value="add"
                                        {if isset($filter.commission_action) && $filter.commission_action == 'commission'}selected{/if}>
                                        Addition
                                    </option>
                                    <option value="refund"
                                        {if  isset($filter.commission_action) && $filter.commission_action == 'refund'}selected{/if}>
                                        Remboursement</option>
                                </select>
                            </th>
                            <th>
                                <select class="mv-filter-input" name="transaction_status">
                                    <option value="">Tous</option>
                                    <option value="paid"
                                        {if  isset($filter.transaction_status) && $filter.transaction_status == 'paid'}selected{/if}>
                                        Payé
                                    </option>
                                    <option value="pending"
                                        {if  isset($filter.transaction_status) && $filter.transaction_status == 'pending'}selected{/if}>
                                        En
                                        cours</option>
                                </select>
                            </th>
                            <th>
                                <input type="text" class="mv-filter-input" name="reference" placeholder="SKU"
                                    value="{$filter.reference|default:''}">
                            </th>
                            <th>
                                <input type="text" class="mv-filter-input" name="datefilter" placeholder="Période"
                                    value="{$filter.datefilter|default:''}">
                            </th>
                            <th>
                                <input type="text" class="mv-filter-input" name="payment_datefilter" placeholder="Période"
                                    value="{$filter.payment_datefilter|default:''}">
                            </th>
                            <th class="mv-filter-range">
                                <button type="button" class="mv-status-btn mv-btn-filter" id="apply-filter">🔍︎</button>
                                <button type="button" class="mv-status-btn mv-btn-reset" id="reset-filter">✖</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $transactions}
                            {foreach from=$transactions item=transaction}
                                {assign var="rm" value=Manifest::getManifestByOrderDetailAndType($transaction.id_order_detail, 1)}
                                {assign var="rt" value=Manifest::getManifestByOrderDetailAndType($transaction.id_order_detail, 2)}
                                {assign var="pay" value=Vendorpayment::getByOrderDetailAndType($transaction.id_order_detail, 'commission')}
                                {assign var="refund" value=Vendorpayment::getByOrderDetailAndType($transaction.id_order_detail, 'refund')}
                                <tr onclick="toggledown(this)">
                                    <td>
                                        <a href="" class="mv-link">{$transaction.id_order}</a>
                                        <br><small>{$transaction.id_order_detail}</small>
                                    </td>
                                    <td>
                                        {assign var="product_image" value=OrderHelper::getProductImageLink($transaction.product_id, $transaction.product_attribute_id)}
                                        {assign var="large_image" value=OrderHelper::getProductImageLink($transaction.product_id, $transaction.product_attribute_id, 'large_default')}
                                        <div class="zoom-container">
                                            <img src="{$product_image}" data-zoom="{$large_image}"
                                                alt="{$transaction.product_name|escape:'html':'UTF-8'}"
                                                class="zoomable-image mv-product-image">
                                        </div>
                                    </td>
                                    <td>{$transaction.product_name}
                                        {if $transaction.product_quantity > 0}(x{$transaction.product_quantity}){/if}</td>
                                    <td>{$transaction.vendor_amount|number_format:3} TND</td>
                                    <td>
                                        <span class="mv-action-type mv-action-{$transaction.commission_action}">
                                            {if $transaction.commission_action != 'commission'}Remboursement{else}Addition{/if}
                                        </span>
                                    </td>
                                    <td>
                                        {if $transaction.transaction_status == 'paid'}
                                            <span style="color: #10b981; font-weight: 700;">Payé</span>
                                        {else}
                                            <span style="color: #f59e0b; font-weight: 700;">En cours</span>
                                        {/if}
                                    </td>
                                    <td>
                                        <span class="mv-status-badge" style="background-color: {$transaction.status_color};">
                                            {$transaction.line_status|capitalize}
                                        </span>
                                    </td>
                                    <td>{$transaction.order_date}</td>
                                    <td>
                                        {$transaction.payment_date|date_format:'%Y-%m-%d %H:%M'}

                                    </td>
                                    <td>
                                        <button class="mv-collapse-btn" onclick="toggleCollapse(this)">+</button>
                                    </td>
                                </tr>
                                <tr class="mv-row-details">
                                    <td colspan="10">
                                        <div class="mv-details-split">
                                            <div class="mv-details-left">
                                                <div style="display:flex; justify-content:space-between; width:100%;">
                                                    <div class="mv-detail-item" style="width: 33%">
                                                        <span class="mv-detail-label">Code barre</span>
                                                        <span class="mv-detail-value">{$transaction.product_mpn|default:'-'}</span>
                                                    </div>
                                                    <div class="mv-detail-item" style="width: 33%">
                                                        <span class="mv-detail-label">SKU</span>
                                                        <span class="mv-detail-value">{$transaction.product_reference}</span>
                                                    </div>
                                                    <div class="mv-detail-item" style="width: 33%">
                                                        <span class="mv-detail-label">Prix Public</span>
                                                        <span class="mv-detail-value">{$transaction.product_price}</span>
                                                    </div>
                                                </div>
                                                <div class="mv-detail-item">
                                                    <span class="mv-detail-label">Bon de Ramassage</span>
                                                    <span class="mv-detail-value">
                                                        <span
                                                            class="mv-detail-badge badge-warning">{if $rm}{$rm.reference}{else}-{/if}</span>
                                                        {if $rm}<div class="mv-detail-date">
                                                            {$rm.date_add|date_format:'%Y-%m-%d %H:%M'}</div>{/if}
                                                    </span>
                                                </div>
                                                <div class="mv-detail-item">
                                                    <span class="mv-detail-label">Bon de Retour</span>
                                                    <span class="mv-detail-value">
                                                        <span
                                                            class="mv-detail-badge badge-warning">{if $rt}{$rt.reference}{else}-{/if}</span>
                                                        {if $rt}<div class="mv-detail-date">
                                                            {$rt.date_add|date_format:'%Y-%m-%d %H:%M'}</div>{/if}
                                                    </span>
                                                </div>
                                                <div class="mv-detail-item">
                                                    <span class="mv-detail-label">Paiement</span>
                                                    <span class="mv-detail-value">
                                                        <span
                                                            class="mv-detail-badge badge-info">{if $pay && $pay->id}{$pay->reference}{else}-{/if}</span>
                                                        {if $pay && $pay->id}<div class="mv-detail-date">
                                                            {$pay->date_add|date_format:'%Y-%m-%d %H:%M'}</div>{/if}
                                                    </span>
                                                </div>
                                                <div class="mv-detail-item">
                                                    <span class="mv-detail-label">Paiement de Retour</span>
                                                    <span class="mv-detail-value">
                                                        <span
                                                            class="mv-detail-badge badge-info">{if $refund && $refund->id}{$refund->reference}{else}-{/if}</span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mv-details-right">
                                                <h4 class="mv-history-title">Historique du statut</h4>
                                                <div class="mv-history-list" data-order-detail="{$transaction.id_order_detail}">
                                                    {assign var="history" value=OrderLineStatusLog::getStatusHistory($transaction.id_order_detail)}
                                                    {if $history}
                                                        {foreach from=$history item=h}
                                                            <div class="mv-history-item">
                                                                <div class="mv-history-date">{$h.date_add|date_format:'%Y-%m-%d %H:%M:%S'}
                                                                </div>
                                                                <div class="mv-history-change">
                                                                    <span class="mv-history-old"
                                                                        style="color: {$h.old_status_color};">{$h.old_status_name}</span> →
                                                                    <span class="mv-history-new"
                                                                        style="color: {$h.new_status_color};">{$h.new_status_name}</span>
                                                                </div>
                                                                {if $h.comment}<div>
                                                                        <pre class="mv-history-change">{$h.comment}</pre>
                                                                </div>{/if}
                                                                <div class="mv-history-user">by {$h.changed_by|default:'System'}</div>
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
                        {else}
                            <tr>
                                <td colspan="10">
                                    <p class="mv-empty-state">{l s='Aucune transaction trouvée.' mod='multivendor'}</p>
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
            <div class="mv-empty-state">{l s='Aucune transaction de commission trouvée.' mod='multivendor'}</div>
        {/if}
    </div>
</div>


{* Payment History Card *}
<div class="mv-card">
    <div class="mv-card-header">
        <h3 class="mv-card-title">{l s='Historique des paiements' mod='multivendor'}</h3>
    </div>
    <div class="mv-card-body">
        {if $payments}
            <div class="mv-payments-list">
                {foreach from=$payments item=payment}
                    <div class="mv-payment-item">
                        <div class="mv-payment-header">
                            <div class="mv-payment-info">
                                <span class="mv-payment-date">{$payment.date_add|date_format:'%Y-%m-%d'}</span>
                                <span class="mv-payment-amount">{$payment.amount|number_format:3} TND</span>
                                <span class="mv-payment-method">{$payment.payment_method|capitalize}</span>
                                <span class="mv-payment-reference">{l s='Réf :' mod='multivendor'} {$payment.reference}</span>
                                <span class="mv-status-badge mv-status-{$payment.status}">
                                    {$payment.status|capitalize}
                                </span>
                            </div>
                            <button class="mv-btn-toggle"
                                onclick="togglePaymentDetails('payment-{$payment.id_vendor_payment}')">
                                <i class="mv-icon-chevron">▼</i>
                            </button>
                        </div>

                        <div class="mv-payment-details" id="payment-{$payment.id_vendor_payment}" style="display: none;">
                            {if $payment.order_details}
                                <table class="mv-table mv-payment-details-table">
                                    <thead>
                                        <tr>
                                            <th>{l s='Commande' mod='multivendor'}</th>
                                            <th>{l s='Produit' mod='multivendor'}</th>
                                            <th>{l s='SKU' mod='multivendor'}</th>
                                            <th>{l s='Qté' mod='multivendor'}</th>
                                            <th>{l s='Montant' mod='multivendor'}</th>
                                            <th>{l s='Date' mod='multivendor'}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach from=$payment.order_details item=detail}
                                            <tr>
                                                <td>
                                                    {if $detail.id_order}
                                                        <a href="" class="mv-link">{$detail.id_order}</a>
                                                    {else}
                                                        <span
                                                            class="text-muted">{l s='Commande ' mod='multivendor'}{$detail.id_order_detail}</span>
                                                    {/if}
                                                </td>
                                                <td>
                                                    {if $detail.product_name}
                                                        {$detail.product_name|truncate:40:'...'}
                                                    {else}
                                                        <span
                                                            class="text-muted">{l s='Détails du produit non disponibles' mod='multivendor'}</span>
                                                    {/if}
                                                </td>
                                                <td>{$detail.product_reference|default:'-'}</td>
                                                <td class="mv-text-center">{$detail.product_quantity|default:'-'}</td>
                                                <td>{$detail.vendor_amount|number_format:3} TND</td>
                                                <td>
                                                    {if $detail.order_date}
                                                        {$detail.order_date|date_format:'%Y-%m-%d'}
                                                    {else}
                                                        <span class="text-muted">-</span>
                                                    {/if}
                                                </td>
                                            </tr>
                                        {/foreach}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="mv-text-right"><strong>{l s='Total :' mod='multivendor'}</strong>
                                            </td>
                                            <td><strong>{$payment.amount|number_format:3} TND</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            {else}
                                <p class="mv-empty-state">
                                    {l s='Aucun détail de commande disponible pour ce paiement.' mod='multivendor'}</p>
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>
        {else}
            <div class="mv-empty-state">
                {l s='Aucun historique de paiement trouvé.' mod='multivendor'}
            </div>
        {/if}
    </div>
</div>
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

        // Apply filter - always reset to page 1
        $('#apply-filter').click(function() {
            const params = new URLSearchParams();
            params.append('page', '1'); // Always go to page 1 when applying filters

            $('.mv-filter-input').each(function() {
                if ($(this).val()) {
                    params.append($(this).attr('name'), $(this).val());
                }
            });

            const baseUrl = '{$link->getModuleLink('multivendor', 'commissions')}';
            const separator = baseUrl.includes('?') ? '&' : '?';
            window.location.href = baseUrl + (params.toString() ? separator + params.toString() : '');
        });

        // Reset filter
        $('#reset-filter').click(function() {
            window.location.href = '{$link->getModuleLink('multivendor', 'commissions')}';
        });

        // Drift zoom
        document.querySelectorAll('.zoomable-image').forEach(function(img) {
            new Drift(img, { inlineOffsetX: 200, zoomFactor: 6 });
        });
    });
</script>