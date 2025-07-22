{*
* Manifest PDF Header Template
* File: views/templates/admin/pdf/manifest-header.tpl
*}

<table style="width: 100%; margin-bottom: 20px;">
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <h2 style="color: #333; margin: 0; font-size: 24px;">
                {l s='MANIFEST DOCUMENT' mod='multivendor'}
            </h2>
        </td>
        <td style="width: 50%; text-align: right; vertical-align: top;">
            <div style="font-size: 12px; color: #666;">
                {l s='Generated on:' mod='multivendor'} {$current_date|date_format:"%d/%m/%Y %H:%M"}
            </div>
        </td>
    </tr>
</table>

<hr style="border: none; border-top: 2px solid #007bff; margin: 10px 0;" />