{*
    * Vendor Commissions Template
    *}
    
    {extends file='page.tpl'}
    
    {block name='page_title'}
        {l s='My Commissions' mod='multivendor'}
    {/block}
    
    {block name='page_content'}
        <div class="mv-dashboard">
            <div class="mv-container">
                <aside class="mv-sidebar">
                    <div class="mv-card">

                        <div class="mv-card-body">
                            <nav class="mv-nav">
                                <a class="mv-nav-link" href="{$vendor_dashboard_url}">
                                    <i class="mv-icon">📊</i>
                                    <span>{l s='Dashboard' mod='multivendor'}</span>
                                </a>
                                <a class="mv-nav-link" href="{$vendor_orders_url}">
                                    <i class="mv-icon">🛒</i>
                                    <span>{l s='Orders' mod='multivendor'}</span>
                                </a>
                                <a class="mv-nav-link mv-nav-link-active" href="#">
                                    <i class="mv-icon">💰</i>
                                    <span>{l s='Commissions' mod='multivendor'}</span>
                                </a>
                               
                            </nav>
                        </div>
                    </div>
                </aside>
                
                <main class="mv-main-content">
                    {* Commission Summary Cards *}
                    <div class="mv-commission-summary">
                      
                        <div class="mv-summary-card mv-summary-card-add">
                            <h6 class="mv-stat-label">{l s='Commissions Earned' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{Tools::displayPrice($commission_summary.total_commission_added)}</h3>
                            <p class="mv-stat-description">{l s='Total commissions added' mod='multivendor'}</p>
                        </div>
                        <div class="mv-summary-card mv-summary-card-paid">
                            <h6 class="mv-stat-label">{l s='Paid Amount' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{Tools::displayPrice($commission_summary.paid_commission)}</h3>
                            <p class="mv-stat-description">{l s='Total paid to you' mod='multivendor'}</p>
                        </div>
                        <div class="mv-summary-card mv-summary-card-pending">
                            <h6 class="mv-stat-label">{l s='Pending Amount' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{Tools::displayPrice($commission_summary.pending_amount)}</h3>
                            <p class="mv-stat-description">{l s='Earned - Paid' mod='multivendor'}</p>
                        </div>
                            <div class="mv-summary-card mv-summary-card-refund">
                            <h6 class="mv-stat-label">{l s='Commissions Refunded' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">-{Tools::displayPrice($commission_summary.total_commission_refunded)}</h3>
                            <p class="mv-stat-description">{l s='Total refunds' mod='multivendor'}</p>
                        </div>
                    </div>
                    
                    {* Commission Rates Card *}
                    <div class="mv-card">
                        <div class="mv-card-header">
                            <h3 class="mv-card-title">{l s='Commission Rates' mod='multivendor'}</h3>
                        </div>
                        <div class="mv-card-body">
                            <div class="mv-commission-detail">
                                <span class="mv-commission-detail-label">{l s='Your standard commission rate:' mod='multivendor'}</span>
                                <span class="mv-commission-detail-value">{$vendor_commission_rate}%</span>
                            </div>
                            
                            {if $category_commissions}
                                <h5 class="mt-3">{l s='Category-specific commission rates:' mod='multivendor'}</h5>
                                <div class="mv-table-container">
                                    <table class="mv-table">
                                        <thead>
                                            <tr>
                                                <th>{l s='Category' mod='multivendor'}</th>
                                                <th>{l s='Commission Rate' mod='multivendor'}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {foreach from=$category_commissions item=commission}
                                                <tr>
                                                    <td>{$commission.category_name}</td>
                                                    <td>{$commission.commission_rate}%</td>
                                                </tr>
                                            {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            {/if}
                        </div>
                    </div>
                    
                    {* Commission Transactions Card *}
                    <div class="mv-card">
                        <div class="mv-card-header">
                            <h3 class="mv-card-title">{l s='Commission Transactions' mod='multivendor'}</h3>
                        </div>
                        <div class="mv-card-body">
                            {if $transactions}
                                <div class="mv-table-container">
                                    <table class="mv-table">
                                        <thead>
                                            <tr>
                                                <th>{l s='Order' mod='multivendor'}</th>
                                                <th>{l s='Date' mod='multivendor'}</th>
                                                <th>{l s='Product' mod='multivendor'}</th>
                                                <th>{l s='Action' mod='multivendor'}</th>
                                                <th>{l s='Your Amount' mod='multivendor'}</th>
                                                <th>{l s='Status' mod='multivendor'}</th>
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
                                                            {$transaction.commission_action}
                                                        </span>
                                                    </td>
                                                   
                                                    <td>
                                                        {if $transaction.commission_action == 'refund'}
                                                            -{$transaction.vendor_amount|displayPrice}
                                                        {else}
                                                            {$transaction.vendor_amount|displayPrice}
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
                                    {l s='No commission transactions found.' mod='multivendor'}
                                </div>
                            {/if}
                        </div>
                    </div>
                    
                    {* Payment History Card *}
                    <div class="mv-card">
                        <div class="mv-card-header">
                            <h3 class="mv-card-title">{l s='Payment History' mod='multivendor'}</h3>
                        </div>
                        <div class="mv-card-body">
                            {if $payments}
                                <div class="mv-table-container">
                                    <table class="mv-table">
                                        <thead>
                                            <tr>
                                                <th>{l s='Date' mod='multivendor'}</th>
                                                <th>{l s='Amount' mod='multivendor'}</th>
                                                <th>{l s='Method' mod='multivendor'}</th>
                                                <th>{l s='Reference' mod='multivendor'}</th>
                                                <th>{l s='Status' mod='multivendor'}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {foreach from=$payments item=payment}
                                                <tr>
                                                    <td>{$payment.date_add|date_format:'%Y-%m-%d'}</td>
                                                    <td>{$payment.amount|displayPrice}</td>
                                                    <td>{$payment.payment_method|capitalize}</td>
                                                    <td>{$payment.reference}</td>
                                                    <td>
                                                        <span class="mv-status-badge mv-status-{$payment.status}">
                                                            {$payment.status|capitalize}
                                                        </span>
                                                    </td>
                                                </tr>
                                            {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            {else}
                                <div class="mv-empty-state">
                                    {l s='No payment history found.' mod='multivendor'}
                                </div>
                            {/if}
                        </div>
                    </div>
                </main>
            </div>
        </div>
    {/block}