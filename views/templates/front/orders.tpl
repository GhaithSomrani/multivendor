{*
    * Vendor Orders Template
    *}
    
    {extends file='page.tpl'}
    
    {block name='page_title'}
        {l s='My Order Lines' mod='multivendor'}
    {/block}
    
    {block name='page_content'}
        <div class="vendor-orders">
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
                                    <a class="nav-link" href="#">
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
                        <div class="card-header">
                            <h3 class="card-title">{l s='My Order Lines' mod='multivendor'}</h3>
                        </div>
                        <div class="card-body">
                            {if isset($success)}
                                {foreach $success as $msg}
                                    <div class="alert alert-success">
                                        {$msg}
                                    </div>
                                {/foreach}
                            {/if}
                            
                            {if isset($errors)}
                                {foreach $errors as $error}
                                    <div class="alert alert-danger">
                                        {$error}
                                    </div>
                                {/foreach}
                            {/if}
                            
                            {if $order_lines}
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>{l s='Reference' mod='multivendor'}</th>
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
                                            {foreach from=$order_lines item=line}
                                                <tr>
                                                    <td>#{$line.order_reference}#{$line.id_order_detail}</td>
                                                    <td>{$line.product_name}</td>
                                                    <td>{$line.product_price|displayPrice}</td>
                                                    <td>{$line.product_quantity}</td>
                                                    <td>{($line.product_price * $line.product_quantity)|displayPrice}</td>
                                                    <td>{$line.commission_amount|displayPrice}</td>
                                                    <td>
                                                        <span class="badge" style="background-color: {$status_colors[$line.line_status]}; color: white;">
                                                            {$line.line_status|capitalize|default:'Pending'}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm update-status-btn" data-toggle="modal" data-target="#updateStatus-{$line.id_order_detail}">
                                                            <i class="material-icons">edit</i>
                                                            {l s='Update Status' mod='multivendor'}
                                                        </button>
                                                        
                                                        {* Status Update Modal *}
                                                        <div class="modal fade" id="updateStatus-{$line.id_order_detail}" tabindex="-1" role="dialog" aria-labelledby="updateStatusLabel-{$line.id_order_detail}" aria-hidden="true">
                                                            <div class="modal-dialog" role="document">
                                                                <form action="{$link->getModuleLink('multivendor', 'orders')}" method="post">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="updateStatusLabel-{$line.id_order_detail}">{l s='Update Order Line Status' mod='multivendor'}</h5>
                                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                <span aria-hidden="true">&times;</span>
                                                                            </button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="id_order_detail" value="{$line.id_order_detail}">
                                                                            
                                                                            <div class="form-group">
                                                                                <label for="status-{$line.id_order_detail}">{l s='Status' mod='multivendor'}</label>
                                                                                <select class="form-control" id="status-{$line.id_order_detail}" name="status">
                                                                                    {foreach from=$statuses key=status_key item=status_label}
                                                                                    <option value="{$status_key}" {if $line.line_status == $status_key}selected{/if} style="background-color: {$status_colors[$status_key]}">
                                                                                        {$status_label|escape:'html':'UTF-8'|capitalize}
                                                                                    </option>
                                                                                    {/foreach}
                                                                                </select>
                                                                            </div>
                                                                            
                                                                            <div class="form-group">
                                                                                <label for="comment-{$line.id_order_detail}">{l s='Comment (optional)' mod='multivendor'}</label>
                                                                                <textarea class="form-control" id="comment-{$line.id_order_detail}" name="comment" rows="3"></textarea>
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
                                                    </td>
                                                </tr>
                                            {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                                
                                {* Pagination *}
                                {if $pages_nb > 1}
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            {if $current_page > 1}
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => 1])}" aria-label="First">
                                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page-1])}" aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            {/if}
                                            
                                            {assign var=p_start value=max(1, $current_page-2)}
                                            {assign var=p_end value=min($pages_nb, $current_page+2)}
                                            
                                            {for $p=$p_start to $p_end}
                                                <li class="page-item {if $p == $current_page}active{/if}">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $p])}">{$p}</a>
                                                </li>
                                            {/for}
                                            
                                            {if $current_page < $pages_nb}
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $current_page+1])}" aria-label="Next">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="{$link->getModuleLink('multivendor', 'orders', ['page' => $pages_nb])}" aria-label="Last">
                                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                                    </a>
                                                </li>
                                            {/if}
                                        </ul>
                                    </nav>
                                {/if}
                            {else}
                                <div class="alert alert-info">
                                    {l s='No order lines found.' mod='multivendor'}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/block}