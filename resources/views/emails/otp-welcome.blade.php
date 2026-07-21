@php
    $settings = app(\App\Services\SettingsService::class);
    $logo = $settings->get('site_logo_url') ?: asset('logo.svg');
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification Talashow</title>
</head>
<body style="margin:0;padding:0;background:#0b1220;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0b1220;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:92%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.10);border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 24px 0 24px;text-align:center;">
                            <img src="{{ $logo }}" alt="Talashow" style="height:44px;width:auto;border-radius:10px;display:inline-block;">
                            <h1 style="margin:16px 0 0 0;font-size:22px;line-height:1.3;">Bienvenue sur Talashow</h1>
                            <p style="margin:10px 0 0 0;color:rgba(255,255,255,0.75);font-size:14px;line-height:1.6;">
                                Bonjour {{ $name }}, voici votre code de vérification pour finaliser la création de votre compte.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 24px;">
                            <div style="background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.12);border-radius:14px;padding:18px;text-align:center;">
                                <div style="color:rgba(255,255,255,0.70);font-size:12px;letter-spacing:0.08em;text-transform:uppercase;">
                                    Code OTP
                                </div>
                                <div style="margin-top:10px;font-size:34px;font-weight:800;letter-spacing:0.18em;color:#ffffff;">
                                    {{ $otp }}
                                </div>
                                <p style="margin:14px 0 0 0;color:rgba(255,255,255,0.70);font-size:12px;line-height:1.6;">
                                    Ce code expire dans 10 minutes.
                                </p>
                            </div>
                            <p style="margin:14px 0 0 0;color:rgba(255,255,255,0.55);font-size:12px;line-height:1.6;text-align:center;">
                                Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 24px 22px 24px;text-align:center;color:rgba(255,255,255,0.45);font-size:12px;">
                            © Talashow — Tous droits réservés
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

