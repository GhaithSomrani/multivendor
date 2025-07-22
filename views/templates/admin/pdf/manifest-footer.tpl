{*
* Manifest PDF Footer Template
* File: views/templates/admin/pdf/manifest-footer.tpl
*}

<div style="width: 100%; text-align: center; font-size: 8px; color: #666; padding-top: 10px; border-top: 1px solid #ddd;">
    <p style="margin: 5px 0;">
        {$footer_text} - {l s='Generated on' mod='multivendor'} {$generation_date|date_format:"%d/%m/%Y at %H:%M"}
    </p>
    <p style="margin: 0;">
        {l s='Page' mod='multivendor'} {$smarty.foreach.page.iteration} - {l s='Manifest Reference:' mod='multivendor'} #{$manifest->reference}
    </p>
</div>