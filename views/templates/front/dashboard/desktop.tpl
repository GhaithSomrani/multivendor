{*
* Desktop Dashboard Template  
* views/templates/front/dashboard/desktop.tpl
*}

<div class="mv-charts-row">
    <div class="mv-card mv-chart-card">
        <div class="mv-card-header">
            <h3 class="mv-card-title">{l s='Ventes quotidiennes' mod='multivendor'} - {$filter_label}</h3>
        </div>
        <div class="mv-card-body">
            <canvas id="filteredDaysChart" class="mv-chart"></canvas>
        </div>
    </div>
    
    <div class="mv-card mv-chart-card">
        <div class="mv-card-header">
            <h3 class="mv-card-title">{l s='Ventes mensuelles' mod='multivendor'}</h3>
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
                <h3 class="mv-card-title">{l s='Lignes de commande récentes' mod='multivendor'}</h3>
            </div>
            <div class="mv-card-body">
                {if $recent_order_lines}
                    <div class="mv-table-container">
                        <table class="mv-table">
                            <thead>
                                <tr>
                                    <th>{l s='Commande #' mod='multivendor'}</th>
                                    <th>{l s='Produit' mod='multivendor'}</th>
                                    <th>{l s='Qté' mod='multivendor'}</th>
                                    <th>{l s='Total' mod='multivendor'}</th>
                                    <th>{l s='Statut' mod='multivendor'}</th>
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
                                        <td>{$line.vendor_amount|number_format:2} TND</td>
                                        <td>
                                            <span class="mv-status-badge" style="background-color: {$line.status_color};">
                                                {$line.line_status|default:'En attente'|capitalize}
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
                            {l s='Voir toutes les lignes de commande' mod='multivendor'}
                        </a>
                    </div>
                {else}
                    <p class="mv-empty-state">{l s='Aucune ligne de commande récente.' mod='multivendor'}</p>
                {/if}
            </div>
        </div>
    </div>
</div>

<div class="mv-grid-row">
    <div class="mv-grid-col">
        <div class="mv-card">
            <div class="mv-card-header">
                <h3 class="mv-card-title">{l s='Produits les plus vendus' mod='multivendor'} - {$filter_label}</h3>
            </div>
            <div class="mv-card-body">
                {if $top_products}
                    <div class="mv-table-container">
                        <table class="mv-table">
                            <thead>
                                <tr>
                                    <th>{l s='Produit' mod='multivendor'}</th>
                                    <th>{l s='Quantité' mod='multivendor'}</th>
                                    <th>{l s='Ventes' mod='multivendor'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$top_products item=product}
                                    <tr>
                                        <td>{$product.product_name}</td>
                                        <td>{$product.quantity_sold}</td>
                                        <td>{$product.total_sales|number_format:2} TND</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {else}
                    <p class="mv-empty-state">{l s='Aucune donnée de vente disponible.' mod='multivendor'}</p>
                {/if}
            </div>
        </div>
    </div>
</div>