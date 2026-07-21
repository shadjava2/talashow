# Rapport contexte — Refonte design premium Talashow
## Pour Vercel v0.app / agent de redesign

> **Date :** 21 juillet 2026  
> **Site prod :** https://tala-show.com/  
> **Repo :** https://github.com/shadjava2/talashow.git  
> **Commit de référence :** `c2877c4` (hero cinématique + thème dark/light)  
> **Usage :** coller le bloc **PROMPT VERCEL / V0** dans v0, joindre 6–10 screenshots (home, browse, série, épisode, profil, mobile), puis utiliser le reste du document comme source de vérité.

---

## PROMPT VERCEL / V0 (copier-coller)

```
Tu es Lead Product Designer + Design System Architect pour une plateforme SVOD africaine premium.

Produit : Talashow (https://tala-show.com/)
Stack réelle à respecter côté intégration : Laravel 10 + Blade + Vite + Tailwind 3 + JS vanilla (PAS Next.js en prod aujourd’hui). Tu peux proposer des maquettes React/Tailwind pour v0, mais chaque composant doit être mappable vers Blade + CSS tokens.

MISSION : refactoriser TOTALlement le design de manière PREMIUM, cohérente, robuste multi-device (PC, Mac, Android, iPhone), sans casser le métier.

OBJECTIF VISUEL
- Niveau Netflix / Prime Video / NetShort sur la sensation premium
- Identité Talashow : dark cinéma + accent rouge (#E50914), jamais purple/cream générique AI
- Typographie expressive (display + body), pas Inter/Roboto/Arial par défaut
- Atmosphère : fond non plat (gradients, grain léger, lumière), hero full-bleed
- Motion : 2–3 animations intentionnelles par écran, fluides, zéro jitter / layout shift
- Light theme aussi soigné que le dark (toggle déjà existant)

ÉCRANS À REFAIRE (priorité)
1. Home (hero + catalogue)
2. Browse / Genre
3. Page série
4. Page épisode / player
5. Header + bottom nav mobile
6. Profil + recharge/donation (harmoniser, moins “formulaire gris”)

CONTRAINTES MÉTIER (NE PAS CASSER)
- Auth, abonnement, pièces, donation, i18n FR/EN
- Hero featured (séries is_featured, max 6)
- Rangées : Tendances (is_trending only) → Nouveautés (< 3 mois) → Genres → À ne pas manquer
- Anti-doublons entre rangées
- Badges HOT / NEW / épisodes
- Lecteur Bunny / Video.js
- PWA / service worker existants
- Déploiement cPanel : assets buildés versionnés (public/build), pas de npm sur serveur

ARCHITECTURE UI ACTUELLE À CONNAÎTRE
- Tokens CSS : --ts-bg-*, --ts-text-*, --ts-accent, data-theme=dark|light
- Hero : .ts-coverflow = fond plein écran + copy gauche + stage posters 3D droite
- Cartes : .ts-poster-card dans .ts-dramabox-row / series-row
- Chrome : .ts-chrome-nav, .ts-header-btn, theme-switcher, lang-switcher
- Fichiers clés listés dans le rapport joint

LIVRABLES ATTENDUS
A. Direction artistique (1 page) : mood, références, ce qu’on garde / on jette
B. Design system premium : couleurs, typo, spacing, radius, elevation, motion tokens
C. Specs par écran (wire + états hover/focus/loading/empty)
D. Spec hero “cinéma” : fond + poster avant + texte lisible + transitions fluides
E. Spec cartes catalogue “zéro tremblement” (une seule scale ≤ 1.05, hover desktop only)
F. Composants React+Tailwind prêts à traduire en Blade
G. Checklist QA multi-device + accessibilité
H. Plan d’implémentation en 3 sprints mappable sur Laravel Blade

INTERDITS
- Inventer une autre marque
- Thème purple / cream terracotta / broadsheet newspaper
- Hero en petite carte isolée sur fond noir mort
- Cards partout (surtout dans le hero)
- Clutter : pills excessives, stats strips, badges flottants sur le hero
- Casser les routes / paiements / admin
```

