<!-- Template de vue du manifeste -->
<div class="panel">
    <div class="panel-heading">
        <i class="icon-list"></i> {l s='Détails du manifeste' mod='multivendor'}
        <span class="badge manifest-count">{$total_items}</span>
    </div>
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            <h4>{l s='Statut du manifeste' mod='multivendor'}</h4>
            <form method="post" action="{$smarty.server.REQUEST_URI}">
                <div class="row">
                    <div class="col-md-4">
                        <select name="id_manifest_status" class="form-control" onchange="this.form.submit()">
                            {foreach $available_statuses as $status}
                                <option value="{$status.id_manifest_status_type}"
                                    {if $manifest->id_manifest_status == $status.id_manifest_status_type}selected{/if}>
                                    {$status.name|escape:'html':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-md-8">
                        <p class="help-block">
                            {l s='Sélectionnez le statut pour mettre à jour le manifeste et les lignes de commande associées' mod='multivendor'}
                        </p>
                    </div>
                </div>
                <input type="hidden" name="submitUpdateManifestStatus" value="1">
                <input type="hidden" name="id_manifest" value="{$manifest->id}">
            </form>
        </div>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <h4>{l s='Informations du manifeste' mod='multivendor'}</h4>
                <dl class="dl-horizontal">
                    <dt>{l s='Référence:' mod='multivendor'}</dt>
                    <dd>{$manifest->reference}</dd>

                    <dt>{l s='Vendeur:' mod='multivendor'}</dt>
                    <dd>{$vendor_name}</dd>

                    <dt>{l s='Type:' mod='multivendor'}</dt>
                    <dd>
                        {$manifestType}
                    </dd>

                    <dt>{l s='Statut:' mod='multivendor'}</dt>
                    {* <dd>
                        <span class="badge badge-info">{$manifest->status}</span>
                    </dd> *}

                    <dt>{l s='Créé:' mod='multivendor'}</dt>
                    <dd>{$manifest->date_add}</dd>
                </dl>
            </div>

            <div class="col-md-6">
                <h4>{l s='Adresse de récupération' mod='multivendor'}</h4>
                {if $address}
                    <address>
                        {$address->firstname} {$address->lastname}<br>
                        {$address->address1}<br>
                        {if $address->address2}{$address->address2}<br>{/if}
                        {$address->postcode} {$address->city}<br>
                        {if $address->phone}{l s='Téléphone:' mod='multivendor'} {$address->phone}<br>{/if}
                    </address>
                {else}
                    <p class="text-muted">{l s='Aucune adresse spécifiée' mod='multivendor'}</p>
                {/if}
            </div>
        </div>
        {if $manifest_details && count($manifest_details) > 0}
            <h4>{l s='Articles dans le manifeste' mod='multivendor'}</h4>
            <div class="table-responsive">
                <table class="table table-striped" id="manifest-details-table">
                    <thead>
                        <tr>
                            <th class="fixed-width-xs center">{l s='ID' mod='multivendor'}</th>
                            <th>{l s='ID Commande' mod='multivendor'}</th>
                            <th class="fixed-width-sm center">{l s='ID Détail' mod='multivendor'}</th>
                            <th>{l s='Vendeur' mod='multivendor'}</th>
                            <th>{l s='Nom' mod='multivendor'}</th>
                            <th class="center">{l s='Référence produit' mod='multivendor'}</th>
                            <th class="fixed-width-xs center">{l s='QTÉ' mod='multivendor'}</th>
                            <th>{l s='Montant vendeur' mod='multivendor'}</th>
                            <th>{l s='Statut de paiement' mod='multivendor'}</th>
                            <th>{l s='Statut de commande' mod='multivendor'}</th>
                            <th>{l s='Statut ligne de commande' mod='multivendor'}</th>
                            <th>{l s='Date de commande' mod='multivendor'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $manifest_details as $detail}
                            <tr>
                                <td class="center">{$detail.id_manifest|default:'-'}</td>
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
                                        <span class="badge"
                                            style="background-color: #6c757d; color: white;">{l s='Non payé' mod='multivendor'}</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $detail.order_state_name}
                                        <span class="badge"
                                            style="background-color: {$detail.order_state_color|default:'#6c757d'}; color: white;">
                                            {$detail.order_state_name|escape:'html':'UTF-8'}
                                        </span>
                                    {else}
                                        <span class="badge badge-secondary">{l s='Inconnu' mod='multivendor'}</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $detail.line_status}
                                        <span class="badge"
                                            style="background-color: {$detail.status_color|default:'#6c757d'}; color: white;">
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
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <div class="alert alert-info">
                <i class="icon-info-circle"></i>
                {l s='Aucun article dans ce manifeste.' mod='multivendor'}
            </div>
        {/if}
    </div>

    <div class="panel-footer">
        <a href="{$back_url}" class="btn btn-default">
            <i class="process-icon-back"></i> {l s='Retour à la liste' mod='multivendor'}
        </a>
        {assign var='isEditable' value=Manifest::isEditable($manifest->id)}
        {if $isEditable}
            <a href="{$link->getAdminLink('AdminManifest')}&id_manifest={$manifest->id}&updatemv_manifest"
                class="btn btn-primary">
                <i class="process-icon-edit"></i> {l s='Modifier le manifeste' mod='multivendor'}
            </a>
        {/if}

        {assign var='isDeletible' value=Manifest::isDeletable($manifest->id)}
        {if $isDeletible}
            <a href="{$link->getAdminLink('AdminManifest')}&id_manifest={$manifest->id}&deletemv_manifest&token={$token}"
                class="btn btn-danger" onclick="return confirm('{l s='Êtes-vous sûr ?' mod='multivendor'}')">
                <i class="process-icon-delete"></i> {l s='Supprimer le manifeste' mod='multivendor'}
            </a>
        {/if}

        <button type="button" class="btn btn-info" id="print-manifest">
            <i class="icon-print"></i> {l s='Imprimer le manifeste' mod='multivendor'}
        </button>
    </div>
</div>
<script type="text/javascript">
    // Mise à jour du gestionnaire du bouton d'impression du manifeste
    $('#print-manifest').on('click', function() {
        var manifestId = {$manifest->id|intval};

        window.open(
            '{$link->getAdminLink("AdminManifest")}&printManifest&id_manifest=' + manifestId + '&token=' + manifestToken,
            '_blank'
        );
    });
</script>


<style>
    .icon-print {
        background: transparent;
        background-position: 50%;
        background-size: 26px;
        display: block;
        font-size: 28px;
        height: 30px;
        margin: 0 auto;
        width: 30px;

    }
</style>