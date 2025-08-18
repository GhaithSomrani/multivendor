{*
* Transaction Row Template with Transaction Type
* File: views/templates/admin/transaction/transaction_row.tpl
*}

<tr>
    <td>
        <input type="checkbox" name="selected_order_details[]" value="{$transaction.id_vendor_transaction|intval}"
            data-amount="{$transaction.vendor_amount|floatval}" class="transaction-checkbox" />
    </td>
    <td>
        <strong>#{$transaction.order_reference}</strong><br>
        <small>Order #{$transaction.id_order}</small>
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
                <strong>{$transaction.vendor_amount|number_format:2} TND</strong>
            </span>
        {else}
            <span class="text-success">
                <strong>{$transaction.vendor_amount|number_format:2} TND</strong>
            </span>
        {/if}
    </td>
    <td>
        <span class="badge" style="background-color: {$transaction.status_color};">
            {$transaction.line_status|escape:'html':'UTF-8'}
        </span>
    </td>
    <td>
        <small>{$transaction.order_date|date_format:'Y-m-d H:i'}</small>
    </td>
</tr>