{*
    * Vendor Commissions Template
    *}
    
    {extends file='page.tpl'}
    
    {block name='page_title'}
        {l s='My Commissions' mod='multivendor'}
    {/block}
    
    {block name='page_content'}
        <div class="vendor-commissions">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{l s='Navigation' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_dashboard_url}">
                                        <i class="material-icons">dashboard</i>
                                        {l s='Dashboard' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_orders_url}">
                                        <i class="material-icons">shopping_cart</i>
                                        {l s='Orders' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item active">
                                    <a class="nav-link" href="#">
                                        <i class="material-icons">attach_money</i>
                                        {l s='Commissions' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_profile_url}">
                                        <i class="material-icons">store</i>
                                        {l s='Shop Profile' mod='multivendor'}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6>{l s='Total Sales' mod='multivendor'}</h6>
                                    <h3>{Tools::displayPrice($commission_summary.total_sales)}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6>{l s='Total Commission' mod='multivendor'}</h6>
                                    <h3>{Tools::displayPrice($commission_summary.total_commission)}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6>{l s='Pending Amount' mod='multivendor'}</h6>
                                    <h3>{Tools::displayPrice($commission_summary.pending_amount)}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">{l s='Commission Rates' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>{l s='Your standard commission rate:' mod='multivendor'}</strong> {$vendor_commission_rate}%</p>
                            
                            {if $category_commissions}
                                <h5 class="mt-3">{l s='Category-specific commission rates:' mod='multivendor'}</h5>
                                <div class="table-responsive">
                                    <table class="table">
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
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">{l s='Commission Transactions' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            {if $transactions}
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>{l s='Order' mod='multivendor'}</th>
                                                <th>{l s='Date' mod='multivendor'}</th>
                                                <th>{l s='Commission' mod='multivendor'}</th>
                                                <th>{l s='Your Amount' mod='multivendor'}</th>
                                                <th>{l s='Status' mod='multivendor'}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {foreach from=$transactions item=transaction}
                                                <tr>
                                                    <td>
                                                        <a href="{$link->getModuleLink('multivendor', 'orders', ['id_order' => $transaction.id_order])}">
                                                            #{$transaction.reference}
                                                        </a>
                                                    </td>
                                                    <td>{$transaction.date_add|date_format:'%Y-%m-%d'}</td>
                                                    <td>{$transaction.commission_amount|displayPrice}</td>
                                                    <td>{$transaction.vendor_amount|displayPrice}</td>
                                                    <td>
                                                        <span class="badge 
                                                            {if $transaction.status == 'pending'}badge-warning
                                                            {elseif $transaction.status == 'paid'}badge-success
                                                            {elseif $transaction.status == 'cancelled'}badge-danger
                                                            {else}badge-secondary{/if}">
                                                            {$transaction.status|capitalize}
                                                        </span>
                                                    </td>
                                                </tr>
                                            {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                                
                                {* Pagination *}
                                {if $pages_nb > 1}
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            {if $current_page > 1}
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => 1])}" aria-label="First">
                                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $current_page-1])}" aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            {/if}
                                            
                                            {assign var=p_start value=max(1, $current_page-2)}
                                            {assign var=p_end value=min($pages_nb, $current_page+2)}
                                            
                                            {for $p=$p_start to $p_end}
                                                <li class="page-item {if $p == $current_page}active{/if}">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $p])}">{$p}</a>
                                                </li>
                                            {/for}
                                            
                                            {if $current_page < $pages_nb}
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $current_page+1])}" aria-label="Next">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $pages_nb])}" aria-label="Last">
                                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                                    </a>
                                                </li>
                                            {/if}
                                        </ul>
                                    </nav>
                                {/if}
                            {else}
                                <div class="alert alert-info">
                                    {l s='No commission transactions found.' mod='multivendor'}
                                </div>
                            {/if}
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{l s='Payment History' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            {if $payments}
                                <div class="table-responsive">
                                    <table class="table">
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
                                                    <td>{$payment.payment_method}</td>
                                                    <td>{$payment.reference}</td>
                                                    <td>
                                                        <span class="badge 
                                                            {if $payment.status == 'pending'}badge-warning
                                                            {elseif $payment.status == 'completed'}badge-success
                                                            {elseif $payment.status == 'cancelled'}badge-danger
                                                            {else}badge-secondary{/if}">
                                                            {$payment.status|capitalize}
                                                        </span>
                                                    </td>
                                                </tr>
                                            {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            {else}
                                <div class="alert alert-info">
                                    {l s='No payment history found.' mod='multivendor'}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/block}