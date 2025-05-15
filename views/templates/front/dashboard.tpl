{*
* Vendor Dashboard Template with Custom CSS
*}

{extends file='page.tpl'}

{block name='page_title'}
    {l s='Vendor Dashboard' mod='multivendor'}
{/block}

{block name='page_content'}
    <div class="mv-dashboard">
        {if $status != 1}
            <div class="mv-alert mv-alert-warning">
                {if $status == 0}
                    {l s='Your vendor account is pending approval.' mod='multivendor'}
                {else}
                    {l s='Your vendor account has been rejected.' mod='multivendor'}
                {/if}
            </div>
        {/if}
        
        <div class="mv-container">
            <aside class="mv-sidebar">
                <div class="mv-card">
                    <div class="mv-card-body">
                        <nav class="mv-nav">
                            <nav class="mv-nav">
                            <a class="mv-nav-link mv-nav-link-active" href="{$vendor_dashboard_url}">
                                <i class="mv-icon">📊</i>
                                <span>{l s='Dashboard' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link" href="{$vendor_orders_url}">
                                <i class="mv-icon">🛒</i>
                                <span>{l s='Orders' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link " href="{$vendor_manage_orders_url}">
                                <i class="mv-icon">📦</i>
                                <span>{l s='Manage Orders' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link" href="{$vendor_commissions_url}">
                                <i class="mv-icon">💰</i>
                                <span>{l s='Commissions' mod='multivendor'}</span>
                            </a>
                        </nav>
                           
                        </nav>
                    </div>
                </div>
            </aside>
            
            <main class="mv-main-content">
                <div class="mv-stats-grid">
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Order Quantity' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{$indicators.order_quantity}</h3>
                            <p class="mv-stat-description">{l s='Total order lines' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Total CA' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{Tools::displayPrice($indicators.total_ca)}</h3>
                            <p class="mv-stat-description">{l s='Total revenue generated' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Products by Reference' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{$indicators.total_products_by_ref}</h3>
                            <p class="mv-stat-description">{l s='Unique product references' mod='multivendor'}</p>
                        </div>
                    </div>
                </div>
                
                 <div class="mv-charts-row">
                    <div class="mv-card mv-chart-card">
                        <div class="mv-card-header">
                            <h3 class="mv-card-title">{l s='Last 28 Days Sales' mod='multivendor'}</h3>
                        </div>
                        <div class="mv-card-body">
                            <canvas id="last28DaysChart" class="mv-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="mv-card mv-chart-card">
                        <div class="mv-card-header">
                            <h3 class="mv-card-title">{l s='Monthly Sales' mod='multivendor'}</h3>
                        </div>
                        <div class="mv-card-body">
                            <canvas id="salesChart" class="mv-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="mv-grid-row">
                    <div class="mv-grid-col">
                        <div class="mv-card">
                            <div class="mv-card-header">
                                <h3 class="mv-card-title">{l s='Recent Order Lines' mod='multivendor'}</h3>
                            </div>
                            <div class="mv-card-body">
                                {if $recent_order_lines}
                                    <div class="mv-table-container">
                                        <table class="mv-table">
                                            <thead>
                                                <tr>
                                                    <th>{l s='Order #' mod='multivendor'}</th>
                                                    <th>{l s='Product' mod='multivendor'}</th>
                                                    <th>{l s='Qty' mod='multivendor'}</th>
                                                    <th>{l s='Total' mod='multivendor'}</th>
                                                    <th>{l s='Status' mod='multivendor'}</th>
                                                    <th>{l s='Date' mod='multivendor'}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {foreach from=$recent_order_lines item=line}
                                                    <tr>
                                                        <td>
                                                            <a href="#" class="mv-link">
                                                                #{$line.order_reference}
                                                            </a>
                                                        </td>
                                                        <td class="mv-product-name">{$line.product_name|truncate:30:'...'}</td>
                                                        <td class="mv-text-center">{$line.product_quantity}</td>
                                                        <td>{Tools::displayPrice($line.vendor_amount)}</td>
                                                        <td>
                                                            <span class="mv-status-badge" style="background-color: {$line.status_color};">
                                                                {$line.line_status|default:'Pending'|capitalize}
                                                            </span>
                                                        </td>
                                                        <td>{$line.order_date|date_format:'%Y-%m-%d'}</td>
                                                    </tr>
                                                {/foreach}
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mv-text-center">
                                        <a href="{$vendor_orders_url}" class="mv-btn mv-btn-outline">
                                            {l s='View All Order Lines' mod='multivendor'}
                                        </a>
                                    </div>
                                {else}
                                    <p class="mv-empty-state">{l s='No recent order lines.' mod='multivendor'}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                                    </div>

            <div class="mv-grid-row">
                    <div class="mv-grid-col">
                        <div class="mv-card">
                            <div class="mv-card-header">
                                <h3 class="mv-card-title">{l s='Top Selling Products' mod='multivendor'}</h3>
                            </div>
                            <div class="mv-card-body">
                                {if $top_products}
                                    <div class="mv-table-container">
                                        <table class="mv-table">
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
                                    <p class="mv-empty-state">{l s='No sales data available.' mod='multivendor'}</p>
                                {/if}
                            </div>
                        </div>
                    </div>
              </div>

                </div>
            </main>
        </div>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Last 28 Days Chart
            var ctx28Days = document.getElementById('last28DaysChart').getContext('2d');
            var last28DaysData = {
                labels: [
                    {foreach from=$last_28_days_sales item=day}
                        '{$day.date}',
                    {/foreach}
                ],
                datasets: [{
                    label: '{l s='Daily Sales' mod='multivendor'}',
                    data: [
                        {foreach from=$last_28_days_sales item=day}
                            {$day.sales},
                        {/foreach}
                    ],
                    backgroundColor: 'rgba(52, 211, 153, 0.2)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            };
            
            var last28DaysChart = new Chart(ctx28Days, {
                type: 'line',
                data: last28DaysData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    return '{$currency_sign}' + value.toFixed(0);
                                }
                            }
                        }
                    }
                }
            });
            
            // Monthly Sales Chart
            var ctxMonthly = document.getElementById('salesChart').getContext('2d');
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
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 2,
                    tension: 0.4
                }]
            };
            
            var salesChart = new Chart(ctxMonthly, {
                type: 'line',
                data: monthlyData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
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