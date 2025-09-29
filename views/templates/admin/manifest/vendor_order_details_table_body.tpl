<!-- Vendor Order Details Table Body -->
{if $order_details && count($order_details) > 0}
    {foreach $order_details as $detail}
        {assign var='ischecked'  value=in_array($detail.id_order_detail,  $selected_ids )}
        {if !$ischecked}

            <tr data-order-detail-id="{$detail.id_order_detail}">
                <td class="text-center">
                    <input type="checkbox" class="order-detail-checkbox" name="selected_order_details[]"
                        value="{$detail.id_order_detail}" data-order-id="{$detail.id_order}" {if $ischecked}checked="checked" {/if}
                        {if $detail.checkbox_disabled && !$ischecked}disabled="disabled" {/if} />
                </td>

                <td class="center">
                    {if $detail.id_manifest }
                        <a href="{Context::getContext()->link->getAdminLink('AdminManifest')}&viewmv_manifest=&id_manifest={$detail.id_manifest}&token={Tools::getAdminTokenLite('AdminManifest')}"
                            class="order-reference-link" target="_blank">
                            {$detail.id_manifest}
                        </a>
                    {else}
                        -
                    {/if}
                </td>
                <td>{$detail.id_order}</td>
                <td class="center">{$detail.id_order_detail}</td>
                <td>{$detail.vendor_name|escape:'html':'UTF-8'}</td>
                <td>
                    <strong>{$detail.product_name|escape:'html':'UTF-8'}</strong>
                    {if $detail.product_mpn}
                        <br><small class="text-muted">MPN: {$detail.product_mpn}</small>
                    {/if}
                </td>
                <td class="center">
                    {if $detail.product_reference}
                        {$detail.product_reference|escape:'html':'UTF-8'}
                    {else}
                        <span class="text-muted">-</span>
                    {/if}
                </td>
                <td class="center">
                    <span class="badge badge-info">{$detail.product_quantity}</span>
                </td>
                <td>
                    {if $detail.vendor_amount}
                        {$detail.vendor_amount|number_format:3}
                    {else}
                        <span class="text-muted">-</span>
                    {/if}
                </td>
                <td>
                    {if $detail.payment_status}
                        <span class="badge"
                            style="background-color: {if $detail.payment_status == 'paid'}#28a745{elseif $detail.payment_status == 'pending'}#ffc107{else}#dc3545{/if}; color: white;">
                            {if $detail.payment_status == 'paid'}{l s='Payé' mod='multivendor'}
                            {elseif $detail.payment_status == 'pending'}{l s='En attente' mod='multivendor'}
                            {elseif $detail.payment_status == 'cancelled'}{l s='Annulé' mod='multivendor'}
                            {else}
                                {l s='Inconnu' mod='multivendor'}
                            {/if}
                        </span>
                    {else}
                        <span class="badge" style="background-color: #6c757d; color: white;">{l s='Non payé' mod='multivendor'}</span>
                    {/if}
                </td>
                <td>
                    {if $detail.order_state_name}
                        <span class="badge" style="background-color: {$detail.order_state_color|default:'#6c757d'}; color: white;">
                            {$detail.order_state_name|escape:'html':'UTF-8'}
                        </span>
                    {else}
                        <span class="badge badge-secondary">{l s='Inconnu' mod='multivendor'}</span>
                    {/if}
                </td>
                <td>
                    {if $detail.line_status}
                        <span class="badge" style="background-color: {$detail.status_color|default:'#6c757d'}; color: white;">
                            {$detail.line_status|escape:'html':'UTF-8'}
                        </span>
                    {else}
                        <span class="badge badge-secondary">{l s='Inconnu' mod='multivendor'}</span>
                    {/if}
                </td>
                <td>
                    {if $detail.order_date}
                        {$detail.order_date }
                    {else}
                        <span class="text-muted">-</span>
                    {/if}
                </td>
            </tr>
        {/if}
    {/foreach}
{else}
    <tr>
        <td colspan="13" class="text-center text-muted">{l s='No order details found.' mod='multivendor'}</td>
    </tr>
{/if}