{*
* Mobile Commissions Template
* views/templates/front/commissions/mobile.tpl
*}

<div class="mv-mobile-commissions">
    {* Commission Transactions Card *}
    <div class="mv-card">
        <div class="mv-card-header">
            <h3 class="mv-card-title">{l s='Transactions de commission' mod='multivendor'}</h3>
        </div>
        <div class="mv-card-body">
            {if $transactions}
                <div class="mv-mobile-transactions-list">
                    {foreach from=$transactions item=transaction}
                        <div class="mv-mobile-transaction-item">
                            <div class="mv-mobile-transaction-header">
                                <span class="mv-mobile-transaction-ref">#{$transaction.order_reference}#{$transaction.id_order_detail}</span>
                                <span class="mv-mobile-transaction-amount">
                                    {if $transaction.commission_action == 'refund'}
                                        -{$transaction.vendor_amount|number_format:2}
                                    {else}
                                        {$transaction.vendor_amount|number_format:2}
                                    {/if}
                                </span>
                            </div>
                            <div class="mv-mobile-transaction-product">{$transaction.product_name|truncate:40:'...'}</div>
                            <div class="mv-mobile-transaction-details">
                                <span class="mv-mobile-transaction-sku">SKU: {$transaction.product_reference}</span>
                                {if $transaction.product_quantity > 0}
                                    <span class="mv-mobile-transaction-qty">Qté: {$transaction.product_quantity}</span>
                                {/if}
                            </div>
                            <div class="mv-mobile-transaction-footer">
                                <span class="mv-action-type mv-action-{$transaction.commission_action}">
                                    {if $transaction.commission_action == 'refund'}remboursement{else}{$transaction.commission_action}{/if}
                                </span>
                                <span class="mv-mobile-transaction-date">{$transaction.order_date|date_format:'%Y-%m-%d'}</span>
                                <span class="mv-status-badge" style="background-color: {$transaction.status_color};">
                                    {$transaction.line_status|capitalize}
                                </span>
                            </div>
                        </div>
                    {/foreach}
                </div>
                
                {* Mobile Pagination *}
                {if $pages_nb > 1}
                    <div class="mv-mobile-pagination">
                        <div class="mv-mobile-pagination-info">
                            {l s='Page' mod='multivendor'} {$current_page} {l s='sur' mod='multivendor'} {$pages_nb}
                        </div>
                        <div class="mv-mobile-pagination-controls">
                            {if $current_page > 1}
                                <a class="mv-mobile-pagination-btn" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $current_page-1])}">
                                    ‹ {l s='Précédent' mod='multivendor'}
                                </a>
                            {/if}
                            {if $current_page < $pages_nb}
                                <a class="mv-mobile-pagination-btn" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $current_page+1])}">
                                    {l s='Suivant' mod='multivendor'} ›
                                </a>
                            {/if}
                        </div>
                    </div>
                {/if}
            {else}
                <div class="mv-mobile-empty-state">
                    <div class="mv-mobile-empty-icon">💰</div>
                    <p>{l s='Aucune transaction de commission trouvée.' mod='multivendor'}</p>
                </div>
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
                <div class="mv-mobile-payments-list">
                    {foreach from=$payments item=payment}
                        <div class="mv-mobile-payment-item">
                            <div class="mv-mobile-payment-header">
                                <div class="mv-mobile-payment-main">
                                    <span class="mv-mobile-payment-amount">{$payment.amount|number_format:2} TND</span>
                                    <span class="mv-status-badge mv-status-{$payment.status}">
                                        {$payment.status|capitalize}
                                    </span>
                                </div>
                                <button class="mv-mobile-btn-toggle" onclick="togglePaymentDetails('payment-{$payment.id_vendor_payment}')">
                                    <i class="mv-icon-chevron">▼</i>
                                </button>
                            </div>
                            <div class="mv-mobile-payment-info">
                                <span class="mv-mobile-payment-date">{$payment.date_add|date_format:'%Y-%m-%d'}</span>
                                <span class="mv-mobile-payment-method">{$payment.payment_method|capitalize}</span>
                                <span class="mv-mobile-payment-ref">Réf: {$payment.reference}</span>
                            </div>
                            
                            <div class="mv-mobile-payment-details" id="payment-{$payment.id_vendor_payment}" style="display: none;">
                                {if $payment.order_details}
                                    <div class="mv-mobile-order-details">
                                        {foreach from=$payment.order_details item=detail}
                                            <div class="mv-mobile-order-detail-item">
                                                <div class="mv-mobile-order-detail-header">
                                                    {if $detail.order_reference}
                                                        <span class="mv-mobile-order-ref">#{$detail.order_reference}</span>
                                                    {else}
                                                        <span class="mv-mobile-order-ref">Commande #{$detail.id_order}</span>
                                                    {/if}
                                                    <span class="mv-mobile-order-amount">{$detail.vendor_amount|number_format:2} TND</span>
                                                </div>
                                                <div class="mv-mobile-order-product">
                                                    {if $detail.product_name}
                                                        {$detail.product_name|truncate:40:'...'}
                                                    {else}
                                                        <span class="text-muted">{l s='Détails du produit non disponibles' mod='multivendor'}</span>
                                                    {/if}
                                                </div>
                                                <div class="mv-mobile-order-meta">
                                                    <span class="mv-mobile-order-sku">SKU: {$detail.product_reference|default:'-'}</span>
                                                    <span class="mv-mobile-order-qty">Qté: {$detail.product_quantity|default:'-'}</span>
                                                    {if $detail.order_date}
                                                        <span class="mv-mobile-order-date">{$detail.order_date|date_format:'%Y-%m-%d'}</span>
                                                    {/if}
                                                </div>
                                            </div>
                                        {/foreach}
                                        <div class="mv-mobile-payment-total">
                                            <strong>{l s='Total :' mod='multivendor'} {$payment.amount|number_format:2} TND</strong>
                                        </div>
                                    </div>
                                {else}
                                    <p class="mv-mobile-empty-state-small">{l s='Aucun détail de commande disponible pour ce paiement.' mod='multivendor'}</p>
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                </div>
            {else}
                <div class="mv-mobile-empty-state">
                    <div class="mv-mobile-empty-icon">💳</div>
                    <p>{l s='Aucun historique de paiement trouvé.' mod='multivendor'}</p>
                </div>
            {/if}
        </div>
    </div>
</div>

<style>

</style>