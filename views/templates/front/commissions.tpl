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
            <aside class="mv-sidebar">
                <div class="mv-card">
                    <div class="mv-card-body">
                        <nav class="mv-nav">
                        <a class="mv-nav-link" href="{$vendor_dashboard_url}">
                            <i class="mv-icon">📊</i>
                            <span>{l s='Tableau de bord' mod='multivendor'}</span>
                        </a>
                        <a class="mv-nav-link" href="{$vendor_orders_url}">
                            <i class="mv-icon">🛒</i>
                            <span>{l s='Commandes' mod='multivendor'}</span>
                        </a>
                        <a class="mv-nav-link mv-nav-link-active"  href="{$vendor_commissions_url}">
                            <i class="mv-icon">💰</i>
                            <span>{l s='Commissions' mod='multivendor'}</span>
                        </a>
                    </nav>
                    </div>
                </div>
            </aside>
            
            <main class="mv-main-content">
                {* Commission Summary Cards *}
                <div class="mv-commission-summary">
                  
                    <div class="mv-summary-card mv-summary-card-add">
                        <h6 class="mv-stat-label">{l s='Chiffre d\'affaires total' mod='multivendor'}</h6>
                        <h3 class="mv-stat-value">{Tools::displayPrice($commission_summary.total_commission_added)}</h3>
                        <p class="mv-stat-description">{l s='Total des commissions ajoutées' mod='multivendor'}</p>
                    </div>
                    <div class="mv-summary-card mv-summary-card-paid">
                        <h6 class="mv-stat-label">{l s='Montant payé' mod='multivendor'}</h6>
                        <h3 class="mv-stat-value">{Tools::displayPrice($commission_summary.paid_commission)}</h3>
                        <p class="mv-stat-description">{l s='Total payé à vous' mod='multivendor'}</p>
                    </div>
                    <div class="mv-summary-card mv-summary-card-pending">
                        <h6 class="mv-stat-label">{l s='Montant en attente' mod='multivendor'}</h6>
                        <h3 class="mv-stat-value">{Tools::displayPrice($commission_summary.pending_amount)}</h3>
                        <p class="mv-stat-description">{l s='Gagné - Payé' mod='multivendor'}</p>
                    </div>
                        <div class="mv-summary-card mv-summary-card-refund">
                        <h6 class="mv-stat-label">{l s='Montant de remboursées' mod='multivendor'}</h6>
                        <h3 class="mv-stat-value">-{Tools::displayPrice($commission_summary.total_commission_refunded)}</h3>
                        <p class="mv-stat-description">{l s='Total des remboursements' mod='multivendor'}</p>
                    </div>
                </div>
                
                {* Commission Rates Card *}
                <div class="mv-card">
                    <div class="mv-card-header">
                        <h3 class="mv-card-title">{l s='Taux de commission' mod='multivendor'}</h3>
                    </div>
                    <div class="mv-card-body">
                        <div class="mv-commission-detail">
                            <span class="mv-commission-detail-label">{l s='Votre taux de commission standard :' mod='multivendor'}</span>
                            <span class="mv-commission-detail-value">{$vendor_commission_rate}%</span>
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