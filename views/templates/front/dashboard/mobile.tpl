{*
* Mobile Dashboard Template
* views/templates/front/dashboard/mobile.tpl
*}

<div class="mv-mobile-dashboard">
    {* Mobile Charts *}
    <div class="mv-chart-card">
        <div class="mv-card-header">
            <h3 class="mv-card-title">{l s='Ventes quotidiennes' mod='multivendor'} - {$filter_label}</h3>
        </div>
        <div class="mv-card-body">
            <canvas id="filteredDaysChart" class="mv-chart"></canvas>
        </div>
    </div>

    {* Mobile Recent Orders *}
    <div class="mv-card">
        <div class="mv-card-header">
            <h3 class="mv-card-title">{l s='Lignes de commande récentes' mod='multivendor'}</h3>
        </div>
        <div class="mv-card-body">
            {if $recent_order_lines}
                <div class="mv-mobile-order-list">
                    {foreach from=$recent_order_lines item=line}
                        <div class="mv-mobile-order-item">
                            <div class="mv-mobile-order-header">
                                <span class="mv-mobile-order-ref">#{$line.order_reference}</span>
                                <span class="mv-mobile-order-amount">{$line.vendor_amount|number_format:3} TND</span>
                            </div>
                            <div class="mv-mobile-order-product">{$line.product_name|truncate:30:'...'}</div>
                            <div class="mv-mobile-order-footer">
                                <span class="mv-mobile-order-qty">Qté: {$line.product_quantity}</span>
                                <span class="mv-mobile-order-status" style="background-color: {$line.status_color};">
                                    {$line.line_status|default:'En attente'|capitalize}
                                </span>
                                <span class="mv-mobile-order-date">{$line.order_date|date_format:'%Y-%m-%d'}</span>
                            </div>
                        </div>
                    {/foreach}
                </div>
                <div class="mv-text-center">
                    <a href="{$link->getModuleLink('multivendor', 'orders')}" class="mv-btn mv-btn-outline">
                        {l s='Voir toutes les lignes de commande' mod='multivendor'}
                    </a>
                </div>
            {else}
                <p class="mv-empty-state">{l s='Aucune ligne de commande récente.' mod='multivendor'}</p>
            {/if}
        </div>
    </div>

    {* Mobile Top Products *}
    <div class="mv-card">
        <div class="mv-card-header">
            <h3 class="mv-card-title">{l s='Produits les plus vendus' mod='multivendor'} - {$filter_label}</h3>
        </div>
        <div class="mv-card-body">
            {if $top_products}
                <div class="mv-mobile-product-list">
                    {foreach from=$top_products item=product}
                        <div class="mv-mobile-product-item">
                            <div class="mv-mobile-product-name">{$product.product_name}</div>
                            <div class="mv-mobile-product-details">
                                <span class="mv-mobile-product-qty">{$product.quantity_sold} vendus</span>
                                <span class="mv-mobile-product-sales">{$product.total_sales|number_format:3} TND</span>
                            </div>
                        </div>
                    {/foreach}
                </div>
            {else}
                <p class="mv-empty-state">{l s='Aucune donnée de vente disponible.' mod='multivendor'}</p>
            {/if}
        </div>
    </div>
</div>

<style>

</style>