{*
* Vendor Orders Template with Custom CSS and AJAX
*}

{extends file='page.tpl'}

{block name='page_title'}
    {l s='My Order Lines' mod='multivendor'}
{/block}

{block name='page_content'}
    <div class="mv-dashboard">
        <div class="mv-container">
            <aside class="mv-sidebar">
                <div class="mv-card">
                    <div class="mv-card-body">
                        <nav class="mv-nav">
                           <nav class="mv-nav">
                            <a class="mv-nav-link" href="{$vendor_dashboard_url}">
                                <i class="mv-icon">📊</i>
                                <span>{l s='Dashboard' mod='multivendor'}</span>
                            </a>
                            <a class="mv-nav-link mv-nav-link-active" href="{$vendor_orders_url}">
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
                {* Order Summary Cards *}
                <div class="mv-stats-grid">
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Total Order Lines' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{$order_summary.total_lines}</h3>
                            <p class="mv-stat-description">{l s='All time' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s='Revenue (Last 28 Days)' mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{Tools::displayPrice($order_summary.total_revenue)}</h3>
                            <p class="mv-stat-description">{l s='Your earnings after commission' mod='multivendor'}</p>
                        </div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="mv-stat-content">
                            <h6 class="mv-stat-label">{l s="Today's Orders" mod='multivendor'}</h6>
                            <h3 class="mv-stat-value">{$order_summary.todays_orders}</h3>
                            <p class="mv-stat-description">{l s='New order lines' mod='multivendor'}</p>
                        </div>
                    </div>
                </div>

                {* Status Breakdown *}
                {if $order_summary.status_breakdown}
                    <div class="mv-card">
                        <div class="mv-card-header">
                            <h3 class="mv-card-title">{l s='Order Status Overview' mod='multivendor'}</h3>
                        </div>
                        <div class="mv-card-body">
                            <div class="mv-status-breakdown">
                                {foreach from=$order_summary.status_breakdown item=statusData}
                                    <div class="mv-status-item">
                                        <span class="mv-status-badge" 
                                              style="background-color: {$status_colors[$statusData.status]|default:'#777'};">
                                            {$statusData.status|capitalize} : {$statusData.count}
                                        </span>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                {/if}
                {* Orders Table *}
                <div class="mv-card">
                    <div class="mv-card-header">
                        <h3 class="mv-card-title">{l s='Order Lines' mod='multivendor'}</h3>
                        <div class="mv-export-buttons">
                            <button class="mv-btn mv-btn-export" onclick="exportTableToCSV()">
                                <i class="mv-icon">📥</i>
                                {l s='Export CSV' mod='multivendor'}
                            </button>
                           
                        </div>
                    </div>
                    <div class="mv-card-body">
                        {if $order_lines}
                            <div class="mv-table-container">
                                <table class="mv-table">
                                    <thead>
                                        <tr>
                                            <th>{l s='Reference' mod='multivendor'}</th>
                                            <th>{l s='Product' mod='multivendor'}</th>
                                            <th>{l s='Qty' mod='multivendor'}</th>
                                            <th>{l s='Total' mod='multivendor'}</th>
                                            <th>{l s='Status' mod='multivendor'}</th>
                                            <th>{l s='Date' mod='multivendor'}</th>
                                            <th>{l s='Actions' mod='multivendor'}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach from=$order_lines item=line}
                                            <tr>
                                                <td>
                                                    <a href="#" class="mv-link">
                                                        #{$line.order_reference}#{$line.id_order_detail}
                                                    </a>
                                                </td>
                                                <td class="mv-product-name">{$line.product_name|truncate:80:'...'}
                                                   <br> (SKU : {$line.product_reference})</td>
                                                <td class="mv-text-center">{$line.product_quantity}</td>
                                                <td>{($line.total_price_tax_incl - $line.commission_amount)|displayPrice}</td>
                                                <td>
                                                    {if isset($all_statuses[$line.line_status]) && !isset($vendor_statuses[$line.line_status])}
                                                        <span class="mv-status-badge" 
                                                            style="background-color: {$status_colors[$line.line_status]|default:'#777'};">
                                                            {$line.line_status|capitalize} 
                                                        </span>
                                                    {else}
                                                        <select class="mv-status-select order-line-status-select" 
                                                                id="status-select-{$line.id_order_detail}"
                                                                data-order-detail-id="{$line.id_order_detail}"
                                                                data-original-status="{$line.line_status|default:'Pending'}">
                                                            {foreach from=$vendor_statuses key=status_key item=status_label}
                                                                <option value="{$status_key}" 
                                                                        {if ($line.line_status|default:'Pending') == $status_key}selected{/if}
                                                                        style="background-color: {$status_colors[$status_key]}; color: white;">
                                                                    {$status_label|escape:'html':'UTF-8'|capitalize}
                                                                </option>
                                                            {/foreach}
                                                        </select>
                                                    {/if}
                                                </td>
                                                <td>{$line.order_date|date_format:'%Y-%m-%d'}</td>
                                                <td>
                                                    <button class="mv-btn-icon view-status-history" 
                                                            data-order-detail-id="{$line.id_order_detail}"
                                                            title="{l s='View History' mod='multivendor'}">
                                                        <i class="mv-icon">📜</i>
                                                    </button>
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
                                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => 1])}">
                                                    <span>«</span>
                                                </a>
                                            </li>
                                            <li class="mv-pagination-item">
                                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page-1])}">
                                                    <span>‹</span>
                                                </a>
                                            </li>
                                        {/if}
                                        
                                        {assign var=p_start value=max(1, $current_page-2)}
                                        {assign var=p_end value=min($pages_nb, $current_page+2)}
                                        
                                        {for $p=$p_start to $p_end}
                                            <li class="mv-pagination-item {if $p == $current_page}mv-pagination-active{/if}">
                                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $p])}">{$p}</a>
                                            </li>
                                        {/for}
                                        
                                        {if $current_page < $pages_nb}
                                            <li class="mv-pagination-item">
                                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page+1])}">
                                                    <span>›</span>
                                                </a>
                                            </li>
                                            <li class="mv-pagination-item">
                                                <a class="mv-pagination-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $pages_nb])}">
                                                    <span>»</span>
                                                </a>
                                            </li>
                                        {/if}
                                    </ul>
                                </nav>
                            {/if}
                        {else}
                            <p class="mv-empty-state">
                                {l s='No order lines found.' mod='multivendor'}
                            </p>
                        {/if}
                    </div>
                </div>
            </main>
        </div>
    </div>

    {* Status History Modal *}
    <div class="mv-modal" id="statusHistoryModal">
        <div class="mv-modal-backdrop" onclick="$('#statusHistoryModal').removeClass('mv-modal-open')"></div>
        <div class="mv-modal-content">
            <div class="mv-modal-header">
                <h5 class="mv-modal-title">{l s='Status History' mod='multivendor'}</h5>
                <button class="mv-modal-close" onclick="$('#statusHistoryModal').removeClass('mv-modal-open')">×</button>
            </div>
            <div class="mv-modal-body" id="statusHistoryContent">
                <!-- History will be loaded here -->
            </div>
        </div>
    </div>
{/block}