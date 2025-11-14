{*
* Mobile Orders Content - Card Layout
*}

{* Mobile Status Filter Dropdown *}
{if $order_summary.status_breakdown}
    <div class="mv-card mv-mobile-filter-card">
        <div class="mv-card-header">
            <h5 class="mv-card-title">{l s='Filtrer par statut' mod='multivendor'}</h5>
        </div>
        <div class="mv-card-body">
            <select class="mv-filter-dropdown-mobile" onchange="window.location.href=this.value">
                <option value="{$link->getModuleLink('multivendor', 'orders')}" {if $filter_status == 'all'}selected{/if}>
                    {l s='Tout' mod='multivendor'} ({$order_summary.total_lines})
                </option>
                {foreach from=$order_summary.status_breakdown item=statusData}
                    {if  $statusData.id_order_line_status_type != 26 }
                        <option
                            value="{$link->getModuleLink('multivendor', 'orders', ['status' => $statusData.id_order_line_status_type])}"
                            {if $filter_status == $statusData.id_order_line_status_type}selected{/if}>
                            {$statusData.status|capitalize} ({$statusData.count})
                        </option>
                    {elseif  $statusData.id_order_line_status_type == 26 && $id_vendor == 7}
                        <option
                            value="{$link->getModuleLink('multivendor', 'orders', ['status' => $statusData.id_order_line_status_type])}"
                            {if $filter_status == $statusData.id_order_line_status_type}selected{/if}>
                            {$statusData.status|capitalize} ({$statusData.count})
                        </option>
                    {/if}

                {/foreach}
            </select>
        </div>
    </div>
{/if}




