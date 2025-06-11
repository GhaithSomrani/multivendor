{extends file='page.tpl'}

{block name='page_title'}
    {$status_title}
{/block}

{block name='page_content'}
    <div class="vendor-verification-page">
        <div class="verification-card">
            <div class="verification-icon">
                {if $is_pending}
                    <i class="material-icons" style="font-size: 64px; color: #f59e0b;">hourglass_empty</i>
                {elseif $is_rejected}
                    <i class="material-icons" style="font-size: 64px; color: #ef4444;">error</i>
                {/if}
            </div>

            <h2>{$status_title}</h2>
            <p class="status-message">{$status_message}</p>

            {if $is_pending}
                <div class="info-box">
                    <h3>{l s='Que se passe-t-il ensuite ?' mod='multivendor'}</h3>
                    <ul>
                        <li>{l s='Notre équipe examinera votre demande de vendeur' mod='multivendor'}</li>
                        <li>{l s='Vous recevrez une notification par e-mail une fois approuvé' mod='multivendor'}</li>
                        <li>{l s='Le processus d\'examen prend généralement 1 à 3 jours ouvrables' mod='multivendor'}</li>
                    </ul>
                </div>
            {/if}

            <div class="action-buttons">
                <button onclick="location.reload();" class="btn btn-primary">
                    {l s='Actualiser le statut' mod='multivendor'}
                </button>
                <a href="{$link->getPageLink('my-account')}" class="btn btn-secondary">
                    {l s='Retour à Mon Compte' mod='multivendor'}
                </a>
            </div>
        </div>
    </div>

    <style>
        .vendor-verification-page {
            padding: 40px 20px;
            text-align: center;
            min-height: 400px;
        }

        .verification-card {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .verification-icon {
            margin-bottom: 24px;
        }

        .status-message {
            font-size: 18px;
            color: #666;
            margin: 24px 0;
        }

        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 24px 0;
            text-align: left;
        }

        .action-buttons {
            margin-top: 32px;
        }

        .action-buttons .btn {
            margin: 0 8px;
        }
    </style>
{/block}