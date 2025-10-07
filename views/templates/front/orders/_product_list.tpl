{if $products|@count > 0}
    {foreach from=$products item=product}
        {include file="module:multivendor/views/templates/front/orders/_product_item.tpl" product=$product}
    {/foreach}
{else}
    <div class="mv-no-results">
        {l s='Aucun produit trouvé' mod='multivendor'}
    </div>
{/if}