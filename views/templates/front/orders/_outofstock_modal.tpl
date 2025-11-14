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
                <strong class="mv-product-name" id="currentoutodstock-name"></strong>

                <div class="mv-product-name">
                    <div class="mv-additional-option">
                        <div class="mv-mobile-product-sku" id="currentoutodstock-brand"></div>
                        <div class="mv-mobile-product-sku" id="currentoutodstock-price"></div>
                        <div class="mv-mobile-product-sku" id="currentoutodstock-sku"></div>
                        <div class="mv-mobile-product-sku" id="currentoutodstock-mpn"></div>
                    </div>
                </div>
                <div id="generated-comment">
                    <textarea id="input-comment" class="mv-form-control" rows="1" placeholder="Note Supplémentaire"
                        style="resize: none; "></textarea>
                </div>
            </div>


            <div class="search-block">
                <div class="input-search-block">
                    <input type="text" id="product-search-input" class="mv-input"
                        placeholder="{l s='Tapez pour rechercher...' mod='multivendor'}"
                        style="width: 45%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">

                    {* <span class="mv-status-badge-filter" style="" id="filter-pricefrom"></span>
                    <span class="mv-status-badge-filter" id="filter-priceto"></span> *}
                    <div class="filter-out-of-stock">
                        <span class="mv-status-badge-filter" id="filter-category"></span>
                        <div class="out-price-filter">
                            <div class="price-slider-wrapper">
                                <div id="slider-range"></div>
                            </div>
                            <small id="filter-pricerange">0 - 1000</small>
                        </div>
                    </div>



                    <button class="mv-btn mv-btn-primary" style="margin-left: auto;">🔍︎ Rechercher</button>
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
                        value="Il n’existe aucune alternative">
                    <span> Il n’existe aucune alternative</span>
                </div>
                <button class="mv-btn mv-btn-primary" value="{$out_of_stock_status->id}" id='outofstock-btn'
                    onclick="confirmOutOfStock()" disabled>
                    {l s='Confirmer' mod='multivendor'}
                </button>
            </div>
        </div>


    </div>
</div>

{literal}
    <script>
        const slider = document.getElementById('slider-range');
        noUiSlider.create(slider, {
            start: [100, 900],
            connect: true,
            range: { min: 0, max: 5000 },
            step: 10,

        });

        const display = document.getElementById('filter-pricerange');
        slider.noUiSlider.on('update', (values) => {
            display.textContent = `${Math.round(values[0])} TND - ${Math.round(values[1])} TND`;
        });
    </script>

{/literal}