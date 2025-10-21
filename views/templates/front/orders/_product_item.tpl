<div class="mv-payment-header {if $product.current_product_id == $product.id_product } selected {/if}"
    data-id-product="{$product.id_product}">
    <div class="mv-payment-info">
        <img src="{$product.image_url}" class="mv-product-image">
    </div>
    <div class="mv-product-name">
        <div>
            <strong class="mv-product-name">{$product.name}</strong>
            <div class="mv-additional-option">
                <div class="mv-mobile-product-sku">REF: {$product.reference}</div>
                <div class="mv-mobile-product-sku">{$product.price_formatted}</div>
                <div class="mv-mobile-product-sku">MPN: {$product.mpn}</div>


                {if $product.shift_price > 0}
                    <div class="mv-mobile-product-sku"> <span style="color:#00DFA2;"> ▲ <strong>
                                {$product.shift_price} TND</strong></span>
                    </div>
                {elseif $product.shift_price < 0}
                    <div class="mv-mobile-product-sku"> <span style="color:#FF0060;"> ▼ <strong>
                                {$product.shift_price} TND</strong></span>
                    </div>
                {/if}

            </div>
        </div>
        <div style="display: flex; gap: 8px; width:100%">
            {if isset($product.attributes) && $product.attributes|@count > 0}
                <div class="mv-variant-container" style="display: flex; gap: 8px;">
                    {foreach from=$product.attributes key=groupName item=options}
                        <select class="mv-form-control variant-select" data-group="{$groupName}">
                            <option value=""> {$groupName}</option>
                            {foreach from=$options item=option}
                                <option value="{$option.id_attribute}">{$option.name}</option>
                            {/foreach}
                        </select>
                    {/foreach}
                </div>
            {/if}
            <button class="mv-btn mv-btn-primary mv-btn-sm suggest-btn" style="margin-left:auto"
                onclick="addSuggestion({$product.id_product}, this); event.stopPropagation();"
                {if $product.attributes|@count > 0}disabled{/if}>
                {l s='Suggérer' mod='multivendor'}
            </button>
        </div>
    </div>
</div>