---

## 1. Contexte produit

| Élément | Détail |
|--------|--------|
| Nom | **Talashow** |
| URL | https://tala-show.com/ |
| Type | Streaming SVOD (séries / épisodes, pièces, abonnement, donation) |
| Public | Francophone / anglophone, mobile-first + desktop |
| Positionnement | Premium, cinématographique, contenu africain / animation 3D (ex. *La Faim*) |
| Langues | FR / EN (session + cookie `locale`) |
| Admin | Back-office Laravel séparé (`resources/views/admin`) |

### Ce qui est sacré (ne pas supprimer)
- Identité **dark + rouge Talashow**
- Navigation : Accueil, Genre, Application, recherche, Connexion, S’abonner, Achat, Donation, FR/EN, thème
- Parcours lecture / déblocage / paiement
- Structure catalogue (hero + rangées)
- Bottom nav mobile
- Accessibilité minimale (focus visible, hits ~44px, `prefers-reduced-motion`)

---

## 2. Stack technique (source de vérité)

| Couche | Techno | Fichiers |
|--------|--------|----------|
| Backend | Laravel 10, PHP 8.1+ | `composer.json`, `app/` |
| Templates | Blade | `resources/views/` |
| CSS | Tailwind 3.4 + CSS custom | `resources/css/app.css`, `navigation-stable.css` |
| JS | Vanilla (Vite) | `resources/js/app.js` |
| Build | Vite 5 | `vite.config.js` → `public/build/` |
| Vidéo | Bunny Stream + Video.js | `components/video/bunny-player.blade.php` |
| PWA | `public/sw.js`, `manifest.json` | — |

**Important pour Vercel/v0 :** les maquettes React sont un **intermédiaire de design**. L’implémentation prod reste **Blade + CSS tokens + app.js**. Chaque composant v0 doit avoir un équivalent Blade clair.

---

## 3. État design actuel (juillet 2026)

### 3.1 Identité & tokens
Définis dans `resources/css/app.css` :

**Dark (défaut)**
- `--ts-bg-deep: #060608`
- `--ts-bg-base: #0b0b0e`
- `--ts-bg-surface: #121216`
- `--ts-accent: #e50914`
- Texte : `#fafafa` / `#d4d4d8` / `#a1a1aa`

**Light** (`html[data-theme="light"]`)
- Fonds clairs `#f7f8fb` / blanc
- Accent `#dc2626`
- Toggle : `components/theme-switcher.blade.php` + `localStorage['talashow-theme']` + script anti-FOUC dans `layouts/app.blade.php`

**Logo :** `public/logo.svg` (TALASHOW, gradient rose→rouge)

### 3.2 Hero home (critique)
Fichier : `resources/views/frontend/home.blade.php`  
CSS : `.ts-coverflow*` dans `app.css`  
JS : bloc `data-hero-carousel` dans `app.js`

Architecture actuelle :
1. **Fond plein écran** (cover de la slide active) + Ken Burns
2. **Scrim / glow / grain** pour lisibilité
3. **Copy gauche** : badge exclusif, méta, titre, description, CTAs, dots
4. **Stage 3D droite** : posters coverflow (`--tx --ry --sc --tz --op --z`)
5. **Progress bar** autoplay ~6.5s
6. Animations : stagger texte, float poster actif, shine, swipe mobile

**Intention à préserver / amplifier en refonte premium :**
- Fond + image en avant (pas un poster seul sur noir)
- Titre/description visibles immédiatement
- Transitions fluides, cinématographiques

### 3.3 Catalogue
Composants :
- `components/catalog/series-card.blade.php` → `.ts-poster-card`
- `components/catalog/series-row.blade.php` → rangées
- `components/catalog/category-tabs.blade.php` → filtres genre
- `components/catalog/section-header.blade.php`

