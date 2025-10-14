{*
* Admin Vendor Payments - Available Transactions Template
* File: views/templates/admin/available_transactions.tpl
*}

{if $transactions && count($transactions) > 0}
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>{l s='Order' mod='multivendor'}</th>
                    <th>{l s='Product' mod='multivendor'}</th>
                    <th>{l s='Type' mod='multivendor'}</th>
                    <th>{l s='Amount' mod='multivendor'}</th>
                    <th>{l s='Status' mod='multivendor'}</th>
                    <th>{l s='Manifest' mod='multivendor'}</th>
                    <th>{l s='Date' mod='multivendor'}</th>
                    <th>{l s='Action' mod='multivendor'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$transactions item=transaction}
                    {assign var="manifestObj" value=TransactionHelper::getManifestReference($transaction.id_order_detail ,$transaction.transaction_type)}
                    {if $manifestObj || $advanced}
                        <tr>
                            <td>
                                <strong>{$transaction.id_order}</strong><br>
                                <small>{$transaction.id_order_detail}</small>
                            </td>
                            <td>
                                <strong>{$transaction.product_name|escape:'html':'UTF-8'}</strong><br>
                                <small>
                                    {if $transaction.product_reference}
                                        SKU: {$transaction.product_reference|escape:'html':'UTF-8'}<br>
                                    {/if}
                                    {if $transaction.product_quantity > 0}
                                        Qty: {$transaction.product_quantity|intval}
                                    {/if}
                                </small>
                            </td>
                            <td>
                                {if $transaction.transaction_type == 'refund'}
                                    <span class="badge badge-warning">
                                        <i class="icon-undo"></i> {l s='Refund' mod='multivendor'}
                                    </span>
                                {elseif $transaction.transaction_type == 'commission'}
                                    <span class="badge badge-success">
                                        <i class="icon-money"></i> {l s='Commission' mod='multivendor'}
                                    </span>
                                {elseif $transaction.transaction_type == 'adjustment'}
                                    <span class="badge badge-info">
                                        <i class="icon-edit"></i> {l s='Adjustment' mod='multivendor'}
                                    </span>
                                {else}
                                    <span class="badge badge-default">
                                        {$transaction.transaction_type|escape:'html':'UTF-8'|capitalize}
                                    </span>
                                {/if}
                            </td>
                            <td>
                                {if $transaction.transaction_type == 'refund'}
                                    <span class="text-danger">
                                        <strong>-{$transaction.vendor_amount|number_format:3} TND</strong>
                                    </span>
                                {else}
                                    <span class="text-success">
                                        <strong>{$transaction.vendor_amount|number_format:3} TND</strong>
                                    </span>
                                {/if}
                            </td>
                            <td>
                                <span class="badge" style="background-color: {$transaction.status_color}; color: white;">
                                    {$transaction.line_status|escape:'html':'UTF-8'}
                                </span>
                            </td>
                            <td>
                                {assign var="manifestlink" value=manifest::getAdminLink($manifestObj.id_manifest)}
                                {assign var="manifeststatus" value=Manifest::getStatus($manifestObj.id_manifest)}

                                <span>
                                    <a href="{$manifestlink}" target="_blank">{$manifestObj.reference}</a>{if $manifeststatus}-[{$manifeststatus}]{/if} 
                                </span>
                            </td>
                            <td>
                                <small>{$transaction.order_date|date_format:'Y-m-d H:i'}</small>
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary btn-xs add-transaction"
                                    data-transaction-id="{$transaction.id_vendor_transaction|intval}"
                                    title="{l s='Add this transaction to the payment' mod='multivendor'}">
                                    <i class="icon-plus"></i> {l s='Add' mod='multivendor'}
                                </button>
                            </td>
                        </tr>
                    {/if}

                {/foreach}
            </tbody>
        </table>

        <div class="alert alert-info">
            <i class="icon-info-circle"></i>
            <strong>{l s='Found %d available transactions' sprintf=[$transactions|count] mod='multivendor'}</strong>
            {if $transactions|count > 0}
                <br><small>{l s='Click "Add" to include a transaction in this payment. Refund transactions will reduce the payment total.' mod='multivendor'}</small>
            {/if}
        </div>
    </div>
{else}
    <div class="alert alert-warning">
        <i class="icon-warning"></i>
        <strong>{l s='No available transactions found' mod='multivendor'}</strong>
        <br><small>{l s='Try adjusting your filters or check if all transactions have already been included in payments.' mod='multivendor'}</small>
    </div>
{/if}

<style>
    .badge-warning {
        background-color: #f0ad4e;
    }

    .badge-success {
        background-color: #5cb85c;
    }

    .badge-info {
        background-color: #5bc0de;
    }

    .badge-default {
        background-color: #777;
    }

    .text-danger {
        color: #d9534f;
    }

    .text-success {
        color: #5cb85c;
    }
</style>