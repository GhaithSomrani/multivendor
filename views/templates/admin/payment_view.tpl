{*
* Multi-vendor Payment Details Template with Print Button
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-money"></i>
        {l s='Payment Transaction Details' mod='multivendor'}
        {if isset($payment)}
            - Payment #{$payment->id}
        {/if}
        
        {* Print Button *}
        {if isset($print_url)}
        <div class="pull-right">
            <a href="{$print_url}" target="_blank" class="btn btn-default">
                <i class="icon-print"></i> {l s='Print Payment' mod='multivendor'}
            </a>
        </div>
        {/if}
    </div>
    
    <div class="panel-body">
        {if isset($payment)}
            {* Payment Information *}
            <div class="row">
                <div class="col-md-6">
                    <h4>{l s='Payment Information' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>{l s='Payment ID:' mod='multivendor'}</strong></td>
                            <td>#{$payment->id}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Amount:' mod='multivendor'}</strong></td>
                            <td class="text-success"><strong>{Tools::displayPrice($payment->amount, $currency)}</strong></td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Status:' mod='multivendor'}</strong></td>
                            <td>
                                <span class="badge badge-{if $payment->status == 'completed'}success{elseif $payment->status == 'pending'}warning{elseif $payment->status == 'cancelled'}danger{else}default{/if}">
                                    {$payment->status|ucfirst}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Payment Method:' mod='multivendor'}</strong></td>
                            <td>{$payment->payment_method|default:'N/A'|ucfirst}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Reference:' mod='multivendor'}</strong></td>
                            <td>{$payment->reference|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Date Created:' mod='multivendor'}</strong></td>
                            <td>{dateFormat date=$payment->date_add full=1}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h4>{l s='Vendor Information' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>{l s='Vendor ID:' mod='multivendor'}</strong></td>
                            <td>#{$vendor->id}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Shop Name:' mod='multivendor'}</strong></td>
                            <td>{$vendor->shop_name|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Email:' mod='multivendor'}</strong></td>
                            <td>{$vendor->email|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Phone:' mod='multivendor'}</strong></td>
                            <td>{$vendor->phone|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Status:' mod='multivendor'}</strong></td>
                            <td>
                                <span class="badge badge-{if $vendor->status == 'approved'}success{elseif $vendor->status == 'pending'}warning{else}danger{/if}">
                                    {$vendor->status|ucfirst}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {* Transaction Details *}
            {if isset($transaction_details) && $transaction_details}
                <div class="row">
                    <div class="col-md-12">
                        <h4>{l s='Transaction Details' mod='multivendor'}</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{l s='Order' mod='multivendor'}</th>
                                        <th>{l s='Product' mod='multivendor'}</th>
                                        <th>{l s='Vendor Amount' mod='multivendor'}</th>
                                        <th>{l s='Type' mod='multivendor'}</th>
                                        <th>{l s='Order Date' mod='multivendor'}</th>
                                        <th>{l s='Status' mod='multivendor'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$transaction_details item=detail}
                                        <tr>
                                            <td>
                                                <a href="{$link->getAdminLink('AdminOrders')}&id_order={$detail.id_order}&vieworder" target="_blank">
                                                    #{$detail.order_reference|default:'N/A'}
                                                </a>
                                            </td>
                                            <td>
                                                {$detail.product_name|default:'N/A'}
                                                {if isset($detail.product_reference) && $detail.product_reference}
                                                    <br><small class="text-muted">Ref: {$detail.product_reference}</small>
                                                {/if}
                                                {if isset($detail.product_quantity) && $detail.product_quantity}
                                                    <br><small class="text-info">Qty: {$detail.product_quantity}</small>
                                                {/if}
                                            </td>
                                            <td class="text-info"><strong>{Tools::displayPrice($detail.vendor_amount, $currency)}</strong></td>
                                            <td>{$detail.transaction_type|default:'commission'|ucfirst}</td>
                                            <td>{dateFormat date=$detail.order_date full=0}</td>
                                            <td>
                                                <span class="badge badge-{if $detail.status == 'completed'}success{elseif $detail.status == 'pending'}warning{elseif $detail.status == 'cancelled'}danger{else}info{/if}">
                                                    {$detail.status|default:'pending'|ucfirst}
                                                </span>
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                                <tfoot>
                                    <tr class="info">
                                        <td colspan="3" class="text-right"><strong>{l s='Total Payment Amount:' mod='multivendor'}</strong></td>
                                        <td><strong class="text-success">{Tools::displayPrice($payment->amount, $currency)}</strong></td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            {else}
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            {l s='No transaction details found for this payment.' mod='multivendor'}
                        </div>
                    </div>
                </div>
            {/if}

            {* Quick Actions *}
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">{l s='Quick Actions' mod='multivendor'}</h4>
                        </div>
                        <div class="panel-body">
                            <a href="{$print_url}" target="_blank" class="btn btn-default">
                                <i class="icon-print"></i> {l s='Print Payment Receipt' mod='multivendor'}
                            </a>
                            <a href="{$link->getAdminLink('AdminVendorPayments')}" class="btn btn-default">
                                <i class="icon-arrow-left"></i> {l s='Back to Payments List' mod='multivendor'}
                            </a>
                            {if $payment->status == 'pending'}
                            <a href="{$link->getAdminLink('AdminVendorPayments')}&id_vendor_payment={$payment->id}&updatevendor_payment" class="btn btn-primary">
                                <i class="icon-edit"></i> {l s='Edit Payment' mod='multivendor'}
                            </a>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        {else}
            <div class="alert alert-warning">
                {l s='Payment information not found.' mod='multivendor'}
            </div>
        {/if}
    </div>
</div>

<style>
.panel-heading .pull-right {
    margin-top: -5px;
}

.table-responsive {
    margin-top: 15px;
}

.badge {
    font-size: 11px;
}

.panel-body h4 {
    color: #555;
    border-bottom: 1px solid #ddd;
    padding-bottom: 8px;
    margin-bottom: 15px;
}
</style>