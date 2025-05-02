{*
    * Admin Category Commissions Template
    *}
    
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-money"></i> {l s='Category Commissions' mod='multivendor'}
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-lg-6">
                    <div class="panel">
                        <div class="panel-heading">
                            {l s='Add/Update Category Commission' mod='multivendor'}
                        </div>
                        <div class="panel-body">
                            <form id="category-commission-form" class="form-horizontal">
                                <div class="form-group">
                                    <label class="control-label col-lg-4">{l s='Vendor' mod='multivendor'}</label>
                                    <div class="col-lg-8">
                                        <select name="id_vendor" class="form-control">
                                            {foreach from=$vendors key=id_vendor item=vendor_name}
                                                <option value="{$id_vendor}">{$vendor_name}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-lg-4">{l s='Category' mod='multivendor'}</label>
                                    <div class="col-lg-8">
                                        <select name="id_category" class="form-control">
                                            {foreach from=$categories key=id_category item=category_name}
                                                <option value="{$id_category}">{$category_name}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-lg-4">{l s='Commission Rate (%)' mod='multivendor'}</label>
                                    <div class="col-lg-8">
                                        <div class="input-group">
                                            <input type="number" name="commission_rate" class="form-control" min="0" max="100" step="0.01" required>
                                            <span class="input-group-addon">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-lg-4">{l s='Comment' mod='multivendor'}</label>
                                    <div class="col-lg-8">
                                        <textarea name="comment" class="form-control"></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-8 col-lg-offset-4">
                                        <button type="submit" class="btn btn-primary">{l s='Save' mod='multivendor'}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="panel">
                        <div class="panel-heading">
                            {l s='Existing Category Commissions' mod='multivendor'}
                        </div>
                        <div class="panel-body">
                            {if $category_commissions}
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>{l s='Vendor' mod='multivendor'}</th>
                                                <th>{l s='Category' mod='multivendor'}</th>
                                                <th>{l s='Rate (%)' mod='multivendor'}</th>
                                                <th>{l s='Last Updated' mod='multivendor'}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {foreach from=$category_commissions item=commission}
                                                <tr>
                                                    <td>{$commission.shop_name}</td>
                                                    <td>{$commission.category_name}</td>
                                                    <td>{$commission.commission_rate}%</td>
                                                    <td>{$commission.date_upd|date_format:'%Y-%m-%d %H:%M:%S'}</td>
                                                </tr>
                                            {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            {else}
                                <p class="alert alert-info">{l s='No category commissions defined yet.' mod='multivendor'}</p>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
        $(document).ready(function() {
            $('#category-commission-form').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '{$category_commission_url}',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showSuccessMessage('{l s='Commission rate updated successfully' mod='multivendor'}');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showErrorMessage('{l s='Error updating commission rate' mod='multivendor'}');
                        }
                    },
                    error: function() {
                        showErrorMessage('{l s='Error communicating with server' mod='multivendor'}');
                    }
                });
            });
        });
    </script>