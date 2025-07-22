{*
* Vendor Dashboard Manifest Hook Template
* File: views/templates/hook/vendor_dashboard_manifest.tpl
*}

<div class="manifest-dashboard-widget card">
    <div class="card-header">
        <h5 class="card-title">
            <i class="material-icons">assignment</i>
            {l s='Manifest Management' mod='multivendor'}
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-number">{$manifest_statistics.total_manifests}</div>
                    <div class="stat-label">{l s='Total Manifests' mod='multivendor'}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-number">{$manifest_statistics.total_items}</div>
                    <div class="stat-label">{l s='Total Items' mod='multivendor'}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="status-breakdown">
                    {if $manifest_statistics.by_status}
                        {foreach $manifest_statistics.by_status as $status => $data}
                            <div class="status-item">
                                <span class="badge badge-{$data.color}">{$data.count}</span>
                                <span class="status-name">{$data.label}</span>
                            </div>
                        {/foreach}
                    {else}
                        <div class="text-muted">{l s='No manifests created yet' mod='multivendor'}</div>
                    {/if}
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-12 text-center">
                <a href="{$manifest_url}" class="btn btn-primary">
                    <i class="material-icons">add_box</i>
                    {l s='Manage Manifests' mod='multivendor'}
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.manifest-dashboard-widget {
    margin-bottom: 20px;
}

.stat-box {
    text-align: center;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 4px;
    background: #f8f9fa;
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.status-breakdown {
    padding: 15px;
}

.status-item {
    display: inline-block;
    margin-right: 15px;
    margin-bottom: 5px;
}

.status-name {
    margin-left: 5px;
    font-size: 12px;
}
</style>