{*
* Modèle de Manifeste Vendeur avec Détection Mobile
*}

{extends file='page.tpl'}

{block name='page_title'}
    {l s='Gérer les Manifestes' mod='multivendor'}
{/block}

{block name='page_content'}
    <div class="mv-dashboard">
        <div class="mv-container">
            {include file="module:multivendor/views/templates/front/_partials/sidebar.tpl" active_page="manifests"}

            <main class="mv-main-content">
                {* Détection Mobile/Bureau pour le Contenu *}
                {if Context::getContext()->isMobile() == 1}
                    {* Charger le Modèle Mobile *}
                    {include file="module:multivendor/views/templates/front/manifest/mobile.tpl"}
                {else}
                    {* Charger le Modèle Bureau *}
                    {include file="module:multivendor/views/templates/front/manifest/desktop.tpl"}
                {/if}
            </main>
        </div>
    </div>



    <script>
        // Passer les variables PHP à JavaScript
        window.manifestConfig = {
            ajaxUrl: '{$manifest_ajax_url}',
            vendorId: {$vendor_id},
            isMobile: {if Context::getContext()->isMobile()}true{else}false{/if},
            translations: {
                loading: '{l s="Chargement..." mod="multivendor"}',
                noItemsSelected: '{l s="Aucun article sélectionné" mod="multivendor"}',
                manifestSaved: '{l s="Manifeste enregistré avec succès" mod="multivendor"}',
                manifestDeleted: '{l s="Manifeste supprimé avec succès" mod="multivendor"}',
                confirmDelete: '{l s="Êtes-vous sûr de vouloir supprimer ce manifeste ?" mod="multivendor"}',
                selectAddress: '{l s="Veuillez sélectionner une adresse" mod="multivendor"}',
                noOrders: '{l s="Aucune commande disponible" mod="multivendor"}',
                noManifests: '{l s="Aucun manifeste trouvé" mod="multivendor"}',
                manifestLoaded: '{l s="Manifeste chargé avec succès" mod="multivendor"}'
            }
        };
    </script>
{/block}