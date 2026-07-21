# Déploiement Git → cPanel (Talashow)

Automatise : `git push` (PC) puis un script dans le **Terminal cPanel**.

## Architecture

| Chemin serveur | Rôle |
|----------------|------|
| `/home/talashow/talashow/` | Code Laravel (dépôt Git) |
| `/home/talashow/public_html/` | DocumentRoot `tala-show.com` |
| GitHub | `https://github.com/shadjava2/talashow.git` |

```
PC: commit + push
        ↓
GitHub (main)
        ↓
cPanel Terminal: bash scripts/deploy-cpanel.sh
        ↓
git pull → composer → npm build → sync public_html → artisan cache
```

## Première installation (une seule fois)

Dans **cPanel → Terminal** :

```bash
cd ~
# Si ~/talashow existe déjà SANS git (upload zip) :
#   mv talashow talashow-backup-$(date +%Y%m%d)
#   puis clone :

git clone https://github.com/shadjava2/talashow.git talashow
cd ~/talashow

# Garder / recréer le .env production (NE PAS prendre celui du PC)
# Si backup : cp ~/talashow-backup-*/.env ~/talashow/.env

chmod +x scripts/deploy-cpanel.sh
bash scripts/deploy-cpanel.sh
```

Vérifie que `~/public_html/index.php` pointe vers `../talashow` (le script le copie depuis `scripts/cpanel/index.php`).

### Auth GitHub (si le dépôt est privé)

```bash
# Option simple : Personal Access Token (HTTPS)
git remote set-url origin https://<TOKEN>@github.com/shadjava2/talashow.git
```

Ou clé SSH + remote `git@github.com:shadjava2/talashow.git`.

### Node.js (pour `npm run build`)

cPanel → **Setup Node.js App** (ou Softaculous) : active Node 18+, puis :

```bash
export PATH=~/nodevenv/.../bin:$PATH   # selon l’UI cPanel
which npm
```

Si **pas de Node** : sur le PC, retire `public/build` du `.gitignore`, commit le build, push — le script utilisera ce dossier.

## Usage quotidien (après chaque feature)

### Sur ton PC (Cursor / PowerShell)

```powershell
cd c:\Users\HP\Documents\VSDEV\talashow
npm run build
git add -A
git commit -m "message clair"
git push origin main
```

### Sur cPanel Terminal

```bash
cd ~/talashow
bash scripts/deploy-cpanel.sh
```

C’est tout.

## Ce que le script ne fait JAMAIS

- Écraser `.env`
- Lancer `db:seed`
- Toucher `zandapp.net` / autres domaines

## Dépannage

| Problème | Action |
|----------|--------|
| 500 après deploy | `cd ~/talashow && php artisan optimize:clear` + logs `storage/logs/laravel.log` |
| Chemins Windows | `rm -f bootstrap/cache/config.php` |
| CSS/JS ancien | vérifier `public_html/build/` + hard refresh |
| `npm: command not found` | activer Node cPanel ou committer `public/build` |
| Pull refusé | `git status` — le script fait `reset --hard` (modifs serveur perdues) |

## Option plus tard : auto-deploy (webhook)

Quand tu voudras zéro Terminal : GitHub Action ou webhook → SSH qui lance `deploy-cpanel.sh`. Pour l’instant le flux **push + 1 commande** est le plus fiable sur cPanel mutualisé.
