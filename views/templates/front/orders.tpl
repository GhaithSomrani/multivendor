{*
    * Vendor Orders Template
    *}
    
    {extends file='page.tpl'}
    
    {block name='page_title'}
        {l s='My Orders' mod='multivendor'}
    {/block}
    
    {block name='page_content'}
        <div class="vendor-orders">
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
                                <li class="nav-item active">
                                    <a class="nav-link" href="#">
                                        <i class="material-icons">shopping_cart</i>
                                        {l s='Orders' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_commissions_url}">
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
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{l s='My Orders' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            {if $orders}
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>{l s='Order Reference' mod='multivendor'}</th>
                                                <th>{l s='Date' mod='multivendor'}</th>
                                                <th>{l s='Status' mod='multivendor'}</th>
                                                <th>{l s='Total' mod='multivendor'}</th>
                                                <th>{l s='Actions' mod='multivendor'}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {foreach from=$orders item=order}
                                                <tr>
                                                    <td>#{$order.reference}</td>
                                                    <td>{$order.date_add|date_format:'%Y-%m-%d %H:%M:%S'}</td>
                                                    <td>{$order.status}</td>
                                                    <td>{Tools::displayPrice($order.total_paid)}</td>
                                                    <td>
                                                        <a href="{$link->getModuleLink('multivendor', 'orders', ['id_order' => $order.id_order])}" class="btn btn-primary btn-sm">
                                                            <i class="material-icons">visibility</i>
                                                            {l s='View' mod='multivendor'}
                                                        </a>
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
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => 1])}" aria-label="First">
                                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page-1])}" aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            {/if}
                                            
                                            {assign var=p_start value=max(1, $current_page-2)}
                                            {assign var=p_end value=min($pages_nb, $current_page+2)}
                                            
                                            {for $p=$p_start to $p_end}
                                                <li class="page-item {if $p == $current_page}active{/if}">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $p])}">{$p}</a>
                                                </li>
                                            {/for}
                                            
                                            {if $current_page < $pages_nb}
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page+1])}" aria-label="Next">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $pages_nb])}" aria-label="Last">
                                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                                    </a>
                                                </li>
                                            {/if}
                                        </ul>
                                    </nav>
                                {/if}
                            {else}
                                <div class="alert alert-info">
                                    {l s='No orders found.' mod='multivendor'}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/block}