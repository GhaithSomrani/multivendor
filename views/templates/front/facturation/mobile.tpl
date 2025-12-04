{*
* Mobile Facturation Template
* views/templates/front/facturation/mobile.tpl
*}

<div class="mv-mobile-commissions">
    {* Payment History Card *}
    <div class="mv-card mv-mobile-orders-card">
        <div class="mv-card-header">
            <h5 class="mv-card-title">{l s='Historique des paiements' mod='multivendor'}</h5>
            {if $total_payments > 0}
                <div class="mv-count-badge">
                    <span>{$total_payments}
                        {if $total_payments > 1}{l s='paiements' mod='multivendor'}{else}{l s='paiement' mod='multivendor'}{/if}</span>
                </div>
            {/if}
        </div>
        <div class="mv-card-body">
            {if $payments}
                <div class="mv-mobile-orders-grid">
                    {foreach from=$payments item=payment}
                        <div class="mv-mobile-order-item" data-id="{$payment.id_vendor_payment}">

                            {* Main Content *}
                            <div class="mv-main-content">
                                <div class="mv-mobile-order-header">
                                    <div class="mv-mobile-order-ref">
                                        <a href="#" class="mv-mobile-link">
                                            <strong>{$payment.reference|truncate:30:"...":true}</strong>
                                        </a>
                                    </div>

                                    <div class="mv-mobile-order-date">
                                        {$payment.date_add|date_format:'%d/%m/%Y'}
                                    </div>

                                    {* Action Buttons *}
                                    <div class="mv-mobile-action-buttons">
                                        {if $payment.status == 'completed'}
                                            <button class="mv-mobile-print-btn"
                                                onclick="printPayment({$payment.id_vendor_payment}, event)">
                                                🖨️
                                            </button>
                                        {/if}
                                        <button class="mv-slide-arrow" onclick="toggleSlide(this)">
                                            ▶ Détail
                                        </button>
                                    </div>
                                </div>

                                <div class="mv-mobile-order-content">
                                    <div class="mv-mobile-order-details">
                                        <div class="mv-mobile-detail-row">
                                            <span class="mv-mobile-label">
                                                <small>{l s='Montant Total:' mod='multivendor'}</small></span>
                                            <span class="mv-mobile-value mv-mobile-amount"
                                                style="font-size: 18px; font-weight: 700; color: #10b981;">
                                                {$payment.amount|number_format:3} TND</span>
                                        </div>
                                        <div class="mv-mobile-detail-row">
                                            <span class="mv-mobile-label">
                                                <small>{l s='Méthode:' mod='multivendor'}</small></span>
                                            <span class="mv-mobile-value">
                                                {if $payment.payment_method == 'bank_transfer'}
                                                    {l s='Virement banquer' mod='multivendor'}
                                                {elseif $payment.payment_method == 'check'}
                                                    {l s='Chèque' mod='multivendor'}
                                                {elseif $payment.payment_method == 'cash'}
                                                    {l s='Espèces' mod='multivendor'}
                                                {else}
                                                    {l s='Autre' mod='multivendor'}
                                                {/if}
                                            </span>
                                        </div>
                                        <div class="mv-mobile-detail-row">
                                            <span class="mv-mobile-label">
                                                <small>{l s='Nombre de Lignes:' mod='multivendor'}</small></span>
                                            <span class="mv-mobile-value">
                                                {if $payment.order_details}{count($payment.order_details)}{else}0{/if}</span>
                                        </div>
                                    </div>

                                    <div class="mv-mobile-status-section">
                                        <div class="mv-mobile-current-status">
                                            <span class="mv-status-badge mv-status-{$payment.status}">
                                                {if isset($payment.status) && $payment.status == 'pending'}
                                                    {l s='En Cours' mod='multivendor'}
                                                {elseif isset($payment.status) && $payment.status == 'completed'}
                                                    {l s='Complété' mod='multivendor'}
                                                {else}
                                                    {$payment.status|capitalize}
                                                {/if}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {* Hidden Content (slides in from right) *}
                            <div class="mv-hidden-content">
                                {* Return Button *}
                                <div
                                    style="display: flex;justify-content: space-between;align-items: center; margin-bottom: 15px;">
                                    <h6 style="margin: 0;">Détails des Transactions</h6>
                                    <button class="mv-slide-arrow" onclick="toggleSlide(this)">◀ Retour</button>
                                </div>

                                {* Transaction Details Section *}
                                <div class="mv-transactions-section">
                                    {if $payment.order_details}
                                        {foreach from=$payment.order_details item=detail}
                                            <div class="mv-mobile-transaction-box"
                                                style="width: 100%; padding: 12px; margin-bottom: 10px; background: #f8f9fa; border-radius: 8px; border-left: 3px solid #6366f1;">
                                                <div
                                                    style="display: flex; justify-content: space-between; margin-bottom: 8px; align-items: center;">
                                                    <span style="font-weight: 600; font-size: 13px; color: #374151;">
                                                        {if $detail.product_name}
                                                            {$detail.product_name|truncate:30:'...'}
                                                        {else}
                                                            Produit #{$detail.id_order_detail}
                                                        {/if}
                                                    </span>
                                                    <span
                                                        style="font-weight: 700; font-size: 15px; color: #10b981; white-space: nowrap; margin-left: 10px;">
                                                        {$detail.vendor_amount|number_format:3} TND
                                                    </span>
                                                </div>
                                                <div style="font-size: 11px; color: #6b7280; line-height: 1.6;">
                                                    <div style="margin-bottom: 4px;">
                                                        <strong>SKU:</strong> {$detail.product_reference|default:'-'}
                                                    </div>
                                                    <div style="margin-bottom: 4px;">
                                                        <strong>Qté:</strong> {$detail.product_quantity|default:'-'}
                                                    </div>
                                                    {if $detail.order_date}
                                                        <div style="color: #9ca3af;">
                                                            📅 {$detail.order_date|date_format:'%d/%m/%Y'}
                                                        </div>
                                                    {/if}
                                                </div>
                                            </div>
                                        {/foreach}

                                        {* Total Section *}
                                        <div style="margin-top: 15px; padding: 12px; background: border-radius: 8px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span>
                                                    Total
                                                </span>
                                                <span>
                                                    <strong> {$payment.amount|number_format:3} TND
                                                    </strong>
                                                </span>
                                            </div>
                                        </div>
                                    {else}
                                        <div style="text-align: center; padding: 30px; color: #999;">
                                            <div style="font-size: 40px; margin-bottom: 10px;">📋</div>
                                            <p style="font-style: italic;">Aucun détail de transaction disponible</p>
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>

                {* Mobile Pagination *}
                {if $payment_pages_nb > 1}
                    <div class="mv-mobile-pagination">
                        <div class="mv-mobile-pagination-info">
                            {l s='Page' mod='multivendor'} {$payment_current_page} {l s='sur' mod='multivendor'}
                            {$payment_pages_nb}
                        </div>
                        <div class="mv-mobile-pagination-controls">
                            {if $payment_current_page > 1}
                                <a class="mv-mobile-pagination-btn"
                                    href="{$link->getModuleLink('multivendor', 'facturation', ['payment_page' => $payment_current_page-1])}">
                                    ‹ {l s='Précédent' mod='multivendor'}
                                </a>
                            {/if}
                            {if $payment_current_page < $payment_pages_nb}
                                <a class="mv-mobile-pagination-btn"
                                    href="{$link->getModuleLink('multivendor', 'facturation', ['payment_page' => $payment_current_page+1])}">
                                    {l s='Suivant' mod='multivendor'} ›
                                </a>
                            {/if}
                        </div>
                    </div>
                {/if}
            {else}
                <div class="mv-mobile-empty-state">
                    <div class="mv-mobile-empty-icon">💳</div>
                    <p>{l s='Aucun historique de paiement trouvé.' mod='multivendor'}</p>
                </div>
            {/if}
        </div>
    </div>
</div>

<script>
    function toggleSlide(button) {
        const orderItem = button.closest('.mv-mobile-order-item');
        orderItem.classList.toggle('slided');
    }

    function printPayment(paymentId, event) {
        event.stopPropagation();
        var url = '{$link->getModuleLink("multivendor", "facturation", ["ajax" => 1 ])}'+"&action=printPayment" + '&id_payment=' + paymentId;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                var printWindow = window.open('', '_blank');
                printWindow.document.write(html);
                printWindow.document.close();
                printWindow.print();
            })
            .catch(error => console.error('Error:', error));
    }
</script>