Ordre home (`HomeController`) :
1. **Tendances** — `is_trending` uniquement
2. **Nouveautés** — publiées < 3 mois
3. **Rangées par genre** (max 4 genres, ≥ 4 séries)
4. **À ne pas manquer** — rating/vues, sans doublons des rangées au-dessus

Hero : `is_featured`, limite 6.

### 3.4 Chrome / navigation
- Header sticky : `.ts-chrome-nav` + état `is-scrolled`
- Boutons : `.ts-header-btn--primary|ghost|soft`
- Mobile header minimal + **bottom nav** 4 items
- Theme switcher + lang switcher

### 3.5 Pages secondaires (dette visuelle)
Moins tokenisées, beaucoup de `bg-gray-*` / `text-gray-*` hardcodés :
- `frontend/series/show.blade.php`
- `frontend/episode/show.blade.php`
- `frontend/profile.blade.php`
- `payment/recharge.blade.php`, `payment/donation.blade.php`
- Modales dans `layouts/app.blade.php`

→ La refonte premium doit **unifier** ces pages sur le même design system.

---

## 4. Diagnostic expert (baseline)

| Critère | Note /10 | Commentaire |
|---------|----------|-------------|
| Branding | 7.0 | Rouge + dark OK ; typo encore trop “Inter générique” |
| Hero | 7.5 | Direction bonne (fond + 3D) ; à peaufiner densité & mobile |
| Catalogue | 6.5 | Structure OK ; hover/motion encore perfectibles |
| Cohérence multi-pages | 5.0 | Home > reste du site |
| Motion | 6.5 | Hero animé ; cartes à stabiliser |
| Light theme | 6.0 | Fonctionnel ; pas encore “premium light” |
| Mobile | 7.0 | Bottom nav OK ; hero stacked à resculpter |
| A11y | 6.5 | Focus/hits présents ; à renforcer |
| **Global** | **~6.6 / 10** | Potentiel 9.5+ avec refonte systémique |

---

## 5. Direction de refonte premium (brief créatif)

### Garder
- Accent rouge Talashow
- Dark cinéma comme mode principal
- Hero immersif full-bleed
- Rangées horizontales type SVOD
- Badges HOT/NEW/épisode
- Toggle dark/light + i18n

### Élever
- **Typographie** : display cinéma + body lisible (éviter Inter/system)
- **Hero** : composition 1er viewport = marque + 1 titre + 1 phrase + CTAs + visuel dominant (pas de clutter)
- **Cartes** : une seule scale hover ≤ 1.05, desktop only ; mobile = brightness
- **Surfaces** : tokens partout (plus de gray Tailwind aléatoires)
- **Light** : surfaces papier premium, pas “blanc hôpital”
- **Motion** : presence (entrée sections, crossfade hero), jamais jitter

### Éviter (anti-patterns AI)
- Purple gradients / glow excessif
- Cream + serif terracotta
- Layout journal / hairlines denses
- Cards dans le hero
- Stats strips / pill clusters / stickers flottants sur le média hero

### Références
- **Netflix** : hero full-bleed, rangées stables, hover maîtrisé
- **Prime Video** : lisibilité, densité confortable
- **NetShort / DramaBox** : coverflow / posters avant, badges, mobile dense
- **Talashow** : contenu africain, énergie, rouge signature

---

## 6. Specs techniques pour l’implémentation post-v0

### 6.1 Fichiers à toucher (refonte totale)

```
resources/css/app.css
resources/css/navigation-stable.css
resources/js/app.js
tailwind.config.js
resources/views/layouts/app.blade.php
resources/views/frontend/home.blade.php
resources/views/frontend/browse.blade.php
resources/views/frontend/series/show.blade.php
resources/views/frontend/episode/show.blade.php
resources/views/frontend/profile.blade.php
resources/views/payment/*.blade.php
resources/views/components/catalog/*
resources/views/components/theme-switcher.blade.php
resources/views/components/lang-switcher.blade.php
resources/views/components/layout/ambient-bg.blade.php
resources/lang/fr/ui.php
resources/lang/en/ui.php
public/build/*          # après npm run build (obligatoire)
public/logo.svg         # si rebrand léger
```

