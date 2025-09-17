{*
* Mobile Manifest Content - Card Layout
*}

{* Mobile Actions Card *}
<div class="mv-card mv-mobile-actions-card">
    <div class="mv-card-header">
        <h5 class="mv-card-title">{l s='Manifest Actions' mod='multivendor'}</h5>
    </div>
    <div class="mv-card-body">
        <div class="mv-mobile-actions-grid">
            <button class="mv-btn-mobile mv-btn-select-mobile" id="mobileSelectAllBtn">
                <i class="mv-icon">☑️</i>
                {l s='Select All' mod='multivendor'}
            </button>
            <button class="mv-btn-mobile mv-btn-save-mobile" id="mobileSaveBtn">
                <i class="mv-icon">💾</i>
                {l s='Save Manifest' mod='multivendor'}
            </button>
            <button class="mv-btn-mobile mv-btn-print-mobile" id="mobilePrintBtn">
                <i class="mv-icon">🖨️</i>
                {l s='Print' mod='multivendor'}
            </button>
            <button class="mv-btn-mobile mv-btn-cancel-mobile" id="mobileCancelBtn">
                <i class="mv-icon">❌</i>
                {l s='Cancel' mod='multivendor'}
            </button>
        </div>
    </div>
</div>

{* Mobile Selected Items Counter *}
<div class="mv-card mv-mobile-counter-card" id="mobileCounterCard" style="display: none;">
    <div class="mv-card-body">
        <div class="mv-mobile-selected-info">
            <span class="mv-selected-count">
                <span id="mobileSelectedCount">0</span> {l s='items selected' mod='multivendor'}
            </span>
            <span class="mv-selected-total">
                {l s='Total:' mod='multivendor'} <span id="mobileTotalAmount">0</span>
            </span>
        </div>
    </div>
</div>

{* Mobile Available Orders *}
<div class="mv-card mv-mobile-orders-card">
    <div class="mv-card-header">
        <h5 class="mv-card-title">{l s='Available Orders' mod='multivendor'}</h5>
    </div>
    <div class="mv-card-body">
        <div class="mv-mobile-orders-grid" id="mobileAvailableOrders">
            <div class="loading">{l s='Loading available orders...' mod='multivendor'}</div>
        </div>
    </div>
</div>

{* Mobile Selected Orders *}
<div class="mv-card mv-mobile-selected-card" id="mobileSelectedCard" style="display: none;">
    <div class="mv-card-header">
        <h5 class="mv-card-title">{l s='Selected for Manifest' mod='multivendor'}</h5>
    </div>
    <div class="mv-card-body">
        <div class="mv-mobile-selected-grid" id="mobileSelectedOrders">
            {* Selected items will be populated by JavaScript *}
        </div>
    </div>
</div>

{* Mobile Manifest List *}
<div class="mv-card mv-mobile-manifests-card">
    <div class="mv-card-header">
        <h5 class="mv-card-title">{l s='My Manifests' mod='multivendor'}</h5>
    </div>
    <div class="mv-card-body">
        <div class="mv-mobile-manifests-grid" id="mobileManifestList">
            <div class="loading">{l s='Loading manifests...' mod='multivendor'}</div>
        </div>
    </div>
</div>