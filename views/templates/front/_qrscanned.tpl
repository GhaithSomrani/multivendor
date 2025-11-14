{extends file='page.tpl'}

{block name='page_content'}
    <style>
        .qr-result {
            max-width: 400px;
            margin: 50px auto;
            padding: 40px 20px;
            text-align: center;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .qr-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: #fff;
        }

        .qr-icon.error {
            background: #ef4444;
        }

        .qr-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #1f2937;
        }

        .qr-message {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .qr-button {
            display: inline-block;
            color: #0079FF;
            text-decoration: underline;
            margin: 5px;
        }

        .qr-button:hover {
            background: #db2777;
            color: #fff;
        }

        .qr-button.success {
            background: #10b981;
        }

        .qr-button.success:hover {
            background: #059669;
        }
    </style>

    <div class="qr-result">
        {if $success}
            <div class="qr-icon">✓</div>
            <div class="qr-title">Félicitations! 💪</div>
            {if $name}
                <h1>{$name}</h1>
            {/if}
            {foreach $success as $msg}
                <div class="qr-message">{$msg}</div>
            {/foreach}
        {else}
            <div class="qr-icon error">✕</div>
            <div class="qr-title">Erreur</div>
            {foreach $errors as $error}
                <div class="qr-message">{$error}</div>
            {/foreach}
        {/if}

        {if $manifest_status == $status_collected && $can_receive && !$isvendor}
            <a href="{$link->getModuleLink('multivendor', 'manifest', ['id' => $manifest_id])}" class="qr-button success">
                Valider la Réception </a>
        {/if}

        <a href="{$link->getPageLink('my-account')}" class="qr-button">
            <small> Retour à mon compte</small>
        </a>
    </div>
{/block}