{* Orders Cards *}
<div class="mv-card mv-mobile-orders-card">
    <div class="mv-card-header">
        <h5 class="mv-card-title">{l s='Lignes de commande' mod='multivendor'}</h5>
    </div>
    <div class="mv-card-body">
        {if $order_lines}
            <div class="mv-mobile-orders-grid">
                {foreach from=$order_lines item=line}
                    <div class="mv-mobile-order-item" data-id="{$line.id_order_detail}"
                        data-status="{$line.line_status|default:'En attente'|lower}" data-product-mpn="{$line.product_mpn}"
                        data-commission-action="{if isset($line.commission_action)}{$line.commission_action}{else}none{/if}">

                        {* Main Content *}
                        <div class="mv-main-content">
                            <div class="mv-mobile-order-header">
                                <div class="mv-mobile-order-ref">
                                    <input type="checkbox" class="mv-mobile-checkbox" data-id="{$line.id_order_detail}"
                                        style="display: none;">
                                    <a href="#" class="mv-mobile-link">
                                        <strong>{$line.id_order} </strong><small>{$line.id_order_detail}</small>
                                        <span class="view-status-history" data-order-detail-id="{$line.id_order_detail}"
                                            title="{l s='Voir l\'historique' mod='multivendor'}">
                                            <i class="mv-icon">📜</i>
                                        </span>
                                    </a>
                                </div>

                                <div class="mv-mobile-order-date">
                                    {$line.order_date|date_format:'%d/%m/%Y'}
                                </div>

                                {* Slide Arrow Button *}
                                <button class="mv-slide-arrow" onclick="toggleSlide(this)">
                                    ▶ Détail </button>
                            </div>

                            <div class="mv-mobile-order-content">
                                <div class="mv-mobile-product-info">
                                    {assign var="product_image" value=OrderHelper::getProductImageLink($line.product_id, $line.product_attribute_id,'medium_default')}
                                    {assign var="large_image" value=OrderHelper::getProductImageLink($line.product_id, $line.product_attribute_id, 'medium_default')}

                                    <div class="zoom-container">
                                        <img src="{$product_image}" data-zoom="{$large_image}"
                                            alt="{$line.product_name|escape:'html':'UTF-8'}"
                                            class="zoomable-image mv-product-image">
                                    </div>

                                    <span class="mv-mobile-product-name">{$line.product_name}
                                        {if $line.product_mpn} <p class="mv-mobile-product-sku">Code barre:
                                                {$line.product_mpn}
                                            </p>
                                        {/if}

                                        {assign var="product_link" value=VendorHelper::getProductPubliclink($line.product_id, $line.product_attribute_id)}
                                        <i> <a href="{$product_link}">🔗</a></i>
                                    </span>
                                </div>

                                <div class="mv-mobile-order-details">

                                    <div class="mv-mobile-detail-row">
                                        <span class="mv-mobile-label">
                                            <small>{l s='Prix Public:' mod='multivendor'}</small></span>
                                        <span class="mv-mobile-value mv-mobile-amount"> {$line.product_price|number_format:3}
                                            TND</span>
                                    </div>
                                    <div class="mv-mobile-detail-row ">
                                        <span class="mv-mobile-label {if $line.product_quantity > 1 } flash-fast {/if}"> <small>
                                                {l s='Quantité' mod='multivendor'} </small></span>
                                        <span
                                            class="mv-mobile-value {if $line.product_quantity > 1 } flash-fast {/if}">{$line.product_quantity}</span>
                                    </div>
                                    <div class="mv-mobile-detail-row">
                                        <span class="mv-mobile-label"> <small>{l s='Total' mod='multivendor'} </small></span>
                                        <span class="mv-mobile-value mv-mobile-amount">{($line.vendor_amount)|number_format:3}
                                            TND</span>
                                    </div>
                                </div>

                                <div class="mv-mobile-status-section">
                                    <div class="mv-mobile-current-status">
                                        {if $line.status_type_id == $first_status }
                                            <small style="text-align: center; display: block;"> Disponible ?</small>
                                            <div class="mv-quick-action">
                                                <button class="mv-status-btn" style="color: black" id="outofstock"
                                                    onclick="openOutOfStockModal({$line.id_order_detail})">🚫 Non</button>
                                                {if $id_vendor == 7}
                                                    {assign var="nextstatut" value=26 }
                                                {else}
                                                    {assign var="nextstatut" value=$available_status_vendor->id }
                                                {/if}
                                                <button class="mv-status-btn" style="color: black" id="available-product"
                                                    onclick="mkAvailble({$line.id_order_detail},  {$nextstatut})">✅
                                                    Oui</button>
                                            </div>
                                        {elseif $line.status_type_id == 26 && $id_vendor == 7}
                                            <small style="text-align: center; display: block;"> En Stock ?</small>
                                            <div class="mv-quick-action">
                                                <button class="mv-status-btn" style="color: black" id="outofstock"
                                                    onclick="openOutOfStockModal({$line.id_order_detail})">🚫 Non</button>
                                                {assign var="nextstatut" value=6 }
                                                <button class="mv-status-btn" style="color: black" id="available-product"
                                                    onclick="mkAvailble({$line.id_order_detail},  {$nextstatut})">✅
                                                    Oui</button>
                                            </div>
                                        {else}
                                            <span class="mv-status-badge"
                                                style="background-color: {$status_colors[$line.line_status]|default:'#777'};">
                                                {$line.line_status|capitalize}
                                            </span>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {* Hidden Content (slides in from right) *}
                        <div class="mv-hidden-content">
                            {* Return Button *}
                            <div style="display: flex;justify-content: space-between;align-items: center;">
                                <h6>Informations Supplémentaires</h6>
                                <button class="mv-slide-arrow" onclick="toggleSlide(this)">◀ Retour</button>
                            </div>
                            <div class="mv-detail-item">
                                <span class="mv-detail-label">SKU:</span>
                                <span class="mv-detail-value">{$line.product_reference|default:'-'}</span>
                            </div>



                            {assign var="oldcommision" value="{(VendorCommission::getCommissionRate($id_vendor))}"
                            }

                            {assign var='oldPrice' value="{$line.vendor_amount/(1-($oldcommision/100)) }"}
                            <div class="mv-detail-item">
                                <span class="mv-detail-label">Action</span>
                                <span class="mv-detail-value">
                                    {(($line.product_price * $line.product_quantity)  - $oldPrice)|number_format:3}
                                    <small>TND</small>
                                    <small> <strong>
                                            {((($line.product_price * $line.product_quantity)  - $oldPrice)/ ($line.product_price * $line.product_quantity))*100|string_format:'%.2f'}%
                                        </strong></small>
                                </span>
                            </div>
                            <div class="mv-detail-item">
                                <span class="mv-detail-label">Commission</span>
                                <span class="mv-detail-value">
                                    {($oldPrice*($oldcommision/100))|number_format:3} <small>TND</small>
                                    <small> <strong>{$oldcommision|string_format:'%.2f'}% </strong></small>
                                </span>
                            </div>

                            {assign var="rm" value=Manifest::getManifestByOrderDetailAndType($line.id_order_detail, 1)}
                            <div class="mv-detail-item">
                                <span class="mv-detail-label">Bon de Ramassage</span>
                                <span class="mv-detail-value">
                                    <span class="mv-detail-badge ">{if $rm}{$rm.reference}{else}-{/if}</span>

                                </span>
                            </div>

                            {assign var="rt" value=Manifest::getManifestByOrderDetailAndType($line.id_order_detail, 2)}
                            <div class="mv-detail-item">
                                <span class="mv-detail-label">Bon de Retour</span>
                                <span class="mv-detail-value">
                                    <span class="mv-detail-badge ">{if $rt}{$rt.reference}{else}-{/if}</span>

                                </span>
                            </div>

                            {assign var="pay" value=Vendorpayment::getByOrderDetailAndType($line.id_order_detail, 'commission')}
                            <div class="mv-detail-item">
                                <span class="mv-detail-label">Paiement</span>
                                <span class="mv-detail-value">
                                    <span class="mv-detail-badge ">{if $pay && $pay->id}{$pay->reference}{else}-{/if}</span>

                                </span>
                            </div>

                            {assign var="refund" value=Vendorpayment::getByOrderDetailAndType($line.id_order_detail, 'refund')}
                            <div class="mv-detail-item">
                                <span class="mv-detail-label">Paiement de Retour</span>
                                <span class="mv-detail-value">
                                    <span
                                        class="mv-detail-badge ">{if $refund && $refund->id}{$refund->reference}{else}-{/if}</span>

                                </span>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>

            {* Mobile Pagination *}
            {if $pages_nb > 1}
                <div class="mv-mobile-pagination">
                    <div class="mv-mobile-pagination-info">
                        {l s='Page' mod='multivendor'} {$current_page} {l s='sur' mod='multivendor'} {$pages_nb}
                    </div>
                    <div class="mv-mobile-pagination-controls">
                        {if $current_page > 1}
                            <a class="mv-mobile-pagination-btn"
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page-1, 'status' => $filter_status])}">
                                ‹ {l s='Précédent' mod='multivendor'}
                            </a>
                        {/if}
                        {if $current_page < $pages_nb}
                            <a class="mv-mobile-pagination-btn"
                                href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page+1, 'status' => $filter_status])}">
                                {l s='Suivant' mod='multivendor'} ›
                            </a>
                        {/if}
                    </div>
                </div>
            {/if}
        {else}
            <div class="mv-mobile-empty-state">
                <div class="mv-mobile-empty-icon">📦</div>
                <p>{l s='Aucune ligne de commande trouvée.' mod='multivendor'}</p>
            </div>
        {/if}
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.zoomable-image').forEach(function(img) {
            new Drift(img, {
                inlineOffsety: 200,
                zoomFactor: 2,
            });
        });
    });

    function toggleSlide(button) {
        const orderItem = button.closest('.mv-mobile-order-item');
        orderItem.classList.toggle('slided');
    }
</script>

<div class="mobile">
    {include file="module:multivendor/views/templates/front/orders/_outofstock_modal.tpl"}
</div>