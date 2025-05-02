{*
    * Vendor Order Detail Template
    *}
    
    {extends file='page.tpl'}
    
    {block name='page_title'}
        {l s='Order Details' mod='multivendor'} #{$order->reference}
    {/block}
    
    {block name='page_content'}
        <div class="vendor-order-detail">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{l s='Navigation' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_dashboard_url}">
                                        <i class="material-icons">dashboard</i>
                                        {l s='Dashboard' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item active">
                                    <a class="nav-link" href="{$back_url}">
                                        <i class="material-icons">shopping_cart</i>
                                        {l s='Orders' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_commissions_url}">
                                        <i class="material-icons">attach_money</i>
                                        {l s='Commissions' mod='multivendor'}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{$vendor_profile_url}">
                                        <i class="material-icons">store</i>
                                        {l s='Shop Profile' mod='multivendor'}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">{l s='Order' mod='multivendor'} #{$order->reference}</h3>
                            <a href="{$back_url}" class="btn btn-outline-secondary btn-sm">
                                <i class="material-icons">arrow_back</i>
                                {l s='Back to Orders' mod='multivendor'}
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">{l s='Order Date' mod='multivendor'}</h5>
                                            <p class="card-text">{$order->date_add|date_format:'%Y-%m-%d %H:%M:%S'}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">{l s='Order Status' mod='multivendor'}</h5>
                                            <p class="card-text">{$order->getCurrentStateName($order->current_state)}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">{l s='Total' mod='multivendor'}</h5>
                                            <p class="card-text">{$order->total_paid|displayPrice}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h4 class="mt-4">{l s='Your Products' mod='multivendor'}</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{l s='Product' mod='multivendor'}</th>
                                            <th>{l s='Unit Price' mod='multivendor'}</th>
                                            <th>{l s='Quantity' mod='multivendor'}</th>
                                            <th>{l s='Total' mod='multivendor'}</th>
                                            <th>{l s='Commission' mod='multivendor'}</th>
                                            <th>{l s='Status' mod='multivendor'}</th>
                                            <th>{l s='Actions' mod='multivendor'}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach from=$order_details item=detail}
                                            <tr>
                                                <td>{$detail.product_name}</td>
                                                <td>{$detail.product_price|displayPrice}</td>
                                                <td>{$detail.product_quantity}</td>
                                                <td>{($detail.product_price * $detail.product_quantity)|displayPrice}</td>
                                                <td>{$detail.commission_amount|displayPrice} ({$detail.commission_rate}%)</td>
                                                <td>
                                                    <span class="badge 
                                                        {if $detail.line_status == 'pending'}badge-warning
                                                        {elseif $detail.line_status == 'processing'}badge-info
                                                        {elseif $detail.line_status == 'shipped'}badge-success
                                                        {elseif $detail.line_status == 'cancelled'}badge-danger
                                                        {else}badge-secondary{/if}">
                                                        {$detail.line_status|capitalize}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm update-status-btn" data-toggle="modal" data-target="#updateStatus-{$detail.id_order_detail}">
                                                        <i class="material-icons">edit</i>
                                                        {l s='Update Status' mod='multivendor'}
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            {* Status Update Modal *}
                                            <div class="modal fade" id="updateStatus-{$detail.id_order_detail}" tabindex="-1" role="dialog" aria-labelledby="updateStatusLabel-{$detail.id_order_detail}" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <form action="{$link->getModuleLink('multivendor', 'orders', ['id_order' => $order->id])}" method="post">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="updateStatusLabel-{$detail.id_order_detail}">{l s='Update Order Line Status' mod='multivendor'}</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id_order_detail" value="{$detail.id_order_detail}">
                                                                <input type="hidden" name="id_order" value="{$order->id}">
                                                                
                                                                <div class="form-group">
                                                                    <label for="status-{$detail.id_order_detail}">{l s='Status' mod='multivendor'}</label>
                                                                    <select class="form-control" id="status-{$detail.id_order_detail}" name="status">
                                                                        {foreach from=$statuses key=status_key item=status_label}
                                                                            <option value="{$status_key}" {if $detail.line_status == $status_key}selected{/if}>{$status_label}</option>
                                                                        {/foreach}
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label for="comment-{$detail.id_order_detail}">{l s='Comment (optional)' mod='multivendor'}</label>
                                                                    <textarea class="form-control" id="comment-{$detail.id_order_detail}" name="comment" rows="3"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Cancel' mod='multivendor'}</button>
                                                                <button type="submit" name="submitStatusUpdate" class="btn btn-primary">{l s='Update Status' mod='multivendor'}</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                            
                            <h4 class="mt-4">{l s='Status History' mod='multivendor'}</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{l s='Product' mod='multivendor'}</th>
                                            <th>{l s='Date' mod='multivendor'}</th>
                                            <th>{l s='Old Status' mod='multivendor'}</th>
                                            <th>{l s='New Status' mod='multivendor'}</th>
                                            <th>{l s='Comment' mod='multivendor'}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach from=$order_details item=detail}
                                            {assign var='history' value=OrderLineStatusLog::getStatusHistory($detail.id_order_detail)}
                                            {foreach from=$history item=log}
                                                <tr>
                                                    <td>{$detail.product_name}</td>
                                                    <td>{$log.date_add|date_format:'%Y-%m-%d %H:%M:%S'}</td>
                                                    <td>
                                                        {if $log.old_status}
                                                            <span class="badge 
                                                                {if $log.old_status == 'pending'}badge-warning
                                                                {elseif $log.old_status == 'processing'}badge-info
                                                                {elseif $log.old_status == 'shipped'}badge-success
                                                                {elseif $log.old_status == 'cancelled'}badge-danger
                                                                {else}badge-secondary{/if}">
                                                                {$log.old_status|capitalize}
                                                            </span>
                                                        {else}
                                                            <span class="badge badge-secondary">{l s='New' mod='multivendor'}</span>
                                                        {/if}
                                                    </td>
                                                    <td>
                                                        <span class="badge 
                                                            {if $log.new_status == 'pending'}badge-warning
                                                            {elseif $log.new_status == 'processing'}badge-info
                                                            {elseif $log.new_status == 'shipped'}badge-success
                                                            {elseif $log.new_status == 'cancelled'}badge-danger
                                                            {else}badge-secondary{/if}">
                                                            {$log.new_status|capitalize}
                                                        </span>
                                                    </td>
                                                    <td>{$log.comment|default:'-'}</td>
                                                </tr>
                                            {/foreach}
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/block}