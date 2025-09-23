{*
* Vendor Dashboard Template with Custom CSS and Date Filtering
*}

{extends file='page.tpl'}

{block name='page_title'}
    {l s='Tableau de bord vendeur' mod='multivendor'}
{/block}

{block name='page_content'}
    <div class="mv-dashboard">
        {if $status != 1}
            <div class="mv-alert mv-alert-warning">
                {if $status == 0}
                    {l s='Votre compte vendeur est en attente d\'approbation.' mod='multivendor'}
                {else}
                    {l s='Votre compte vendeur a été rejeté.' mod='multivendor'}
                {/if}
            </div>
        {/if}

        <div class="mv-container">
            <aside class="mv-sidebar">
                <div class="mv-card">
                    <div class="mv-card-body">
                        <nav class="mv-nav">
                            <a class="mv-nav-link mv-nav-link-active" href="{$vendor_dashboard_url}">
                                <i class="mv-icon">📊</i>
                                <span>{l s='Tableau de bord' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link" href="{$vendor_orders_url}">
                                <i class="mv-icon">🛒</i>
                                <span>{l s='Commandes' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link" href="{$vendor_manifest_url}">
                                <i class="mv-icon">📋</i>
                                <span>{l s='Manifestes' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link" href="{$vendor_commissions_url}">
                                <i class="mv-icon">💰</i>
                                <span>{l s='Commissions' mod='multivendor'}</span>
                            </a>
                        </nav>
                    </div>
                </div>
            </aside>

            <main class="mv-main-content">
                {* Date Filter Form *}
                <div class="mv-card mv-date-filter-card">
                    <div class="mv-card-header">
                        <h3 class="mv-card-title">{l s='Filtre de données' mod='multivendor'}</h3>
                        <span class="mv-active-filter">{l s='Filtre actuel :' mod='multivendor'} {$filter_label}</span>
                    </div>
                    <div class="mv-card-body">
                        <form action="{$vendor_dashboard_url}" method="post" class="mv-date-filter-form">
                            <div class="mv-date-filter-options">
                                <div class="mv-date-option">
                                    <input type="radio" id="filter-this-month" name="date_filter_type" value="this_month"
                                        {if $date_filter_type == 'this_month'}checked{/if}>
                                    <label for="filter-this-month">{l s='Ce mois' mod='multivendor'}</label>
                                </div>

                                <div class="mv-date-option">
                                    <input type="radio" id="filter-custom" name="date_filter_type" value="custom"
                                        {if $date_filter_type == 'custom'}checked{/if}>
                                    <label for="filter-custom">{l s='Plage personnalisée' mod='multivendor'}</label>
                                </div>
                            </div>

                            <div class="mv-custom-date-range" id="customDateRange"
                                style="{if $date_filter_type != 'custom'}display: none;{/if}">
                                <div class="mv-date-inputs">
                                    <div class="mv-date-field">
                                        <label for="custom-start-date">{l s='Du' mod='multivendor'}</label>
                                        <input type="date" id="custom-start-date" name="custom_start_date"
                                            max="{$current_date}" value="{$start_date|default:$current_date}">
                                    </div>
                                    <div class="mv-date-field">
                                        <label for="custom-end-date">{l s='Au' mod='multivendor'}</label>
                                        <input type="date" id="custom-end-date" name="custom_end_date" max="{$current_date}"
                                            value="{$end_date|default:$current_date}">
                                    </div>
                                </div>
                            </div>

                            <div class="mv-filter-actions">
                                <button type="submit" name="submitDateFilter" class="mv-btn mv-btn-primary">
                                    <i class="mv-icon">🔍</i> {l s='Appliquer le filtre' mod='multivendor'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mv-stats-grid">
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Quantité de commandes' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{$indicators.order_quantity}</h3>
                            <p class="mv-stat-description">{$filter_label}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='CA Total' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{$indicators.total_ca|number_format:3} TND</h3>
                            <p class="mv-stat-description">{$filter_label}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Produits par référence' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{$indicators.total_products_by_ref}</h3>
                            <p class="mv-stat-description">{$filter_label}</p>
                        </div>
                    </div>
                </div>

                {* Mobile/Desktop Detection for Content *}
                {if Context::getContext()->isMobile() == 1}
                    {* Load Mobile Dashboard Template *}
                    {include file="module:multivendor/views/templates/front/dashboard/mobile.tpl"}
                {else}
                    {* Load Desktop Dashboard Template *}
                    {include file="module:multivendor/views/templates/front/dashboard/desktop.tpl"}
                {/if}
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Date filter toggle for custom date range
            const customRadio = document.getElementById('filter-custom');
            const customDateRange = document.getElementById('customDateRange');

            function toggleCustomDateRange() {
                if (customRadio.checked) {
                    customDateRange.style.display = 'block';
                } else {
                    customDateRange.style.display = 'none';
                }
            }

            customRadio.addEventListener('change', toggleCustomDateRange);
            document.querySelectorAll('input[name="date_filter_type"]').forEach(function(radio) {
                radio.addEventListener('change', toggleCustomDateRange);
            });

            // Initialize custom date range display
            toggleCustomDateRange();

            // Daily Filtered Sales Chart
            var ctxFilteredDays = document.getElementById('filteredDaysChart');
            if (ctxFilteredDays) {
                var filteredDaysData = {
                    labels: [
                        {foreach from=$filtered_daily_sales item=day}
                            '{$day.date}',
                        {/foreach}
                    ],
                    datasets: [{
                        label: '{l s='Ventes quotidiennes' mod='multivendor'}',
                        data: [
                            {foreach from=$filtered_daily_sales item=day}
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

                var filteredDaysChart = new Chart(ctxFilteredDays.getContext('2d'), {
                    type: 'line',
                    data: filteredDaysData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '{$currency_sign}' + context.raw.toFixed(2);
                                    }
                                }
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
            }

            // Monthly Sales Chart - Bar Chart 
            var ctxMonthly = document.getElementById('salesChart');
            if (ctxMonthly) {
                var monthlyData = {
                    labels: [
                        {foreach from=$monthly_sales item=month}
                            '{$month.month}',
                        {/foreach}
                    ],
                    datasets: [{
                        label: '{l s='Ventes mensuelles' mod='multivendor'}',
                        data: [
                            {foreach from=$monthly_sales item=month}
                                {$month.sales},
                            {/foreach}
                        ],
                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }]
                };

                var salesChart = new Chart(ctxMonthly.getContext('2d'), {
                    type: 'bar',
                    data: monthlyData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '{$currency_sign}' + context.raw.toFixed(2);
                                    }
                                }
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
            }
        });
    </script>
{/block}