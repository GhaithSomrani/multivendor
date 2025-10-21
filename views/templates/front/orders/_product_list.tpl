{if $products|@count > 0}
    {foreach from=$products item=product}
        {if $product.current_product_id == $product.id_product}
            {include file="module:multivendor/views/templates/front/orders/_product_item.tpl" product=$product}
            {break}
        {/if}
    {/foreach}
    {foreach from=$products item=product}
        {if $product.current_product_id != $product.id_product}
            {include file="module:multivendor/views/templates/front/orders/_product_item.tpl" product=$product}
        {/if}
    {/foreach}
{else}
    <div class="mv-no-results">
        {l s='Aucun produit trouvé' mod='multivendor'}
    </div>
{/if}