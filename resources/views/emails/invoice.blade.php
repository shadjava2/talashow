<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Facture {{ $invoiceNumber }}</title>
</head>
<body style="margin:0;padding:0;background:#0b1220;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0b1220;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:92%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.10);border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="padding:22px 24px 0 24px;">
                        <div style="font-size:12px;color:rgba(255,255,255,0.65);letter-spacing:.08em;text-transform:uppercase;">Facture</div>
                        <div style="margin-top:6px;font-size:22px;font-weight:800;">{{ $invoiceNumber }}</div>
                        <div style="margin-top:6px;color:rgba(255,255,255,0.80);font-size:14px;">{{ $invoiceTitle }}</div>
                        <div style="margin-top:6px;color:rgba(255,255,255,0.60);font-size:12px;">
                            Date : {{ $transaction->created_at->format('d/m/Y H:i') }}
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 24px 0 24px;">
                        <div style="background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.12);border-radius:14px;padding:16px;">
                            <div style="font-weight:700;margin-bottom:8px;">Client</div>
                            <div style="color:rgba(255,255,255,0.88);font-size:14px;">{{ $transaction->user->name }}</div>
                            <div style="color:rgba(255,255,255,0.60);font-size:12px;">{{ $transaction->user->email }}</div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 24px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            <thead>
                            <tr>
                                <th align="left" style="padding:10px 8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10);font-size:12px;">Description</th>
                                <th align="right" style="padding:10px 8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10);font-size:12px;">Qté</th>
                                <th align="right" style="padding:10px 8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10);font-size:12px;">Prix</th>
                                <th align="right" style="padding:10px 8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10);font-size:12px;">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($invoiceLines as $line)
                                @php
                                    $qty = (int) ($line['qty'] ?? 1);
                                    $unit = (float) ($line['unit'] ?? 0);
                                    $total = $qty * $unit;
                                @endphp
                                <tr>
                                    <td style="padding:10px 8px;border:1px solid rgba(255,255,255,0.10);color:rgba(255,255,255,0.88);font-size:13px;">
                                        {{ $line['label'] ?? '' }}
                                    </td>
                                    <td align="right" style="padding:10px 8px;border:1px solid rgba(255,255,255,0.10);color:rgba(255,255,255,0.75);font-size:13px;">
                                        {{ $qty }}
                                    </td>
                                    <td align="right" style="padding:10px 8px;border:1px solid rgba(255,255,255,0.10);color:rgba(255,255,255,0.75);font-size:13px;">
                                        {{ strtoupper($transaction->currency) }} {{ number_format($unit, 2, '.', ' ') }}
                                    </td>
                                    <td align="right" style="padding:10px 8px;border:1px solid rgba(255,255,255,0.10);font-weight:700;font-size:13px;">
                                        {{ strtoupper($transaction->currency) }} {{ number_format($total, 2, '.', ' ') }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                            <div style="color:rgba(255,255,255,0.60);font-size:12px;">
                                Statut : <strong style="color:#ffffff;">{{ $transaction->status }}</strong><br>
                                Référence : <strong style="color:#ffffff;">{{ $transaction->payment_id ?: ('TX-' . $transaction->id) }}</strong>
                            </div>
                            <div style="text-align:right;">
                                <div style="color:rgba(255,255,255,0.60);font-size:12px;">Total</div>
                                <div style="font-size:20px;font-weight:900;">
                                    {{ strtoupper($transaction->currency) }} {{ number_format((float) $transaction->amount, 2, '.', ' ') }}
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:16px;text-align:center;">
                            <a href="{{ $invoiceUrl }}"
                               style="display:inline-block;background:#dc2626;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;font-size:14px;">
                                Voir / imprimer la facture
                            </a>
                            <div style="margin-top:10px;color:rgba(255,255,255,0.55);font-size:12px;line-height:1.5;">
                                Si le bouton ne fonctionne pas, copiez ce lien :<br>
                                <span style="word-break:break-all;">{{ $invoiceUrl }}</span>
                            </div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 24px 22px 24px;text-align:center;color:rgba(255,255,255,0.45);font-size:12px;">
                        © {{ config('app.name', 'Talashow') }} — {{ now()->year }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

