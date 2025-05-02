<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="vendor-dashboard-link" href="{$vendor_dashboard_url}">
    <span class="link-item">
      <i class="material-icons">store</i>
      {if $is_vendor}
        {l s='My Vendor Dashboard' d='Module.LamodeMarketplace.Shop'}
      {else}
        {l s='Become a Vendor' d='Module.LamodeMarketplace.Shop'}
      {/if}
    </span>
  </a>