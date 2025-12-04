{*
* Mobile Commissions Template
* views/templates/front/commissions/mobile.tpl
*}

<div class="mv-mobile-commissions">
    {* Commission Transactions Card *}
    <div class="mv-card mv-mobile-orders-card">
        <div class="mv-card-header">
            <h5 class="mv-card-title">{l s='Détails des commandes' mod='multivendor'}</h5>
            {if $total_order_details > 0}
                <div class="mv-count-badge">
                    <span>{$total_order_details}
                        {if $total_order_details > 1}{l s='commandes' mod='multivendor'}{else}{l s='commande' mod='multivendor'}{/if}</span>
                </div>
            {/if}
        </div>
        <div class="mv-card-body">
            {if $order_details}
                <div class="mv-mobile-orders-grid">
                    {foreach from=$order_details item=detail}
                        <div class="mv-mobile-order-item" data-id="{$detail.id_order_detail}">

                            {* Main Content *}
                            <div class="mv-main-content">
                                <div class="mv-mobile-order-header">
                                    <div class="mv-mobile-order-ref">
                                        <a href="#" class="mv-mobile-link">
                                            <strong>#{$detail.id_order}</strong> <small>{$detail.id_order_detail}</small>
                                        </a>
                                    </div>

                                    <div class="mv-mobile-order-date">
                                        {$detail.order_date|date_format:'%d/%m/%Y'}
                                    </div>

                                    {* Slide Arrow Button *}
                                    <button class="mv-slide-arrow" onclick="toggleSlide(this)">
                                        ▶ Détail
                                    </button>
                                </div>

                                <div class="mv-mobile-order-content">
                                    <div class="mv-mobile-product-info">
                                        {assign var="product_image" value=OrderHelper::getProductImageLink($detail.product_id, $detail.product_attribute_id, 'medium_default')}
                                        {assign var="large_image" value=OrderHelper::getProductImageLink($detail.product_id, $detail.product_attribute_id, 'large_default')}

                                        <div class="zoom-container">
                                            <img src="{$product_image}" data-large="{$large_image}"
                                                alt="{$detail.product_name|escape:'html':'UTF-8'}"
                                                class="mv-product-image mv-mobile-clickable-image"
                                                onclick="openImageModal(this)">
                                        </div>

                                        <span class="mv-mobile-product-name">{$detail.product_name}
                                            {if $detail.product_mpn}
                                                <p class="mv-mobile-product-sku">Code barre: {$detail.product_mpn}</p>
                                            {/if}

                                            {assign var="product_link" value=VendorHelper::getProductPubliclink($detail.product_id, $detail.product_attribute_id)}
                                            <i><a href="{$product_link}">🔗</a></i>
                                        </span>
                                    </div>

                                    <div class="mv-mobile-order-details">
                                        <div class="mv-mobile-detail-row">
                                            <span class="mv-mobile-label">
                                                <small>{l s='Prix Public:' mod='multivendor'}</small></span>
                                            <span class="mv-mobile-value mv-mobile-amount">
                                                {$detail.product_price|number_format:3} TND</span>
                                        </div>
                                        <div class="mv-mobile-detail-row">
                                            <span class="mv-mobile-label {if $detail.product_quantity > 1}flash-fast{/if}">
                                                <small>{l s='Quantité' mod='multivendor'}</small></span>
                                            <span
                                                class="mv-mobile-value {if $detail.product_quantity > 1}flash-fast{/if}">{$detail.product_quantity}</span>
                                        </div>
                                        <div class="mv-mobile-detail-row">
                                            <span class="mv-mobile-label">
                                                <small>{l s='Montant' mod='multivendor'}</small></span>
                                            <span class="mv-mobile-value mv-mobile-amount">
                                                {$detail.vendor_amount|number_format:3} TND</span>
                                        </div>
                                    </div>

                                    <div class="mv-mobile-status-section">
                                        <div class="mv-mobile-current-status">
                                            <span class="mv-status-badge" style="background-color: {$detail.status_color};">
                                                {$detail.line_status|capitalize}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {* Hidden Content (slides in from right) *}
                            <div class="mv-hidden-content">
                                {* Return Button *}
                                <div
                                    style="display: flex;justify-content: space-between;align-items: center; margin-bottom: 15px;">
                                    <h6 style="margin: 0;">Transactions</h6>
                                    <button class="mv-slide-arrow" onclick="toggleSlide(this)">◀ Retour</button>
                                </div>

                                {* Transaction Section *}
                                <div class="mv-transactions-section">
                                    {if $detail.transactions}
                                        {foreach from=$detail.transactions item=trans}
                                            <div class="mv-mobile-transaction-box"
                                                style="width: 100%; padding: 12px; margin-bottom: 10px; background: {if $trans.transaction_type == 'commission'}#ecfdf5{else}#fef2f2{/if}; border-radius: 8px; border-left: 3px solid {if $trans.transaction_type == 'commission'}#10b981{else}#ef4444{/if};">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                                    <span style="font-weight: 600; font-size: 14px;">
                                                        {if $trans.transaction_type == 'commission'}Commission{else}Remboursement{/if}
                                                    </span>
                                                    <span
                                                        style="font-weight: 700; font-size: 14px; color: {if $trans.transaction_type == 'commission'}#10b981{else}#ef4444{/if};">
                                                        {$trans.vendor_amount|number_format:3}
                                                        TND
                                                    </span>
                                                </div>
                                                <div style="font-size: 12px; color: #666;">
                                                    <div
                                                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                                        <span
                                                            style="color: {if $trans.status == 'paid'}#10b981{else}#f59e0b{/if}; font-weight: 600;">
                                                            {if $trans.status == 'paid'}
                                                                {if $trans.transaction_type == 'refund'}
                                                                    Remboursé
                                                                {else}
                                                                    Payé
                                                                {/if}
                                                            {else}
                                                                En cours
                                                            {/if}
                                                        </span>
                                                        <span style="font-size: 11px; color: #999;">
                                                            <small> date de valitaion :</small>
                                                            {$trans.transaction_date|date_format:'%d/%m/%Y'}
                                                        </span>
                                                    </div>
                                                    {if $trans.payment_reference}
                                                        <div
                                                            style="margin-top: 5px; padding: 6px; background: rgba(99, 102, 241, 0.1); border-radius: 4px; font-size: 11px;">
                                                            <strong>Paiement:</strong> {$trans.payment_reference}
                                                            <br>
                                                            <span style="color: #999;">

                                                                <small>Date de paiement :</small>

                                                                {$trans.payment_date|date_format:'%d/%m/%Y'}</span>
                                                        </div>
                                                    {/if}
                                                </div>
                                            </div>
                                        {/foreach}
                                    {else}
                                        <div style="text-align: center; padding: 30px; color: #999;">
                                            <div style="font-size: 40px; margin-bottom: 10px;">💰</div>
                                            <p style="font-style: italic;">Aucune transaction disponible</p>
                                        </div>
                                    {/if}
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
                                    href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $current_page-1, 'view' => 'transactions'])}">
                                    ‹ {l s='Précédent' mod='multivendor'}
                                </a>
                            {/if}
                            {if $current_page < $pages_nb}
                                <a class="mv-mobile-pagination-btn"
                                    href="{$link->getModuleLink('multivendor', 'commissions', ['page' => $current_page+1, 'view' => 'transactions'])}">
                                    {l s='Suivant' mod='multivendor'} ›
                                </a>
                            {/if}
                        </div>
                    </div>
                {/if}
            {else}
                <div class="mv-mobile-empty-state">
                    <div class="mv-mobile-empty-icon">💰</div>
                    <p>{l s='Aucune transaction de commission trouvée.' mod='multivendor'}</p>
                </div>
            {/if}
        </div>
    </div>
