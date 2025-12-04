{*
* Vendor Commissions Template
*}

{extends file='page.tpl'}

{block name='page_title'}
    {l s='Mes Commissions' mod='multivendor'}
{/block}

{block name='page_content'}
    <div class="mv-dashboard">
        <div class="mv-container">
            {include file="module:multivendor/views/templates/front/_partials/sidebar.tpl" active_page="commissions"}

            <main class="mv-main-content">
                {* Commission Summary Cards *}
                <div class="mv-commission-summary">
                    <div class="mv-summary-card mv-summary-card-add mv-clickable"
                        onclick="window.location.href='{$link->getModuleLink('multivendor', 'commissions')|escape:'quotes':'UTF-8'}'">
                        <h6 class="mv-stat-label">{l s='Chiffre d\'affaires total' mod='multivendor'}</h6>
                        <h3 class="mv-stat-value">{$commission_summary.total_commission_added.total|number_format:3} TND
                        </h3>
                    </div>

                    <div class="mv-summary-card mv-summary-card-paid mv-clickable"
                        onclick="window.location.href='{$link->getModuleLink('multivendor', 'commissions', ['transaction_status' => 'paid', 'commission_action' => 'commission', 'transaction_type' => ''])|escape:'quotes':'UTF-8'}'">
                        <h6 class="mv-stat-label">{l s='Total payé' mod='multivendor'}</h6>
                        <h3 class="mv-stat-value">{$commission_summary.paid_commission.total|number_format:3} TND</h3>
                    </div>

                    <div class="mv-summary-card mv-summary-card-add mv-clickable"
                        onclick="window.location.href='{$link->getModuleLink('multivendor', 'commissions', ['transaction_status' => 'pending', 'commission_action' => 'add', 'transaction_type' => 'commission', 'line_status' => '15'])|escape:'quotes':'UTF-8'}'">
                        <h6 class="mv-stat-label">{l s='Livré non payé' mod='multivendor'}</h6>
                        <h3 class="mv-stat-value">{$commission_summary.total_commission_pending.total|number_format:3} TND
                        </h3>
                        <p class="mv-stat-description">{$commission_summary.total_commission_pending.count_details}
                            commande(s)</p>
                    </div>

                    <div class="mv-summary-card mv-summary-card-pending mv-clickable"
                        onclick="window.location.href='{$link->getModuleLink('multivendor', 'commissions', ['transaction_status' => 'pending', 'transaction_type' => 'commission', 'commission_action' => 'add', 'line_status_not' => '15'])|escape:'quotes':'UTF-8'}'">
                        <h6 class="mv-stat-label">{l s='Commandes en cours' mod='multivendor'}</h6>
                        <h3 class="mv-stat-value">{$commission_summary.pending_amount.total|number_format:3} TND</h3>
                        <p class="mv-stat-description">{$commission_summary.pending_amount.count_details} commande(s)</p>
                    </div>

                    <div class="mv-summary-card mv-summary-card-refund mv-clickable"
                        onclick="window.location.href='{$link->getModuleLink('multivendor', 'commissions', ['commission_action' => 'refund', 'transaction_status' => '', 'transaction_type' => ''])|escape:'quotes':'UTF-8'}'">
                        <h6 class="mv-stat-label">{l s='Retours en cours' mod='multivendor'}</h6>
                        <h3 class="mv-stat-value">{$commission_summary.total_commission_refunded.total|number_format:3} TND
                        </h3>
                        <p class="mv-stat-description">{$commission_summary.total_commission_refunded.count_details}
                            commande(s)</p>
                    </div>
                </div>

                {* Commission Rates Card *}
                <div class="mv-card">
                    <div class="mv-card-header">
                        <h3 class="mv-card-title">{l s='Taux de commission' mod='multivendor'}</h3>
                    </div>
                    <div class="mv-card-body">
                        <div class="mv-commission-detail">
                            <span
                                class="mv-commission-detail-label">{l s='Votre taux de commission standard :' mod='multivendor'}</span>
                            <span class="mv-commission-detail-value">{$vendor_commission_rate|number_format:2}%</span>
                        </div>
                    </div>
                </div>

                {* Mobile/Desktop Detection for Content *}
                {if Context::getContext()->isMobile() == 1}
                    {* Load Mobile Commissions Template *}
                    {include file="module:multivendor/views/templates/front/commissions/mobile.tpl"}
                {else}
                    {* Load Desktop Commissions Template *}
                    {include file="module:multivendor/views/templates/front/commissions/desktop.tpl"}
                {/if}
            </main>
        </div>
    </div>

    <script>
        function togglePaymentDetails(paymentId) {
            const detailsDiv = document.getElementById(paymentId);
            const toggleBtn = detailsDiv.parentElement.querySelector('.mv-btn-toggle');

            if (detailsDiv.style.display === 'none') {
                detailsDiv.style.display = 'block';
                toggleBtn.classList.add('expanded');
            } else {
                detailsDiv.style.display = 'none';
                toggleBtn.classList.remove('expanded');
            }
        }
    </script>
{/block}