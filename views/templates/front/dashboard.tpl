
{*
    * Vendor Dashboard Template
    *}
    
    {extends file='page.tpl'}
    
    {block name='page_title'}
        {l s='Vendor Dashboard' mod='multivendor'}
    {/block}
    
    {block name='page_content'}
        <div class="vendor-dashboard">
            {if $status != 1}
                <div class="alert alert-warning">
                    {if $status == 0}
                        {l s='Your vendor account is pending approval.' mod='multivendor'}
                    {else}
                        {l s='Your vendor account has been rejected.' mod='multivendor'}
                    {/if}
                </div>
            {/if}
            
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{l s='Navigation' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            <ul class="nav flex-column">
                                <li class="nav-item active">
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
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{l s='Monthly Sales' mod='multivendor'}</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="salesChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{l s='Recent Orders' mod='multivendor'}</h3>
                                </div>
                                <div class="card-body">
                                    {if $recent_orders}
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>{l s='Order' mod='multivendor'}</th>
                                                        <th>{l s='Date' mod='multivendor'}</th>
                                                        <th>{l s='Status' mod='multivendor'}</th>
                                                        <th>{l s='Total' mod='multivendor'}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {foreach from=$recent_orders item=order}
                                                        <tr>
                                                            <td>
                                                                <a href="{$vendor_orders_url}&id_order={$order.id_order}">
                                                                    #{$order.reference}
                                                                </a>
                                                            </td>
                                                            <td>{$order.date_add|date_format:'%Y-%m-%d'}</td>
                                                            <td>{$order.status}</td>
                                                            <td>{Tools::displayPrice($order.total_paid)}</td>
                                                        </tr>
                                                    {/foreach}
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center">
                                            <a href="{$vendor_orders_url}" class="btn btn-outline-primary btn-sm">
                                                {l s='View All Orders' mod='multivendor'}
                                            </a>
                                        </div>
                                    {else}
                                        <p class="text-center">{l s='No recent orders.' mod='multivendor'}</p>
                                    {/if}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{l s='Top Selling Products' mod='multivendor'}</h3>
                                </div>
                                <div class="card-body">
                                    {if $top_products}
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>{l s='Product' mod='multivendor'}</th>
                                                        <th>{l s='Quantity' mod='multivendor'}</th>
                                                        <th>{l s='Sales' mod='multivendor'}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {foreach from=$top_products item=product}
                                                        <tr>
                                                            <td>{$product.product_name}</td>
                                                            <td>{$product.quantity_sold}</td>
                                                            <td>{$product.total_sales|displayPrice}</td>
                                                        </tr>
                                                    {/foreach}
                                                </tbody>
                                            </table>
                                        </div>
                                    {else}
                                        <p class="text-center">{l s='No sales data available.' mod='multivendor'}</p>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var ctx = document.getElementById('salesChart').getContext('2d');
                var monthlyData = {
                    labels: [
                        {foreach from=$monthly_sales item=month}
                            '{$month.month}',
                        {/foreach}
                    ],
                    datasets: [{
                        label: '{l s='Monthly Sales' mod='multivendor'}',
                        data: [
                            {foreach from=$monthly_sales item=month}
                                {$month.sales},
                            {/foreach}
                        ],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                };
                
                var salesChart = new Chart(ctx, {
                    type: 'bar',
                    data: monthlyData,
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value, index, values) {
                                        return '{$currency_sign}' + value;
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    {/block}