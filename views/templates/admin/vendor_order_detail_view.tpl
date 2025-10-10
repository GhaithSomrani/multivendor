{*
* Vendor Order Detail View Template
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-eye"></i>
        {l s='Vendor Order Detail Information' mod='multivendor'}
        {if isset($vendor_order_detail)}
            - #{$vendor_order_detail->id}
        {/if}
    </div>

    <div class="panel-body">
        {if isset($vendor_order_detail)}
            <div class="row">
                {* Order Information *}
                <div class="col-md-6">
                    <h4>{l s='Order Information' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>{l s='Order ID:' mod='multivendor'}</strong></td>
                            <td>
                                <a href="{$link->getAdminLink('AdminOrders' ,true , ['id_order' =>$order->id, 'vieworder'=>1 ])}"
                                    target="_blank">
                                    #{$order->id}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Order Reference:' mod='multivendor'}</strong></td>
                            <td> <a href="{$link->getAdminLink('AdminOrders' ,true , ['id_order' =>$order->id, 'vieworder'=>1 ])}"
                                    target="_blank"> {$order->reference} </a></td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Order Detail ID:' mod='multivendor'}</strong></td>
                            <td>#{$vendor_order_detail->id_order_detail}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Order Date:' mod='multivendor'}</strong></td>
                            <td>{dateFormat date=$order->date_add full=1}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Order Total:' mod='multivendor'}</strong></td>
                            <td>{$order->total_paid|number_format:3} TND</td>
                        </tr>
                    </table>
                </div>

                {* Vendor Information *}
                <div class="col-md-6">
                    <h4>{l s='Vendor Information' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>{l s='Vendor ID:' mod='multivendor'}</strong></td>
                            <td>#{$vendor->id}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Shop Name:' mod='multivendor'}</strong></td>
                            <td>{$vendor->shop_name}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Supplier ID:' mod='multivendor'}</strong></td>
                            <td>#{$vendor->id_supplier}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Vendor Status:' mod='multivendor'}</strong></td>
                            <td>
                                {if $vendor->status == 1}
                                    <span class="badge badge-success">{l s='Active' mod='multivendor'}</span>
                                {elseif $vendor->status == 0}
                                    <span class="badge badge-warning">{l s='Pending' mod='multivendor'}</span>
                                {else}
                                    <span class="badge badge-danger">{l s='Rejected' mod='multivendor'}</span>
                                {/if}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row">
                {* Product Information *}
                <div class="col-md-6">
                    <h4>{l s='Product Information' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>{l s='Product ID:' mod='multivendor'}</strong></td>
                            <td>#{$vendor_order_detail->product_id}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Product Name:' mod='multivendor'}</strong></td>
                            <td>{$vendor_order_detail->product_name}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Product Reference (SKU):' mod='multivendor'}</strong></td>
                            <td>{$vendor_order_detail->product_reference|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Product MPN:' mod='multivendor'}</strong></td>
                            <td>{$vendor_order_detail->product_mpn|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Unit Price:' mod='multivendor'}</strong></td>
                            <td>{$vendor_order_detail->product_price|number_format:3} TND</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Quantity:' mod='multivendor'}</strong></td>
                            <td>{$vendor_order_detail->product_quantity}</td>
                        </tr>
                    </table>
                </div>

                {* Commission Information *}
                <div class="col-md-6">
                    <h4>{l s='Commission Information' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>{l s='Commission Rate:' mod='multivendor'}</strong></td>
                            <td>{$vendor_order_detail->commission_rate}%</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Commission Amount:' mod='multivendor'}</strong></td>
                            <td class="text-warning">
                                {$vendor_order_detail->commission_amount|number_format:3} TND</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Vendor Amount:' mod='multivendor'}</strong></td>
                            <td class="text-success">{$vendor_order_detail->vendor_amount|number_format:3}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Total Product Value:' mod='multivendor'}</strong></td>
                            <td class="text-info">
                                {assign var="total_value" value=($vendor_order_detail->commission_amount + $vendor_order_detail->vendor_amount)}
                                {$total_value|number_format:3}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {* Current Status Information *}
            <div class="row">
                <div class="col-md-12">
                    <h4>{l s='Current Status Information' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td width="20%"><strong>{l s='Current Status:' mod='multivendor'}</strong></td>
                            <td>
                                {if isset($status_info) && $status_info}
                                    <span class="badge"
                                        style="background-color: {$status_info.color|default:'#777'}; color: white;">
                                        {$status_info.status_name}
                                    </span>
                                {else}
                                    <span class="badge badge-secondary">{l s='Pending' mod='multivendor'}</span>
                                {/if}
                            </td>
                        </tr>
                        {if isset($status_info) && $status_info && $status_info.comment}
                            <tr>
                                <td><strong>{l s='Status Comment:' mod='multivendor'}</strong></td>
                                <td><pre>{$status_info.comment}</pre></td>
                            </tr>
                        {/if}
                        {if isset($status_info) && $status_info && $status_info.date_upd}
                            <tr>
                                <td><strong>{l s='Last Status Update:' mod='multivendor'}</strong></td>
                                <td>{dateFormat date=$status_info.date_upd full=1}</td>
                            </tr>
                        {/if}
                    </table>
                </div>
            </div>

            {* Status History *}
            {if isset($status_history) && count($status_history) > 0}
                <div class="row">
                    <div class="col-md-12">
                        <h4>{l s='Status History' mod='multivendor'}</h4>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>{l s='Date' mod='multivendor'}</th>
                                        <th>{l s='Old Status' mod='multivendor'}</th>
                                        <th>{l s='New Status' mod='multivendor'}</th>
                                        <th>{l s='Changed By' mod='multivendor'}</th>
                                        <th>{l s='Comment' mod='multivendor'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$status_history item=log}
                                        <tr>
                                            <td>{dateFormat date=$log.date_add full=1}</td>
                                            <td>
                                                {if $log.old_status_name}
                                                    <span class="badge"
                                                        style="background-color: {$log.old_status_color|default:'#777'}; color: white;">
                                                        {$log.old_status_name}
                                                    </span>
                                                {else}
                                                    <span class="text-muted">{l s='Initial' mod='multivendor'}</span>
                                                {/if}
                                            </td>
                                            <td>
                                                <span class="badge"
                                                    style="background-color: {$log.new_status_color|default:'#777'}; color: white;">
                                                    {$log.new_status_name}
                                                </span>
                                            </td>
                                            <td>
                                                {$log.changed_by_firstname} {$log.changed_by_lastname}
                                            </td>
                                            <td>
                                                <pre>{$log.comment}</pre>    
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            {/if}

        {else}
            <div class="alert alert-warning">
                {l s='Vendor order detail information not found.' mod='multivendor'}
            </div>
        {/if}
    </div>
</div>