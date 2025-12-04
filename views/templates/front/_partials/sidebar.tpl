{*
* Vendor Sidebar Navigation
* Usage: {include file="module:multivendor/views/templates/front/_partials/sidebar.tpl" active_page="dashboard"}
* active_page options: 'dashboard', 'orders', 'manifests', 'commissions', 'facturation'
*
* This template is completely self-contained and doesn't require any URL variables
* It generates all URLs dynamically using the Link object
*}

<aside class="mv-sidebar">
    <div class="mv-card">
        <div class="mv-card-body">
            <nav class="mv-nav">
                <a class="mv-nav-link {if $active_page == 'dashboard'}mv-nav-link-active{/if}" href="{$link->getModuleLink('multivendor', 'dashboard')}">
                    <i class="mv-icon">📊</i>
                    <span>{l s='Tableau de bord' mod='multivendor'}</span>
                </a>
                <a class="mv-nav-link {if $active_page == 'orders'}mv-nav-link-active{/if}" href="{$link->getModuleLink('multivendor', 'orders')}">
                    <i class="mv-icon">🛒</i>
                    <span>{l s='Commandes' mod='multivendor'}</span>
                </a>
                <a class="mv-nav-link {if $active_page == 'manifests'}mv-nav-link-active{/if}" href="{$link->getModuleLink('multivendor', 'manifestmanager')}">
                    <i class="mv-icon">📋</i>
                    <span>{l s='Manifestes' mod='multivendor'}</span>
                </a>
                <a class="mv-nav-link {if $active_page == 'commissions'}mv-nav-link-active{/if}" href="{$link->getModuleLink('multivendor', 'commissions')}">
                    <i class="mv-icon">💰</i>
                    <span>{l s='Commissions' mod='multivendor'}</span>
                </a>
                <a class="mv-nav-link {if $active_page == 'facturation'}mv-nav-link-active{/if}" href="{$link->getModuleLink('multivendor', 'facturation')}">
                    <i class="mv-icon">💳</i>
                    <span>{l s='Facturation' mod='multivendor'}</span>
                </a>
            </nav>
        </div>
    </div>
</aside>
