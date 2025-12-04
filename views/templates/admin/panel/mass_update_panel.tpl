{*
* Mass Update Panel Template with AJAX
* Path: views/templates/admin/mass_update_panel.tpl
*}

<div class="panel" id="mass-update-panel">
    <div class="panel-heading">
        <i class="icon-edit"></i> {l s='Action de masse' mod='multivendor'}

    </div>
    <div class="panel-body" id="mass-update-content">
        <div class="row" id="checkbox-update-section">
            <div class="form-horizontal">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label"> {l s='Entrée de la barre de code' mod='multivendor'}<strong>
                                    <small>(<span id="ajax-selected-count-display">0
                                            {l s='sélectionné(s)' mod='multivendor'}</span>)</small></strong>:
                            </label>
                            <input type="text" name="barre_code" id="barcode" class="form-control"
                                placeholder="123456789" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="checkbox-update-section">
            <div class="form-horizontal">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">{l s='Nouveau statut' mod='multivendor'} :</label>
                            <select id="ajax-bulk-status-select" class="form-control" required>
                                <option value="">{l s='Sélectionner un statut' mod='multivendor'}</option>
                                {foreach from=$status_types item=status}
                                    <option value="{$status.id_order_line_status_type|intval}">
                                        {$status.name|escape:'html':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">{l s='Commentaire' mod='multivendor'} :</label>
                            <input type="text" id="ajax-bulk-comment" class="form-control" value="Bulk update"
                                placeholder="{l s='Commentaire optionnel' mod='multivendor'}" disabled />
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-primary" id="ajax-bulk-update-btn">
                                    <i class="icon-check"></i> {l s='Mettre à jour la sélection' mod='multivendor'}
                                </button>

                            </div>
                        </div>
                    </div>
                </div>

                {* Progress bar *}
                <div class="row" id="ajax-progress-section" style="display: none;">
                    <div class="col-md-12">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="ajax-progress-text" class="text-center"></div>
                    </div>
                </div>

                {* Results display *}
                <div class="row" id="ajax-results-section" style="display: none;">
                    <div class="col-md-12">
                        <div id="ajax-results-content"></div>
                    </div>
                </div>
            </div>
        </div>
        {assign var="isSuperAdmin" value=Context::getContext()->employee->isSuperAdmin() }
        {if $isSuperAdmin}
            <div class="row">
                <button type="button" class="btn btn-primary" id="revert-button">
                    Retourner au statut précédent
                </button>
            </div>
        {/if}

        <hr>
    </div>
    {assign var="changeby" value=EntityLogHelper::getChangedBy()}


</div>



<script type="text/javascript">
    $(document).ready(function() {
        // AJAX URL for mass update
        var ajaxUrl = '{$current_index|escape:'html':'UTF-8'}&ajax=1&action=ajaxMassUpdateStatus&token={$token|escape:'html':'UTF-8'}';
        var ajaxUrlBarcode ='{$current_index|escape:'html':'UTF-8'}&ajax=1&action=checkBarCode&token={$token|escape:'html':'UTF-8'}'
        var ajaxUrlRevert ='{$current_index|escape:'html':'UTF-8'}&ajax=1&action=Revert&token={$token|escape:'html':'UTF-8'}'

        // Update selected count display
        function updateSelectedCount() {
            var checkedBoxes = $("input[name='vendor_order_detailsBox[]']:checked");

            var count = checkedBoxes.length;
            $("#ajax-selected-count-display").text(count + " {l s='sélectionné(s)' mod='multivendor'}");

            // Enable/disable bulk update button
            $("#ajax-bulk-update-btn").prop("disabled", count === 0);
        }

        // Listen for checkbox changes
        $(document).on("change", "input[name='vendor_order_detailsBox[]'], input[name='checkme']", function() {
            updateSelectedCount();
        });

        // Initial count update
        updateSelectedCount();


        // Handle AJAX bulk update
        $("#ajax-bulk-update-btn").on("click", function(e) {
                e.preventDefault();

                var checkedBoxes = $("input[name='vendor_order_detailsBox[]']:checked");
                if (checkedBoxes.length === 0) {
                    alert("{l s='Veuillez sélectionner au moins un élément.' mod='multivendor'}");
                    return false;
                }

                var newStatusId = $("#ajax-bulk-status-select").val();
                if (!newStatusId) {
                    alert("{l s='Veuillez sélectionner un nouveau statut.' mod='multivendor'}");
                    return false;
                }

                var comment = $("#ajax-bulk-comment").val() || 'Mise à jour en masse via AJAX';

                if (!confirm("{l s='Êtes-vous sûr de vouloir mettre à jour le statut des éléments sélectionnés ?' mod='multivendor'}")) {
                return false;
            }

            // Collect selected IDs
            var selectedIds = []; checkedBoxes.each(function() {
                selectedIds.push($(this).val());
            });


            // Start AJAX mass update
            performAjaxMassUpdate(selectedIds, newStatusId, comment);
        });

    // Perform AJAX mass update
    function performAjaxMassUpdate(selectedIds, newStatusId, comment) {
        // Show progress section
        $("#ajax-progress-section").show();
        $("#ajax-results-section").hide();

        // Disable button and show loading
        $("#ajax-bulk-update-btn").prop("disabled", true).html('<i class="icon-spinner icon-spin"></i> {l s='Mise à jour en cours...' mod='multivendor'}');

        // Initialize progress
        var total = selectedIds.length;
        var processed = 0;
        var success = 0;
        var errors = 0;
        var errorMessages = [];

        updateProgress(0, total, "Début de la mise à jour...");

        // Process each ID sequentially
        processNextItem();

        function processNextItem() {
            if (processed >= total) {
                // All items processed
                completeUpdate();
                return;
            }

            var currentId = selectedIds[processed];
            updateProgress(processed + 1, total, "Mise à jour de l'élément " + (processed + 1) + "/" + total);

            // AJAX call for single item
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    id: currentId,
                    status_id: newStatusId,
                    comment: comment
                },
                success: function(response) {
                    processed++;
                    if (response.success) {
                        success++;
                    } else {
                        errors++;
                        errorMessages.push("ID " + currentId + ": " + (response.message ||
                            'Erreur inconnue'));
                    }

                    // Process next item
                    setTimeout(processNextItem, 100); // Small delay to prevent server overload
                },
                error: function(xhr, status, error) {
                    processed++;
                    errors++;
                    errorMessages.push("ID " + currentId + ": Erreur AJAX - " + error);

                    // Process next item
                    setTimeout(processNextItem, 100);
                }
            });
        }

        function completeUpdate() {
            // Hide progress bar
            $("#ajax-progress-section").hide();

            // Show results
            var resultsHtml = '<div class="alert ' + (errors === 0 ? 'alert-success' : 'alert-warning') + '">';
            resultsHtml += '<h4>Résultats de la mise à jour :</h4>';
            resultsHtml += '<ul>';
            resultsHtml += '<li><strong>Total traité :</strong> ' + total + '</li>';
            resultsHtml += '<li><strong>Succès :</strong> ' + success + '</li>';
            resultsHtml += '<li><strong>Erreurs :</strong> ' + errors + '</li>';
            resultsHtml += '</ul>';

            if (errorMessages.length > 0) {
                resultsHtml += '<h5>Détails des erreurs :</h5>';
                resultsHtml += '<ul>';
                errorMessages.forEach(function(msg) {
                    resultsHtml += '<li>' + msg + '</li>';
                });
                resultsHtml += '</ul>';
            }

            resultsHtml += '</div>';

            $("#ajax-results-content").html(resultsHtml);
            $("#ajax-results-section").show();

            // Reset button
            $("#ajax-bulk-update-btn").prop("disabled", false).html('<i class="icon-check"></i> {l s='Mettre à jour la sélection' mod='multivendor'}');

            // Clear selections
            $("input[name='vendor_order_detailsBox[]']").prop('checked', false);
            $("input[name='checkme']").prop('checked', false);
            updateSelectedCount();

            // Clear form
            $("#ajax-bulk-status-select").val('');
            $("#ajax-bulk-comment").val('');

            // Refresh page after delay if all successful
            if (errors === 0 && success > 0) {
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        }

        function updateProgress(current, total, message) {
            var percentage = Math.round((current / total) * 100);
            $(".progress-bar").css("width", percentage + "%").attr("aria-valuenow", percentage);
            $("#ajax-progress-text").text(message + " (" + percentage + "%)");
        }
    }
    let barcodeTimer;

    $('#barcode').on('input', function() {
        clearTimeout(barcodeTimer); // reset timer every time input changes

        barcodeTimer = setTimeout(function() {
            let barcode = $('#barcode').val().trim();

            // 1️⃣ ERROR: no barcode entered
            if (!barcode) {
                if (typeof window.showErrorMessage === 'function') {
                    window.showErrorMessage('Veuillez saisir un code barre.', 'error');
                } else {
                    alert('Veuillez saisir un code barre.');
                }
                return;
            }

            // Find all spans with the matching data-mpn
            let $spans = $('span[data-mpn="' + barcode + '"]');
            let checkedOne = false;

            $spans.each(function() {
                if (checkedOne) return false; // stop after checking one

                let $row = $(this).closest('tr');
                let $checkbox = $row.find('input[name="vendor_order_detailsBox[]"]');

                if ($checkbox.length && !$checkbox.is(':checked')) {
                    $checkbox.prop('checked', true).trigger('change');
                    checkedOne = true;

                    if (typeof window.showSuccessMessage === 'function') {
                        window.showSuccessMessage(
                            'Code barre ' + barcode + ' sélectionné avec succès',
                            'success'
                        );
                    }

                    $('#barcode').val('');
                }
            });

            // 2️⃣ ERROR: barcode exists, but all matching rows are already checked OR no matches
            if (!checkedOne) {
                if ($spans.length === 0) {
                    // No rows with this barcode at all
                    if (typeof window.showErrorMessage === 'function') {
                        window.showErrorMessage(
                            'Aucun produit trouvé pour le code barre : ' + barcode,
                            'error'
                        );
                    } else {
                        alert('Aucun produit trouvé pour ce code barre.');
                    }
                } else {
                    // Exists but all are checked
                    if (typeof window.showErrorMessage === 'function') {
                        window.showErrorMessage(
                            'Plus aucun produit disponible pour le code barre : ' + barcode,
                            'error'
                        );
                    } else {
                        alert('Plus aucun produit disponible pour ce code barre.');
                    }
                }
            }
        }, 500);
    });


    $("#revert-button").on('click', function(e) {
        e.preventDefault();
        var checkedBoxes = $("input[name='vendor_order_detailsBox[]']:checked");
        if (checkedBoxes.length === 0) {
            alert("{l s='Veuillez sélectionner au moins un élément.' mod='multivendor'}");
            return false;
        }
        var selectedIds = [];
        checkedBoxes.each(function() {
            selectedIds.push($(this).val());
        });

        $.ajax({
            url: ajaxUrlRevert,
            type: 'POST',
            dataType: 'json',
            data: {
                ids: selectedIds,
                changeby : '{$changeby}'
            },
            success: function(response) {
                if (response.success) {
                    window.showSuccessMessage(
                        response.message
                    );
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } else {
                    window.showErrorMessage(
                        response.message
                    );
                }

            },
            error: function(xhr, status, error) {

            }
        });
    });

    });
</script>