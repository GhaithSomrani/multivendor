{*
* Vendor Manage Orders Template
*}

{extends file='page.tpl'}

{block name='page_title'}
    {l s='Manage Orders' mod='multivendor'}
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
                            <a class="mv-nav-link mv-nav-link-active" href="{$vendor_manage_orders_url}">
                                <i class="mv-icon">📦</i>
                                <span>{l s='Manage Orders' mod='multivendor'}</span>
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
                {* Header Actions *}
                <div class="mv-actions-bar">
                    <button class="mv-btn mv-btn-primary" id="printSelectedAwb" disabled>
                        <i class="mv-icon">🖨️</i>
                        {l s='Print Selected AWBs' mod='multivendor'}
                    </button>
                    <button class="mv-btn mv-btn-secondary" id="selectAll">
                        <i class="mv-icon">☑️</i>
                        {l s='Select All Ready' mod='multivendor'}
                    </button>
                    <button class="mv-btn mv-btn-secondary" id="deselectAll">
                        <i class="mv-icon">⬜</i>
                        {l s='Deselect All' mod='multivendor'}
                    </button>
                </div>

                {* Order Panels *}
                <div class="mv-order-panels">
                    
                    {* Panel 1: Pending Orders *}
                    <div class="mv-order-panel" id="pendingPanel">
                        <div class="mv-panel-header">
                            <h3 class="mv-panel-title">
                                <i class="mv-icon">⏳</i>
                                {l s='Pending Orders' mod='multivendor'}
                                <span class="mv-count">{$order_lines_by_status.pending|count}</span>
                            </h3>
                        </div>
                        <div class="mv-panel-body">
                            <div class="mv-order-zone" data-status="pending">
                                {foreach from=$order_lines_by_status.pending item=line}
                                    <div class="mv-order-card draggable" data-order-detail-id="{$line.id_order_detail}">
                                        <div class="mv-order-header">
                                            <span class="mv-order-ref">#{$line.order_reference}</span>
                                            <span class="mv-order-date">{$line.order_date|date_format:'%Y-%m-%d'}</span>
                                        </div>
                                        <div class="mv-order-body">
                                            <div class="mv-product-info">
                                                <strong>{$line.product_name|truncate:60:'...'}</strong>
                                                <div class="mv-product-meta">
                                                    SKU: {$line.product_reference} | Qty: {$line.product_quantity}
                                                </div>
                                            </div>
                                            
                                            <div class="mv-order-total">
                                                {$line.vendor_amount|displayPrice}
                                            </div>
                                        </div>
                                        <div class="mv-order-status" style="background-color: {$line.status_color}">
                                            {$line.line_status|capitalize}
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>

                    {* Panel 2: Ready to Ship Orders *}
                    <div class="mv-order-panel" id="readyPanel">
                        <div class="mv-panel-header">
                            <h3 class="mv-panel-title">
                                <i class="mv-icon">✅</i>
                                {l s='Ready to Ship' mod='multivendor'}
                                <span class="mv-count">{$order_lines_by_status.ready|count}</span>
                            </h3>
                        </div>
                        <div class="mv-panel-body">
                            <div class="mv-order-zone" data-status="ready">
                                {foreach from=$order_lines_by_status.ready item=line}
                                    <div class="mv-order-card draggable" data-order-detail-id="{$line.id_order_detail}">
                                        <div class="mv-order-select">
                                            <input type="checkbox" class="mv-order-checkbox" value="{$line.id_order_detail}">
                                        </div>
                                        <div class="mv-order-header">
                                            <span class="mv-order-ref">#{$line.order_reference}</span>
                                            <span class="mv-order-date">{$line.order_date|date_format:'%Y-%m-%d'}</span>
                                        </div>
                                        <div class="mv-order-body">
                                            <div class="mv-product-info">
                                                <strong>{$line.product_name|truncate:60:'...'}</strong>
                                                <div class="mv-product-meta">
                                                    SKU: {$line.product_reference} | Qty: {$line.product_quantity}
                                                </div>
                                            </div>

                                            <div class="mv-order-total">
                                                {$line.vendor_amount|displayPrice}
                                            </div>
                                        </div>
                                        <div class="mv-order-actions">
                                            <button class="mv-btn-icon print-awb" data-order-detail-id="{$line.id_order_detail}">
                                                <i class="mv-icon">🖨️</i>
                                            </button>
                                        </div>
                                        <div class="mv-order-status" style="background-color: {$line.status_color}">
                                            {$line.line_status|capitalize}
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>

                    {* Panel 3: Cancelled/Refunded Orders *}
                    <div class="mv-order-panel" id="noCommissionPanel">
                        <div class="mv-panel-header">
                            <h3 class="mv-panel-title">
                                <i class="mv-icon">❌</i>
                                {l s='Cancelled/Refunded Orders' mod='multivendor'}
                                <span class="mv-count">{$order_lines_by_status.no_commission|count}</span>
                            </h3>
                        </div>
                        <div class="mv-panel-body">
                            <div class="mv-order-zone" data-status="no_commission">
                                {foreach from=$order_lines_by_status.no_commission item=line}
                                    <div class="mv-order-card" data-order-detail-id="{$line.id_order_detail}">
                                        <div class="mv-order-header">
                                            <span class="mv-order-ref">#{$line.order_reference}</span>
                                            <span class="mv-order-date">{$line.order_date|date_format:'%Y-%m-%d'}</span>
                                        </div>
                                        <div class="mv-order-body">
                                            <div class="mv-product-info">
                                                <strong>{$line.product_name|truncate:60:'...'}</strong>
                                                <div class="mv-product-meta">
                                                    SKU: {$line.product_reference} | Qty: {$line.product_quantity}
                                                </div>
                                            </div>
                                            <div class="mv-order-amounts">
                                                <div class="mv-vendor-amount">
                                                    <span class="mv-label">{l s='Vendor Amount:' mod='multivendor'}</span>
                                                    <span class="mv-value">{$line.vendor_amount|displayPrice}</span>
                                                </div>
                                                <div class="mv-commission-info">
                                                    <span class="mv-label">{l s='Commission:' mod='multivendor'}</span>
                                                    <span class="mv-value 
                                                        {if $line.commission_action == 'refund'}mv-refund{else}mv-cancelled{/if}">
                                                        {if $line.commission_action == 'refund'}
                                                            -{$line.commission_amount|displayPrice}
                                                        {else}
                                                            {l s='Cancelled' mod='multivendor'}
                                                        {/if}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mv-order-status" style="background-color: {$line.status_color}">
                                            {$line.line_status|capitalize}
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
{/block}