{*
* Vendor Facturation (Payments/Invoicing) Template
*}

{extends file='page.tpl'}

{block name='page_title'}
    {l s='Facturation & Paiements' mod='multivendor'}
{/block}

{block name='page_content'}
    <div class="mv-dashboard">
        <div class="mv-container">
            {include file="module:multivendor/views/templates/front/_partials/sidebar.tpl" active_page="facturation"}

            <main class="mv-main-content">
                {* Mobile/Desktop Detection for Content *}
                {if Context::getContext()->isMobile() == 1}
                    {* Load Mobile Template *}
                    {include file="module:multivendor/views/templates/front/facturation/mobile.tpl"}
                {else}
                    {* Load Desktop Template *}
                    {include file="module:multivendor/views/templates/front/facturation/desktop.tpl"}
                {/if}
            </main>
        </div>
    </div>
{/block}
