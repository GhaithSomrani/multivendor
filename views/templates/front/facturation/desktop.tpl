{*
* Desktop Facturation Template
* views/templates/front/facturation/desktop.tpl
*}

{* Payment History Card - with filters *}
<div class="mv-card">
    <div class="mv-card-header">
        <h3 class="mv-card-title">{l s='Historique des paiements' mod='multivendor'}</h3>
        {if $total_payments > 0}
            <div class="mv-count-badge">
                <span>{$total_payments}
                    {if $total_payments > 1}{l s='paiements' mod='multivendor'}{else}{l s='paiement' mod='multivendor'}{/if}</span>
            </div>
        {/if}
    </div>
    <div class="mv-card-body">
        {if $payments || $payment_filter}
            <div class="mv-table-container">
                <table class="mv-table">
                    <thead>
                        <tr>
                            <th>{l s='Référence' mod='multivendor'}</th>
                            <th>{l s='Date' mod='multivendor'}</th>
                            <th>{l s='Montant' mod='multivendor'}</th>
                            <th>{l s='Méthode' mod='multivendor'}</th>
                            <th>{l s='Statut' mod='multivendor'}</th>
                            <th></th>
                        </tr>
                        <tr class="mv-filter-row">
                            <th>
                                <input type="text" class="mv-filter-input payment-filter" name="payment_reference"
                                    placeholder="Référence" value="{$payment_filter.reference|default:''}">
                            </th>
                            <th>
                                <input type="text" class="mv-filter-input payment-filter" name="payment_date_filter"
                                    placeholder="Période" value="{$payment_filter.datefilter|default:''}">
                            </th>
                            <th class="mv-filter-range">
                                <input type="text" class="mv-filter-input payment-filter" name="payment_amount_min"
                                    placeholder="Min" value="{$payment_filter.amount_min|default:''}">
                                <input type="text" class="mv-filter-input payment-filter" name="payment_amount_max"
                                    placeholder="Max" value="{$payment_filter.amount_max|default:''}">
                            </th>
                            <th>
                                <select class="mv-filter-input payment-filter" name="payment_method">
                                    <option value="">Tous</option>
                                    <option value="bank_transfer"
                                        {if isset($payment_filter.payment_method) && $payment_filter.payment_method == 'bank_transfer'}selected{/if}>
                                        Virement</option>
                                    <option value="check"
                                        {if isset($payment_filter.payment_method) && $payment_filter.payment_method == 'check'}selected{/if}>
                                        Chèque</option>
                                    <option value="cash"
                                        {if isset($payment_filter.payment_method) && $payment_filter.payment_method == 'cash'}selected{/if}>
                                        Espèces</option>
                                </select>
                            </th>
                            <th>
                                <select class="mv-filter-input payment-filter" name="payment_status">
                                    <option value="">Tous</option>
                                    <option value="pending"
                                        {if isset($payment_filter.status) && $payment_filter.status == 'pending'}selected{/if}>
                                        En cours</option>
                                    <option value="completed"
                                        {if isset($payment_filter.status) && $payment_filter.status == 'completed'}selected{/if}>
                                        Complété</option>

                                </select>
                            </th>
                            <th class="mv-filter-range" style="text-align-last: end;">
                                <button type="button" class="mv-status-btn mv-btn-filter"
                                    id="apply-payment-filter">🔍︎</button>
                                <button type="button" class="mv-status-btn mv-btn-reset"
                                    id="reset-payment-filter">✖</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $payments}
                            {foreach from=$payments item=payment}
                                <tr onclick="toggledown(this)">
                                    <td>{$payment.reference}</td>
                                    <td>{$payment.date_add|date_format:'%Y-%m-%d %H:%M'}</td>
                                    <td><strong>{$payment.amount|number_format:3} TND</strong></td>
                                    <td>
                                        {if $payment.payment_method == 'bank_transfer'}
                                            {l s='Virement' mod='multivendor'}
                                        {elseif $payment.payment_method == 'check'}
                                            {l s='Chèque' mod='multivendor'}
                                        {elseif $payment.payment_method == 'cash'}
                                            {l s='Espèces' mod='multivendor'}
                                        {else}
                                            {l s='Autre' mod='multivendor'}
                                        {/if}
                                    </td>
                                    <td>
                                        <span class="mv-status-badge mv-status-{$payment.status}">
                                            {if isset($payment.status) && $payment.status == 'pending'}
                                                {l s='En Cours' mod='multivendor'}
                                            {elseif isset($payment.status) && $payment.status == 'completed'}
                                                {l s='Complété' mod='multivendor'}
                                            {else}
                                                {$payment.status|capitalize}
                                            {/if}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex;gap: 0.8rem;justify-content: end;align-items: center;">
                                            {if $payment.status == 'completed'}
                                                <button class="mv-status-btn mv-btn-print"
                                                    onclick="printPayment({$payment.id_vendor_payment}, event)">🖨️</button>
                                            {/if}

                                            <button class="mv-collapse-btn" onclick="toggleCollapse(this)">+</button>
                                        </div>


                                    </td>
                                </tr>
                                <tr class="mv-row-details">
                                    <td colspan="6">
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
                                                                <span href="" class="mv-link"> <strong>{$detail.id_order}</strong><small>
                                                                        {$detail.order_detail_id}</small></span>
                                                            </td>
                                                            <td>
                                                                {if $detail.product_name}
                                                                    {$detail.product_name|truncate:40:'...'}
                                                                {else}
                                                                    <span
                                                                        class="text-muted">{l s='Produit non disponible' mod='multivendor'}</span>
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
                                                        <td colspan="4" class="mv-text-right">
                                                            <strong>{l s='Total :' mod='multivendor'}</strong>
                                                        </td>
                                                        <td><strong>{$payment.amount|number_format:3} TND</strong></td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        {else}
                                            <p class="mv-empty-state">{l s='Aucun détail de commande disponible.' mod='multivendor'}</p>
                                        {/if}
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="6">
                                    <p class="mv-empty-state">{l s='Aucun paiement trouvé.' mod='multivendor'}</p>
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>

            {if $payment_pages_nb > 1}
                <nav class="mv-pagination">
                    <ul class="mv-pagination-list">
                        {if $payment_current_page > 1}
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'facturation', array_merge(['payment_page' => 1], $payment_filter))}">
                                    <span>«</span>
                                </a>
                            </li>
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'facturation', array_merge(['payment_page' => $payment_current_page-1], $payment_filter))}">
                                    <span>‹</span>
                                </a>
                            </li>
                        {/if}

                        {assign var=p_start value=max(1, $payment_current_page-2)}
                        {assign var=p_end value=min($payment_pages_nb, $payment_current_page+2)}

                        {for $p=$p_start to $p_end}
                            <li class="mv-pagination-item {if $p == $payment_current_page}mv-pagination-active{/if}">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'facturation', array_merge(['payment_page' => $p], $payment_filter))}">{$p}</a>
                            </li>
                        {/for}

                        {if $payment_current_page < $payment_pages_nb}
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'facturation', array_merge(['payment_page' => $payment_current_page+1], $payment_filter))}">
                                    <span>›</span>
                                </a>
                            </li>
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link"
                                    href="{$link->getModuleLink('multivendor', 'facturation', array_merge(['payment_page' => $payment_pages_nb], $payment_filter))}">
                                    <span>»</span>
                                </a>
                            </li>
                        {/if}
                    </ul>
                </nav>
            {/if}
        {else}
            <div class="mv-empty-state">{l s='Aucun historique de paiement trouvé.' mod='multivendor'}</div>
        {/if}
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Date filter
        $('input[name="payment_date_filter"]').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Effacer',
                applyLabel: 'Appliquer',
                format: 'DD/MM/YYYY'
            }
        });

        $('input[name="payment_date_filter"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format(
                'DD/MM/YYYY'));
        });

        $('input[name="payment_date_filter"]').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        // Apply payment filter function
        function applyPaymentFilter() {
            var params = {};
            $('.payment-filter').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                if (value) {
                    params[name] = value;
                }
            });
            var url = '{$link->getModuleLink("multivendor", "facturation")|escape:"javascript":"UTF-8"}';
            var queryString = $.param(params);
            window.location.href = url + (queryString ? '?' + queryString : '');
        }

        // Apply payment filter on button click
        $('#apply-payment-filter').on('click', function() {
            applyPaymentFilter();
        });

        // Apply payment filter on Enter key press
        $('.payment-filter').on('keypress', function(e) {
            if (e.which === 13 || e.keyCode === 13) {
                e.preventDefault();
                applyPaymentFilter();
            }
        });

        // Reset payment filter
        $('#reset-payment-filter').on('click', function() {
            window.location.href = '{$link->getModuleLink("multivendor", "facturation")|escape:"javascript":"UTF-8"}';
        });
    });

    function printPayment(paymentId, event) {
        event.stopPropagation();
        var url = '{$link->getModuleLink("multivendor", "facturation", ["ajax" => 1 ])}'+"&action=printPayment" + '&id_payment=' + paymentId;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                var printWindow = window.open('', '_blank');
                printWindow.document.write(html);
                printWindow.document.close();
                // printWindow.print();
            })
            .catch(error => console.error('Error:', error));
    }

    function toggleCollapse(button) {
        event.stopPropagation();
        var row = $(button).closest('tr');
        var detailsRow = row.next('.mv-row-details');

        detailsRow.toggleClass('open');
        button.textContent = detailsRow.hasClass('open') ? '-' : '+';
    }

    function toggledown(row) {
        var detailsRow = $(row).next('.mv-row-details');
        var button = $(row).find('.mv-collapse-btn');

        detailsRow.toggleClass('open');
        button.text(detailsRow.hasClass('open') ? '-' : '+');
    }
</script>