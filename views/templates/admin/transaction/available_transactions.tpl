{*
* Admin Vendor Payments - Available Transactions Template
* File: views/templates/admin/available_transactions.tpl
*}

{if $transactions && count($transactions) > 0}
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{l s='Order' mod='multivendor'}</th>
                <th>{l s='Order Detail ID' mod='multivendor'}</th>
                <th>{l s='Product' mod='multivendor'}</th>
                <th>{l s='SKU' mod='multivendor'}</th>
                <th>{l s='Amount' mod='multivendor'}</th>
                <th>{l s='Status' mod='multivendor'}</th>
                <th>{l s='Date' mod='multivendor'}</th>
                <th>{l s='Actions' mod='multivendor'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$transactions item=transaction}
                <tr>
                    <td>#{$transaction.order_reference|escape:'htmlall':'UTF-8'}</td>
                    <td>{$transaction.id_order_detail|intval}</td>
                    <td>{$transaction.product_name|escape:'htmlall':'UTF-8'}</td>
                    <td>
                        {if $transaction.product_reference}
                            {$transaction.product_reference|escape:'htmlall':'UTF-8'}
                        {else}
                            <span class="text-muted">-</span>
                        {/if}
                    </td>
                    <td>{$transaction.vendor_amount|number_format:2} {$currency_sign|escape:'htmlall':'UTF-8'}</td>
                    <td>
                        <span class="badge" style="background-color: {$transaction.status_color|escape:'htmlall':'UTF-8'}">
                            {$transaction.status_name|escape:'htmlall':'UTF-8'}
                        </span>
                    </td>
                    <td>
                        <small>{$transaction.order_date|date_format:'%Y-%m-%d'}</small>
                    </td>
                    <td>
                        <button type="button" 
                                class="btn btn-success btn-xs add-transaction"
                                data-transaction-id="{$transaction.id_vendor_transaction|intval}"
                                data-amount="{$transaction.vendor_amount|floatval}">
                            <i class="icon-plus"></i> {l s='Add' mod='multivendor'}
                        </button>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{else}
    <div class="alert alert-info">{l s='No pending transactions available' mod='multivendor'}</div>
{/if}