### 6.2 Règles non négociables (prod)
1. **Ne pas toucher** logique paiement / routes métier hors UI
2. **Ne pas écraser** `.env` prod
3. Après design : `npm run build` puis commit de `public/build`
4. Déploiement cPanel :
```bash
cd ~/talashow && git fetch origin && git reset --hard origin/main
rsync -a --delete public/build/ ~/public_html/build/
php artisan view:clear && php artisan optimize:clear
```
5. DocumentRoot = `~/public_html` (code dans `~/talashow`) — ne pas inverser
6. Hover desktop uniquement : `@media (hover: hover) and (pointer: fine)`
7. Respecter `prefers-reduced-motion`

### 6.3 Mapping v0 → Blade (exemple)

| Composant v0 | Cible Blade / CSS |
|--------------|-------------------|
| `HeroCoverflow` | `home.blade.php` + `.ts-coverflow` |
| `PosterCard` | `series-card.blade.php` + `.ts-poster-card` |
| `CatalogRow` | `series-row.blade.php` |
| `AppHeader` | `layouts/app.blade.php` nav |
| `BottomNav` | bottom nav dans layout |
| `ThemeToggle` | `theme-switcher.blade.php` |
| Tokens | `:root` / `[data-theme]` dans `app.css` |

---

## 7. Plan de sprints suggéré (après maquettes v0)

### Sprint A — Foundation (P0)
- Tokens dark/light finaux + typographie
- Header / footer / bottom nav
- Poster card + row (zéro tremblement)
- Build + deploy

### Sprint B — Home & Browse (P0)
- Hero cinéma final (fond + poster + copy + motion)
- Rangées catalogue + empty/loading
- Category tabs
- Build + deploy

### Sprint C — Series / Episode / Account (P1)
- Page série + grille épisodes
- Player chrome
- Profil + recharge + donation (même language UI)
- A11y pass + QA multi-device
- Build + deploy

---

## 8. Checklist QA (cible 98 %)

- [ ] Home : fond hero visible + poster avant + texte lisible FR/EN
- [ ] Autoplay hero pause au hover ; swipe mobile OK
- [ ] Cartes : pas de tremblement desktop ; pas de scale au touch
- [ ] Light/Dark : contraste OK, pas de texte blanc sur blanc
- [ ] Browse : filtres + recherche + empty state
- [ ] Série / épisode : CTA play, pièces, partage
- [ ] iPhone safe-area + bottom nav
- [ ] Android chrome address bar (vh)
- [ ] Focus clavier visible
- [ ] `prefers-reduced-motion` désactive animations lourdes
- [ ] Hard refresh + SW : UI à jour après deploy
- [ ] Paiement / auth non régressés

---

## 9. Screenshots à joindre dans v0 (recommandé)

1. Home desktop — hero
2. Home desktop — rangées catalogue
3. Home mobile
4. Browse / genre
5. Page série
6. Page épisode / player
7. Header actions (connexion, thème, langue)
8. Light theme (si disponible)
9. Hover carte (si capture possible)
10. Profil ou recharge (état “non premium” actuel)

---

## 10. Résumé exécutif pour Vercel

Talashow est une **SVOD Laravel Blade** déjà en production sur **cPanel**, avec une base UI dark/rouge, un **hero fond+3D**, un **toggle thème**, et un catalogue structuré.  
La refonte premium doit être **totale sur le design system et les écrans**, mais **conservative sur le métier et le déploiement**.  

v0 doit livrer une **direction + design system + composants** prêts à être **traduits en Blade/CSS/JS**, pas une app Next.js de remplacement.

**Succès =** sensation Netflix-grade, identité Talashow intacte, zéro régression paiement/lecture, deploy via `public/build` versionné.
