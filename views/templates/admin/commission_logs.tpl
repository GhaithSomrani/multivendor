{*
    * Admin Commission Logs Template
    *}
    
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-history"></i> {l s='Commission History' mod='multivendor'}
        </div>
        <div class="panel-body">
            {if $commission_logs}
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{l s='Date' mod='multivendor'}</th>
                                <th>{l s='Vendor' mod='multivendor'}</th>
                                <th>{l s='Category' mod='multivendor'}</th>
                                <th>{l s='Old Rate (%)' mod='multivendor'}</th>
                                <th>{l s='New Rate (%)' mod='multivendor'}</th>
                                <th>{l s='Changed By' mod='multivendor'}</th>
                                <th>{l s='Comment' mod='multivendor'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$commission_logs item=log}
                                <tr>
                                    <td>{$log.date_add|date_format:'%Y-%m-%d %H:%M:%S'}</td>
                                    <td>{$log.shop_name}</td>
                                    <td>{$log.category_name|default:'-'}</td>
                                    <td>{$log.old_commission_rate}%</td>
                                    <td>{$log.new_commission_rate}%</td>
                                    <td>{$log.employee_name}</td>
                                    <td>{$log.comment|default:'-'}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            {else}
                <p class="alert alert-info">{l s='No commission history available.' mod='multivendor'}</p>
            {/if}
        </div>
    </div>