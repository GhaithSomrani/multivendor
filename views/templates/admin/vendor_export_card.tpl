{* views/templates/admin/vendor_export_card.tpl *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-download"></i> Export Order Lines
    </div>
    <div class="panel-body">
        <form id="exportForm" method="post" action="{$current_index}&token={$token}">
            <input type="hidden" name="action" value="exportFilteredPDF" />
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="export_vendor">Vendor:</label>
                        <select name="export_vendor" id="export_vendor" class="form-control" required>
                            <option value="">-- Select Vendor --</option>
                            {foreach from=$vendors item=vendor}
                                <option value="{$vendor.id_vendor|intval}">{$vendor.shop_name|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="export_status_type">Order Line Status:</label>
                        <select name="export_status_type" id="export_status_type" class="form-control" required>
                            <option value="">-- Select Status --</option>
                            {foreach from=$status_types item=status}
                                <option value="{$status.id_order_line_status_type|intval}">{$status.name|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="export_date_from">From Date:</label>
                        <input type="date" name="export_date_from" id="export_date_from" class="form-control" required />
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="export_date_to">To Date:</label>
                        <input type="date" name="export_date_to" id="export_date_to" class="form-control" required />
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="export_type">Export Type:</label>
                        <select name="export_type" id="export_type" class="form-control" required>
                            <option value="">-- Select Type --</option>
                            <option value="pickup">Pickup</option>
                            <option value="retour">Retour</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-download"></i> Export PDF
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>