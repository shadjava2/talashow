# Démarrer Laravel + tunnel Cloudflare (domaine temporaire trycloudflare.com)
# Si le tunnel se coupe (erreur DNS "region1.v2.argotunnel.com"), il redémarre automatiquement.
# Pour limiter les coupures : configurer le DNS du PC en 1.1.1.1 (Cloudflare) ou 8.8.8.8 (Google).
$ProjectRoot = $PSScriptRoot

Write-Host "Demarrage du serveur Laravel..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$ProjectRoot'; php artisan serve"

Start-Sleep -Seconds 3

Write-Host "Demarrage du tunnel Cloudflare (domaine temporaire)..." -ForegroundColor Green
Write-Host "Partagez l'URL affichee ci-dessous avec votre client.`n" -ForegroundColor Yellow
do {
    & cloudflared tunnel --url http://127.0.0.1:8000
    $exit = $LASTEXITCODE
    if ($exit -ne 0) {
        Write-Host "`nTunnel arrete (code $exit). Redemarrage dans 5 s..." -ForegroundColor Yellow
        Start-Sleep -Seconds 5
    }
} while ($true)
