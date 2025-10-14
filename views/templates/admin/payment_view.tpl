{*
* Template des détails de paiement multi-vendeur avec bouton d'impression
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-money"></i>
        {l s='Détails de la transaction de paiement' mod='multivendor'}
        {if isset($payment)}
            - Paiement #{$payment->id}
        {/if}

        {* Bouton d'impression *}
        {if isset($print_url)}
            <div class="pull-right">
                <a href="{$print_url}" target="_blank" class="btn btn-default">
                    <i class="icon-print"></i> {l s='Imprimer le paiement' mod='multivendor'}
                </a>
            </div>
        {/if}
    </div>

    <div class="panel-body">
        {if isset($payment)}
            {* Informations de paiement *}
            <div class="row">
                <div class="col-md-6">
                    <h4>{l s='Informations de paiement' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>{l s='ID Paiement:' mod='multivendor'}</strong></td>
                            <td>#{$payment->id}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Montant:' mod='multivendor'}</strong></td>
                            <td class="text-success"><strong>{$payment->amount|number_format:3} TND</strong></td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Statut:' mod='multivendor'}</strong></td>
                            <td>
                                <span
                                    class="badge badge-{if $payment->status == 'completed'}success{elseif $payment->status == 'pending'}warning{elseif $payment->status == 'cancelled'}danger{else}default{/if}">
                                    {$payment->status|ucfirst}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Méthode de paiement:' mod='multivendor'}</strong></td>
                            <td>{$payment->payment_method|default:'N/A'|ucfirst}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Référence:' mod='multivendor'}</strong></td>
                            <td>{$payment->reference|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Date de création:' mod='multivendor'}</strong></td>
                            <td>{dateFormat date=$payment->date_add full=1}</td>
                        </tr>

                    </table>
                </div>

                <div class="col-md-6">
                    <h4>{l s='Informations du vendeur' mod='multivendor'}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>{l s='ID Vendeur:' mod='multivendor'}</strong></td>
                            <td>#{$vendor->id}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Nom de la boutique:' mod='multivendor'}</strong></td>
                            <td>{$vendor->shop_name|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Email:' mod='multivendor'}</strong></td>
                            <td>{$vendor->email|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Téléphone:' mod='multivendor'}</strong></td>
                            <td>{$vendor->phone|default:'N/A'}</td>
                        </tr>
                        <tr>
                            <td><strong>{l s='Statut:' mod='multivendor'}</strong></td>
                            <td>
                                <span
                                    class="badge badge-{if $vendor->status == 'approved'}success{elseif $vendor->status == 'pending'}warning{else}danger{/if}">
                                    {$vendor->status|ucfirst}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {* Détails de la transaction *}
            {if isset($transaction_details) && $transaction_details}
                <div class="row">
                    <div class="col-md-12">
                        <h4>{l s='Détails de la transaction' mod='multivendor'}</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{l s='Commande' mod='multivendor'}</th>
                                        <th>{l s='Produit' mod='multivendor'}</th>
                                        <th>{l s='Montant vendeur' mod='multivendor'}</th>
                                        <th>{l s='Type' mod='multivendor'}</th>
                                        <th>{l s='manifest' mod='multivendor'}</th>
                                        <th>{l s='Date de commande' mod='multivendor'}</th>
                                        <th>{l s='Statut' mod='multivendor'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$transaction_details item=detail}
                                        <tr>
                                            <td>
                                                <a href="{$link->getAdminLink('AdminOrders')}&id_order={$detail.id_order}&vieworder"
                                                    target="_blank">
                                                    {$detail.id_order|default:'N/A'}#{$detail.id_order_detail|default:'N/A'}
                                                </a>
                                            </td>
                                            <td>
                                                {$detail.product_name|default:'N/A'}
                                                {if isset($detail.product_reference) && $detail.product_reference}
                                                    <br><small class="text-muted">Réf: {$detail.product_reference}</small>
                                                {/if}
                                                {if isset($detail.product_quantity) && $detail.product_quantity}
                                                    <br><small class="text-info">Qté: {$detail.product_quantity}</small>
                                                {/if}
                                            </td>
                                            <td class="text-info"><strong>{$detail.vendor_amount|number_format:3} TND</strong></td>
                                            <td>{$detail.transaction_type|default:'commission'|ucfirst}</td>
                                            <td>
                                                {assign var="manifestObj" value=TransactionHelper::getManifestReference($detail.id_order_detail ,$detail.transaction_type)}
                                                {assign var="manifestlink" value=Manifest::getAdminLink($manifestObj.id_manifest)}
                                                {assign var="manifeststatus" value=Manifest::getStatus($manifestObj.id_manifest)}
                                                <span>
                                                    <a href="{$manifestlink}" target="_blank">
                                                       {$manifestObj.reference}</a>{if $manifeststatus}-[{$manifeststatus}]{/if} 
                                                </span>
                                            </td>
                                            <td>{dateFormat date=$detail.order_date full=0}</td>
                                            <td>
                                                <span
                                                    class="badge badge-{if $detail.status == 'completed'}success{elseif $detail.status == 'pending'}warning{elseif $detail.status == 'cancelled'}danger{else}info{/if}">
                                                    {$detail.status|default:'pending'|ucfirst}
                                                </span>
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                                <tfoot>
                                    <tr class="info">
                                        <td colspan="3" class="text-right">
                                            <strong>{l s='Montant total du paiement:' mod='multivendor'}</strong>
                                        </td>
                                        <td><strong class="text-success">{$payment->amount|number_format:3} TND</strong></td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            {else}
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            {l s='Aucun détail de transaction trouvé pour ce paiement.' mod='multivendor'}
                        </div>
                    </div>
                </div>
            {/if}

            {* Actions rapides *}
            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">{l s='Actions rapides' mod='multivendor'}</h4>
                        </div>
                        <div class="panel-body">
                            <a href="{$print_url}" target="_blank" class="btn btn-default">
                                <i class="icon-print"></i> {l s='Imprimer le reçu de paiement' mod='multivendor'}
                            </a>
                            <a href="{$link->getAdminLink('AdminVendorPayments')}" class="btn btn-default">
                                <i class="icon-arrow-left"></i> {l s='Retour à la liste des paiements' mod='multivendor'}
                            </a>
                            {if $payment->status == 'pending'}
                                <a href="{$link->getAdminLink('AdminVendorPayments')}&id_vendor_payment={$payment->id}&updatemv_vendor_payment"
                                    class="btn btn-primary">
                                    <i class="icon-edit"></i> {l s='Modifier le paiement' mod='multivendor'}
                                </a>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        {else}
            <div class="alert alert-warning">
                {l s='Informations de paiement non trouvées.' mod='multivendor'}
            </div>
        {/if}
    </div>
</div>

<style>
    .panel-heading .pull-right {
        margin-top: -5px;
    }

    .table-responsive {
        margin-top: 15px;
    }

    .badge {
        font-size: 11px;
    }

    .panel-body h4 {
        color: #555;
        border-bottom: 1px solid #ddd;
        padding-bottom: 8px;
        margin-bottom: 15px;
    }
</style>