{*
* Admin Vendor Payments - Transaction Row Template
* File: views/templates/admin/transaction_row.tpl
*}

<tr>
    <td>
        <input type="checkbox" name="selected_order_details[]" value="{$transaction.id_vendor_transaction|intval}"
            data-amount="{$transaction.vendor_amount|floatval}" data-vendor="{$transaction.id_vendor|intval}"
            class="transaction-checkbox" />
    </td>
    <td>
        <i class="icon-eye"></i> #{$transaction.order_reference|escape:'htmlall':'UTF-8'}

        <br><small class="text-muted">{$transaction.order_date|date_format:'%Y-%m-%d %H:%M'}</small>
    </td>
    <td>
        <strong>{$transaction.product_name|escape:'htmlall':'UTF-8'}</strong>
        {if $transaction.product_reference}
            <br><small class="text-muted">Ref: {$transaction.product_reference|escape:'htmlall':'UTF-8'}</small>
        {/if}
    </td>
    <td class="text-center">
        <span class="badge badge-info">{$transaction.product_quantity|intval}</span>
    </td>
    <td class="text-right">
        <strong>{$transaction.vendor_amount|number_format:'2'}</strong>
    </td>
    <td class="text-center">
        <span class="mv-status-badge"
            style="background-color: {$transaction.status_color|escape:'htmlall':'UTF-8'}; color: white; padding: 4px 8px; border-radius: 3px;">
            {$transaction.status_name|escape:'htmlall':'UTF-8'}
        </span>
    </td>
    <td class="text-center">
        <small>{$transaction.order_date|date_format:'%Y-%m-%d'}</small>
    </td>
</tr>