</div>

{* Image Modal *}
<div id="imageModal" class="mv-image-modal" style="display: none;">
    <div class="mv-image-modal-overlay" onclick="closeImageModal()"></div>
    <div class="mv-image-modal-content">
        <button class="mv-image-modal-close" onclick="closeImageModal()">✕</button>
        <img id="modalImage" src="" alt="" class="mv-modal-image">
    </div>
</div>

<style>
    .mv-mobile-clickable-image {
        cursor: pointer;
    }

    .mv-image-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mv-image-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
    }

    .mv-image-modal-content {
        position: relative;
        max-width: 95%;
        max-height: 95%;
        z-index: 10000;
    }

    .mv-modal-image {
        max-width: 100%;
        max-height: 95vh;
        object-fit: contain;
        border-radius: 8px;
    }

    .mv-image-modal-close {
        position: absolute;
        right: 0;
        background: #ffffff00;
        border: none;
        font-size: 28px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10001;
    }

    .mv-image-modal-close:active {
        background: rgba(255, 255, 255, 1);
    }
</style>

<script>
    function openImageModal(imgElement) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const largeImageSrc = imgElement.getAttribute('data-large');

        modalImg.src = largeImageSrc;
        modalImg.alt = imgElement.alt;
        modal.style.display = 'flex';

        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
    }

    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        modal.style.display = 'none';

        // Restore body scroll
        document.body.style.overflow = '';
    }

    // Close modal on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeImageModal();
        }
    });

    function toggleSlide(button) {
        const orderItem = button.closest('.mv-mobile-order-item');
        orderItem.classList.toggle('slided');
    }
</script>