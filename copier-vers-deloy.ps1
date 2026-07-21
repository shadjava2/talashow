# Copie tous les fichiers et dossiers a deployer vers le dossier DELOY
$racine = $PSScriptRoot
$dest   = Join-Path $racine "DELOY"

$dossiers = @("app", "bootstrap", "config", "database", "public", "resources", "routes", "storage", "vendor", "tools", "tests")
$fichiers = @("artisan", "composer.json", "composer.lock", "package.json", "vite.config.js", "postcss.config.js", "tailwind.config.js", ".env.example", ".htaccess", "index.php")

Write-Host "Nettoyage de DELOY (sauf DEPLOIEMENT.txt)..." -ForegroundColor Cyan
Get-ChildItem -Path $dest -Exclude "DEPLOIEMENT.txt" -Force | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue

foreach ($d in $dossiers) {
    $srcPath = Join-Path $racine $d
    if (Test-Path $srcPath) {
        Write-Host "  Copie $d (exclusion: node_modules, .git)..." -ForegroundColor Gray
        if ($d -eq "storage") {
            robocopy $srcPath (Join-Path $dest $d) /E /XD ".git" /NFL /NDL /NJH /NJS /NC /NS /NP 2>$null
        } elseif ($d -eq "vendor") {
            robocopy $srcPath (Join-Path $dest $d) /E /NFL /NDL /NJH /NJS /NC /NS /NP 2>$null
        } else {
            robocopy $srcPath (Join-Path $dest $d) /E /XD "node_modules" ".git" /NFL /NDL /NJH /NJS /NC /NS /NP 2>$null
        }
        if ($LASTEXITCODE -ge 8) { Write-Host "  Erreur robocopy pour $d" -ForegroundColor Red }
    }
}

foreach ($f in $fichiers) {
    $srcPath = Join-Path $racine $f
    if (Test-Path $srcPath) {
        Copy-Item -Path $srcPath -Destination $dest -Force
        Write-Host "  Copie $f" -ForegroundColor Gray
    }
}

Write-Host "`nTermine. Contenu a envoyer dans: tala-show.com/talashow" -ForegroundColor Green
Write-Host "Chemin serveur: /home/mpakadev/tala-show.com/talashow" -ForegroundColor Green
