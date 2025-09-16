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
                        <label for="export_status_type">Order Line Status*:</label>
                        <select name="export_status_type" id="export_status_type" class="form-control">
                            {foreach from=$status_types item=status}
                                <option value="{$status.id_order_line_status_type|intval}">
                                    {$status.name|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="export_vendor">Vendor:</label>
                        <select name="export_vendor" id="export_vendor" class="form-control">
                            <option value="">-- Select Vendor --</option>
                            {foreach from=$vendors item=vendor}
                                <option value="{$vendor.id_vendor|intval}">{$vendor.shop_name|escape:'html':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="export_type">Export Type:</label>
                        <select name="export_type" id="export_type" class="form-control" required>
                            {foreach from=$manifest_types item=type}
                            {if $type.id != 1}  <option value="{$type.id}">{$type.name}</option> {/if}
                            {/foreach}
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-1">
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-download"></i> Export PDF
                        </button>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <button id="exportselectd" class="btn btn-primary">
                            <i class="icon-download"></i> Les status selectiones <span id="nbreStatus"> 0 </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        var ajaxUrl = '{$current_index|escape:'html':'UTF-8'}&ajax=1&action=exportSelectedIds&token={$token|escape:'html':'UTF-8'}';

        function updateSelectedCount() {
            var selectedCount = $('input[name="vendor_order_detailsBox[]"]:checked').length;
            $('#nbreStatus').text(selectedCount);
        }

        $('input[name="vendor_order_detailsBox[]"]').on('change', function() {
            updateSelectedCount();
        });

        $('#exportselectd').on('click', function(e) {
            e.preventDefault();
            var selectedStatus = [];
            var exportType = $('#export_type').val();
            var vendorId = $('#export_vendor').val();
            console.log(exportType, vendorId);
            $('input[name="vendor_order_detailsBox[]"]:checked').each(function() {
                selectedStatus.push($(this).val());
            });

            if (selectedStatus.length === 0) {
                alert('Please select at least one item to export.');
                return;
            }

            console.log(exportType);
            console.log(selectedStatus);

            // Show loading indicator
            $('#exportselectd').prop('disabled', true).text('Generating PDF...');

            // METHOD 2: AJAX with blob handling
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxUrl, true);
            xhr.responseType = 'blob';

            xhr.onload = function() {
                $('#exportselectd').prop('disabled', false).text('Export Selected');

                if (xhr.status === 200) {
                    var blob = xhr.response;
                    var url = window.URL.createObjectURL(blob);
                    window.open(url, '_blank');
                    window.URL.revokeObjectURL(url);
                } else {
                    alert('Error generating export. Please try again.');
                }
            };

            xhr.onerror = function() {
                $('#exportselectd').prop('disabled', false).text('Export Selected');
                alert('Error generating PDF. Please try again.');
            };

            // Prepare form data
            var formData = new FormData();
            selectedStatus.forEach(function(id, index) {
                formData.append('ids[' + index + ']', id);
            });
            formData.append('export_type', exportType);
            formData.append('vendor_id', vendorId);

            xhr.send(formData);
        });
    });
</script>