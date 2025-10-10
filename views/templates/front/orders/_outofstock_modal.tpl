<div class="mv-modal" id="outofstock-modal">
    <div class="mv-modal-backdrop" onclick="closeOutOfStockModal()"></div>
    <div class="mv-modal-content">
        <div class="mv-modal-header">
            <div class="mv-modal-title">
                <h3>{l s='Produit en rupture' mod='multivendor'}</h3>
            </div>
            <button class="mv-modal-close" onclick="closeOutOfStockModal()">&times;</button>
        </div>

        <div class="mv-modal-body">
            <div id="currentoutofstock">
                <div class="mv-product-image">
                    <img src="" id="currentoutodstock-image" class="mv-product-image">
                </div>
                <div class="mv-product-name">
                    <strong class="mv-product-name" id="currentoutodstock-name"></strong>
                    <div class="mv-additional-option">
                        <div class="mv-mobile-product-sku" id="currentoutodstock-brand"></div>
                        <div class="mv-mobile-product-sku" id="currentoutodstock-price"></div>
                        <div class="mv-mobile-product-sku" id="currentoutodstock-sku"></div>
                        <div class="mv-mobile-product-sku" id="currentoutodstock-mpn"></div>
                    </div>
                </div>
                <div id="generated-comment">
                    <textarea id="input-comment" class="mv-form-control" rows="3"
                        placeholder="Si vous ne trouvez pas votre suggestion dans la recherche, vous pouvez nous fournir des informations à ce sujet ici. Exemple (nom - référence - lien)"
                        style="resize: none;"></textarea>
                </div>
            </div>


            <div class="search-block">
                <div class="input-search-block">
                    <input type="text" id="product-search-input" class="mv-input"
                        placeholder="{l s='Tapez pour rechercher...' mod='multivendor'}"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    <span class="mv-status-badge-filter" style="" id="filter-pricefrom"></span>
                    <span class="mv-status-badge-filter" id="filter-priceto"></span>
                    <span class="mv-status-badge-filter" id="filter-category"></span>

                    <button class="mv-btn mv-btn-primary"> Rechercher</button>
                </div>
                <div id="variant-selected" style="display :none;">

                </div>
            </div>


            <div id="outfostock-result" style="margin-bottom: 20px;">
                <h4 style="margin-bottom: 12px; font-size: 16px;">
                    {l s='Résultats de recherche' mod='multivendor'}
                </h4>
                <div class="mv-search-container" id="search-results">


                </div>
                <div id="search-outofstock-pagination"></div>
            </div>
            <div class="mv-modal-footer">

                <div class="no-suggestion"> <input type="checkbox" id="no-suggestion"
                        value="n'existe aucune autre variante, produit similaire ou suggestion alternative disponible.">
                    <span> Il
                        n’existe aucune autre
                        variante, produit similaire ou suggestion alternative disponible. </span>
                </div>
                <button class="mv-btn mv-btn-primary" value="{$out_of_stock_status->id}" id='outofstock-btn'
                    onclick="confirmOutOfStock()" disabled>
                    {l s='Confirmer' mod='multivendor'}
                </button>
            </div>
        </div>


    </div>
</div>