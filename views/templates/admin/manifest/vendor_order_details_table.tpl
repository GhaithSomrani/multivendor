 <table class="table">
        <thead>
            <tr>
                <th>
                    <input type="checkbox" id="check_all" />
                </th>
                <th>{l s='Order Ref'}</th>
                <th>{l s='Product Name'}</th>
                <th>{l s='Reference'}</th>
                <th>{l s='MPN'}</th>
                <th>{l s='Qty'}</th>
                <th>{l s='Price'}</th>
                <th>{l s='Commission'}</th>
                <th>{l s='Vendor Amount'}</th>
                <th>{l s='Order Date'}</th>
                <th>{l s='Line Status'}</th>
                <th>{l s='Payment Status'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$order_details item=detail}
                <tr>
                    <td>
                        <input type="checkbox" name="order_details[]" value="{$detail.id_order_detail}" class="check_item" />
                    </td>
                    <td>{$detail.order_reference}</td>
                    <td>{$detail.product_name}</td>
                    <td>{$detail.product_reference}</td>
                    <td>{$detail.product_mpn}</td>
                    <td>{$detail.product_quantity}</td>
                    <td>{$detail.product_price}</td>
                    <td>{$detail.commission_amount}</td>
                    <td>{$detail.vendor_amount}</td>
                    <td>{$detail.order_date}</td>
                    <td>
                        <span style="color: {$detail.status_color}; font-weight: bold;">
                            {$detail.line_status}
                        </span>
                    </td>
                    <td>{$detail.payment_status}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>