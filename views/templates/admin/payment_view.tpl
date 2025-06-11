{*
* Multi-vendor Payment Details Template
* Simple template for displaying payment transaction details
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-money"></i>
        {l s='Payment Transaction Details' mod='multivendor'}
        {if isset($payment)}
            - Payment #{$payment->id}
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
                            <td class="text-success">{Tools::displayPrice($payment->amount, $currency)}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Status:' mod='multivendor'}</strong></td>
                            <td>
                                <span class="badge badge-{if $payment->status == 'paid'}success{elseif $payment->status == 'pending'}warning{elseif $payment->status == 'cancelled'}danger{else}default{/if}">
                                    {$payment->status|ucfirst}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Payment Method:' mod='multivendor'}</strong></td>
                            <td>{$payment->payment_method|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Reference:' mod='multivendor'}</strong></td>
                            <td>{$payment->reference|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Date Created:' mod='multivendor'}</strong></td>
                            <td>{dateFormat date=$payment->date_add full=1}</td>
                        </tr>
                        {if isset($payment->date_upd) && $payment->date_upd != $payment->date_add}
                        <tr>
                            <td><strong>{l s='Last Updated:' mod='multivendor'}</strong></td>
                            <td>{dateFormat date=$payment->date_upd full=1}</td>
                        </tr>
                        {/if}
                        {if isset($payment->notes) && $payment->notes}
                        <tr>
                            <td><strong>{l s='Notes:' mod='multivendor'}</strong></td>
                            <td>{$payment->notes}</td>
                        </tr>
                        {/if}
                    </table>
                </div>

                {* Payment Details Summary *}
                <div class="col-md-6">
                    <h4>{l s='Payment Summary' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        {if isset($transaction_details)}
                            <tr>
                                <td><strong>{l s='Total Transactions:' mod='multivendor'}</strong></td>
                                <td>{count($transaction_details)}</td>
                            </tr>
                            <tr>
                                <td><strong>{l s='Total Commission:' mod='multivendor'}</strong></td>
                                <td class="text-success">
                                    {assign var="total_commission" value=0}
                                    {foreach from=$transaction_details item=detail}
                                        {assign var="total_commission" value=$total_commission+$detail.commission_amount}
                                    {/foreach}
                                    {Tools::displayPrice($total_commission, $currency)}
                                </td>
                            </tr>
                        {/if}
                        <tr>
                            <td><strong>{l s='Payment Type:' mod='multivendor'}</strong></td>
                            <td>{if isset($payment->type)}{$payment->type|ucfirst}{else}Commission Payment{/if}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {* Transaction Details *}
            {if isset($transaction_details) && count($transaction_details) > 0}
                <div class="row">
                    <div class="col-md-12">
                        <h4>{l s='Transaction Details' mod='multivendor'}</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>{l s='Transaction ID' mod='multivendor'}</th>
                                        <th>{l s='Order ID' mod='multivendor'}</th>
                                        <th>{l s='Order Reference' mod='multivendor'}</th>
                                        <th>{l s='Product' mod='multivendor'}</th>
                                        <th>{l s='Commission Amount' mod='multivendor'}</th>
                                        <th>{l s='Vendor Amount' mod='multivendor'}</th>
                                        <th>{l s='Transaction Type' mod='multivendor'}</th>
                                        <th>{l s='Order Date' mod='multivendor'}</th>
                                        <th>{l s='Status' mod='multivendor'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$transaction_details item=detail}
                                        <tr>
                                            <td>#{$detail.id_vendor_transaction}</td>
                                            <td>
                                                <a href="{$link->getAdminLink('AdminOrders')}&id_order={$detail.id_order}&vieworder" target="_blank">
                                                    #{$detail.id_order}
                                                </a>
                                            </td>
                                            <td>{$detail.order_reference|default:'N/A'}</td>
                                            <td>
                                                {$detail.product_name|default:'N/A'}
                                                {if isset($detail.product_reference) && $detail.product_reference}
                                                    <br><small class="text-muted">Ref: {$detail.product_reference}</small>
                                                {/if}
                                                {if isset($detail.product_quantity) && $detail.product_quantity}
                                                    <br><small class="text-info">Qty: {$detail.product_quantity}</small>
                                                {/if}
                                            </td>
                                            <td class="text-success">{Tools::displayPrice($detail.commission_amount, $currency)}</td>
                                            <td class="text-info">{Tools::displayPrice($detail.vendor_amount, $currency)}</td>
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
        {else}
            <div class="alert alert-warning">
                {l s='Payment information not found.' mod='multivendor'}
            </div>
        {/if}
    </div>
</div>