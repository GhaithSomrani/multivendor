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
        {if $transactions}
            <div class="mv-table-container">
                <table class="mv-table">
                    <thead>
                        <tr>
                            <th>{l s='Commande' mod='multivendor'}</th>
                            <th>{l s='Date' mod='multivendor'}</th>
                            <th>{l s='Produit' mod='multivendor'}</th>
                            <th>{l s='Action' mod='multivendor'}</th>
                            <th>{l s='Votre montant' mod='multivendor'}</th>
                            <th>{l s='Statut' mod='multivendor'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$transactions item=transaction}
                            <tr>
                                <td>
                                    <a href="#" class="mv-link">
                                        #{$transaction.order_reference}#{$transaction.id_order_detail}
                                    </a>
                                </td>
                                <td>{$transaction.order_date|date_format:'%Y-%m-%d'}</td>
                                <td>
                                    {$transaction.product_name|truncate:80:'...'} 
                                    {if $transaction.product_quantity > 0}
                                        (x{$transaction.product_quantity})
                                    {/if}
                                     <br> (SKU : {$transaction.product_reference})
                                </td>
                                <td>
                                    <span class="mv-action-type mv-action-{$transaction.commission_action}">
                                        {if $transaction.commission_action == 'refund'}remboursement{else}{$transaction.commission_action}{/if}
                                    </span>
                                </td>
                               
                                <td>
                                    {if $transaction.commission_action == 'refund'}
                                        -{$transaction.vendor_amount|number_format:3}
                                    {else}
                                        {$transaction.vendor_amount|number_format:3}
                                    {/if}
                                </td>
                                <td>
                                    <span class="mv-status-badge" style="background-color: {$transaction.status_color};">
                                        {$transaction.line_status|capitalize}
                                    </span>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            
            {* Pagination *}
            {if $pages_nb > 1}
                <nav class="mv-pagination">
                    <ul class="mv-pagination-list">
                        {if $current_page > 1}
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => 1])}">
                                    <span>«</span>
                                </a>
                            </li>
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $current_page-1])}">
                                    <span>‹</span>
                                </a>
                            </li>
                        {/if}
                        
                        {assign var=p_start value=max(1, $current_page-2)}
                        {assign var=p_end value=min($pages_nb, $current_page+2)}
                        
                        {for $p=$p_start to $p_end}
                            <li class="mv-pagination-item {if $p == $current_page}mv-pagination-active{/if}">
                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $p])}">{$p}</a>
                            </li>
                        {/for}
                        
                        {if $current_page < $pages_nb}
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $current_page+1])}">
                                    <span>›</span>
                                </a>
                            </li>
                            <li class="mv-pagination-item">
                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $pages_nb])}">
                                    <span>»</span>
                                </a>
                            </li>
                        {/if}
                    </ul>
                </nav>
            {/if}
        {else}
            <div class="mv-empty-state">
                {l s='Aucune transaction de commission trouvée.' mod='multivendor'}
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
            <div class="mv-payments-list">
                {foreach from=$payments item=payment}
                    <div class="mv-payment-item">
                        {* Payment Header *}
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
                            <button class="mv-btn-toggle" onclick="togglePaymentDetails('payment-{$payment.id_vendor_payment}')">
                                <i class="mv-icon-chevron">▼</i>
                            </button>
                        </div>
                        
                        {* Payment Details *}
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
                                                    {if $detail.order_reference}
                                                        <a href="#" class="mv-link">
                                                            #{$detail.order_reference}
                                                        </a>
                                                    {else}
                                                        <span class="text-muted">{l s='Commande #' mod='multivendor'}{$detail.id_order}</span>
                                                    {/if}
                                                </td>
                                                <td>
                                                    {if $detail.product_name}
                                                        {$detail.product_name|truncate:40:'...'}
                                                    {else}
                                                        <span class="text-muted">{l s='Détails du produit non disponibles' mod='multivendor'}</span>
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
                                            <td colspan="4" class="mv-text-right"><strong>{l s='Total :' mod='multivendor'}</strong></td>
                                            <td><strong>{$payment.amount|number_format:3} TND</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            {else}
                                <p class="mv-empty-state">{l s='Aucun détail de commande disponible pour ce paiement.' mod='multivendor'}</p>
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