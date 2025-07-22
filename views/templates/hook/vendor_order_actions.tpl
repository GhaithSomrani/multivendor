{*
* Vendor Order Actions Hook Template
* File: views/templates/hook/vendor_order_actions.tpl
*}

<div class="manifest-order-actions">
    {if $in_manifest}
        <span class="badge badge-info">
            <i class="material-icons">assignment_turned_in</i>
            {l s='In Manifest' mod='multivendor'}
        </span>
    {else}
        <button type="button" 
                class="btn btn-sm btn-outline-primary add-to-manifest" 
                data-order-detail-id="{$id_order_detail}"
                title="{l s='Add to Manifest' mod='multivendor'}">
            <i class="material-icons">playlist_add</i>
            {l s='Add to Manifest' mod='multivendor'}
        </button>
    {/if}
</div>

<script>
$(document).ready(function() {
    $('.add-to-manifest').on('click', function() {
        const orderDetailId = $(this).data('order-detail-id');
        
        // Open manifest management with pre-selected item
        const manifestUrl = '{$manifest_url}' + '&preselect=' + orderDetailId;
        window.open(manifestUrl, '_blank');
    });
});
</script>

<style>
.manifest-order-actions {
    display: inline-block;
    margin-left: 10px;
}

.manifest-order-actions .badge {
    font-size: 11px;
}

.manifest-order-actions .btn {
    font-size: 11px;
    padding: 4px 8px;
}

.manifest-order-actions .material-icons {
    font-size: 14px;
    vertical-align: middle;
}
</style>