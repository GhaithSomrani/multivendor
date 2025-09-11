{*
    * Admin Pending Commissions Template
    *}
    
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-money"></i> {l s='Pending Commissions' mod='multivendor'}
            <span class="badge">{count($pending_commissions)}</span>
            <span class="panel-heading-action">
                <a href="{$current_url}" class="btn btn-default" title="{l s='Refresh' mod='multivendor'}">
                    <i class="process-icon-refresh"></i>
                </a>
            </span>
        </div>
        <div class="panel-body">
            {if $pending_commissions}
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{l s='Vendor' mod='multivendor'}</th>
                                <th>{l s='Pending Amount' mod='multivendor'}</th>
                                <th>{l s='Transactions' mod='multivendor'}</th>
                                <th>{l s='Actions' mod='multivendor'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$pending_commissions item=commission}
                                <tr>
                                    <td>{$commission.shop_name}</td>
                                    <td>{$commission.pending_amount|string_format:"%.3f"}{$currency->sign}</td>
                                    <td>{$commission.transaction_count}</td>
                                    <td>
                                        <button class="btn btn-success btn-sm pay-commission-btn" 
                                                data-toggle="modal" 
                                                data-target="#payCommissionModal-{$commission.id_vendor}" 
                                                data-amount="{$commission.pending_amount|string_format:"%.3f"}{$currency->sign}"
                                                data-vendor="{$commission.shop_name}">
                                            <i class="icon-credit-card"></i> {l s='Pay Commission' mod='multivendor'}
                                        </button>
                                        
                                        <!-- Pay Commission Modal -->
                                        <div class="modal fade" id="payCommissionModal-{$commission.id_vendor}" tabindex="-1" role="dialog" aria-labelledby="payCommissionLabel-{$commission.id_vendor}">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title" id="payCommissionLabel-{$commission.id_vendor}">
                                                            {l s='Pay Commission to' mod='multivendor'} {$commission.shop_name}
                                                        </h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form id="pay-commission-form-{$commission.id_vendor}" class="pay-commission-form">
                                                            <input type="hidden" name="id_vendor" value="{$commission.id_vendor}">
                                                            
                                                            <div class="form-group">
                                                                <label>{l s='Amount to Pay' mod='multivendor'}</label>
                                                                <p class="form-control-static">{$commission.pending_amount|string_format:"%.2f"}{$currency->sign}</p>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="payment_method-{$commission.id_vendor}">{l s='Payment Method' mod='multivendor'}</label>
                                                                <select name="payment_method" id="payment_method-{$commission.id_vendor}" class="form-control" required>
                                                                    <option value="">{l s='-- Select Payment Method --' mod='multivendor'}</option>
                                                                    {foreach from=$payment_methods key=key item=method}
                                                                        <option value="{$key}">{$method}</option>
                                                                    {/foreach}
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="reference-{$commission.id_vendor}">{l s='Payment Reference' mod='multivendor'}</label>
                                                                <input type="text" name="reference" id="reference-{$commission.id_vendor}" class="form-control" required>
                                                                <p class="help-block">{l s='Transaction ID, Check Number, etc.' mod='multivendor'}</p>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cancel' mod='multivendor'}</button>
                                                        <button type="button" class="btn btn-primary confirm-pay-btn" data-id="{$commission.id_vendor}">
                                                            {l s='Confirm Payment' mod='multivendor'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            {else}
                <p class="alert alert-info">{l s='No pending commissions found.' mod='multivendor'}</p>
            {/if}
        </div>
    </div>
    
   <script type="text/javascript">
    $(document).ready(function() {
        $('.confirm-pay-btn').click(function() {
            var id_vendor = $(this).data('id');
            var form = $('#pay-commission-form-' + id_vendor);
            
            if (form[0].checkValidity()) {
                $.ajax({
                    url: '{$current_url}&ajax=1&action=payCommission',
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showSuccessMessage(response.message || '{l s='Payment processed successfully' mod='multivendor'}');
                            $('#payCommissionModal-' + id_vendor).modal('hide');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showErrorMessage(response.message || '{l s='Error processing payment' mod='multivendor'}');
                        }
                    },
                    error: function(xhr) {
                        showErrorMessage('{l s='Error communicating with server' mod='multivendor'}: ' + xhr.responseText);
                    }
                });
            } else {
                form[0].reportValidity();
            }
        });
    });
</script>
