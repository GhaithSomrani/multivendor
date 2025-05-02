{*
    * Vendor Profile Template
    *}
    
    {extends file='page.tpl'}
    
    {block name='page_title'}
        {l s='Shop Profile' mod='multivendor'}
    {/block}
    
    {block name='page_content'}
        <div class="vendor-profile">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{l s='Navigation' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_dashboard_url}">
                                        <i class="material-icons">dashboard</i>
                                        {l s='Dashboard' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_orders_url}">
                                        <i class="material-icons">shopping_cart</i>
                                        {l s='Orders' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_commissions_url}">
                                        <i class="material-icons">attach_money</i>
                                        {l s='Commissions' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item active">
                                    <a class="nav-link" href="#">
                                        <i class="material-icons">store</i>
                                        {l s='Shop Profile' mod='multivendor'}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{l s='Shop Profile' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            {if isset($success)}
                                {foreach $success as $msg}
                                    <div class="alert alert-success">
                                        {$msg}
                                    </div>
                                {/foreach}
                            {/if}
                            
                            {if isset($errors)}
                                {foreach $errors as $error}
                                    <div class="alert alert-danger">
                                        {$error}
                                    </div>
                                {/foreach}
                            {/if}
                            
                            <form action="{$link->getModuleLink('multivendor', 'profile')}" method="post" enctype="multipart/form-data">
                                <div class="form-group row">
                                    <label for="shop_name" class="col-sm-3 col-form-label">{l s='Shop Name' mod='multivendor'} *</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="shop_name" name="shop_name" value="{$vendor->shop_name}" required>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label for="description" class="col-sm-3 col-form-label">{l s='Description' mod='multivendor'}</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="description" name="description" rows="5">{$vendor->description}</textarea>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label for="logo" class="col-sm-3 col-form-label">{l s='Shop Logo' mod='multivendor'}</label>
                                    <div class="col-sm-9">
                                        {if $vendor->logo}
                                            <div class="mb-2">
                                                <img src="{$image_base_path}{$vendor->logo}" alt="Logo" style="max-width: 150px; max-height: 150px;">
                                            </div>
                                        {/if}
                                        <input type="file" class="form-control-file" id="logo" name="logo">
                                        <small class="form-text text-muted">{l s='Recommended size: 200x200px. Max file size: 2MB. Formats: jpg, jpeg, png, gif.' mod='multivendor'}</small>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label for="banner" class="col-sm-3 col-form-label">{l s='Shop Banner' mod='multivendor'}</label>
                                    <div class="col-sm-9">
                                        {if $vendor->banner}
                                            <div class="mb-2">
                                                <img src="{$image_base_path}{$vendor->banner}" alt="Banner" style="max-width: 100%; max-height: 200px;">
                                            </div>
                                        {/if}
                                        <input type="file" class="form-control-file" id="banner" name="banner">
                                        <small class="form-text text-muted">{l s='Recommended size: 1200x300px. Max file size: 2MB. Formats: jpg, jpeg, png, gif.' mod='multivendor'}</small>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <div class="col-sm-9 offset-sm-3">
                                        <button type="submit" name="submitVendorProfile" class="btn btn-primary">
                                            <i class="material-icons">save</i>
                                            {l s='Save Profile' mod='multivendor'}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/block}