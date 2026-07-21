# Brief UI/UX Expert — Talashow (tala-show.com)
## À coller dans v0.app (Vercel) pour analyse & redesign premium stable

> **Usage v0 :** copier le bloc « PROMPT V0 » ci-dessous dans le chat v0, puis joindre 4–8 screenshots (home, browse, page série, mobile, hover cards).  
> **Objectif :** stabiliser UI/UX à **~98 %** (PC, Mac, Android, iPhone) sans casser le design ni les fonctionnalités actuelles.

---

## PROMPT V0 (copier-coller)

```
Tu es un Lead Product Designer / UI-UX spécialisé plateformes SVOD (Netflix, Amazon Prime Video, DramaBox).

Analyse le site https://tala-show.com/ et les screenshots joints.

Mission :
1) Noter le design actuel /10 (UI, UX, motion, mobile, accessibilité, performance perçue).
2) Diagnostiquer précisément le bug « tremblement / shake au hover » sur les cartes et rangées.
3) Proposer un plan de stabilisation premium vers 98 % UI/UX, SANS casser :
   - identité visuelle Talashow (dark premium, accent rouge)
   - structure catalogue (hero + rangées horizontales type DramaBox/Netflix)
   - fonctionnalités (lecture, pièces, abonnement, i18n FR/EN, admin)
4) Livrer des specs concrètes : tokens, spacing, typography, hover rules, breakpoints, composants.
5) Prioriser PC / Mac / Android / iPhone (touch vs pointeur fin).

Contraintes strictes :
- Ne pas inventer une nouvelle marque ni un thème purple/cream générique.
- Pas de redesign total « from scratch » : évolution contrôlée.
- Motion : presence & hiérarchie, jamais de jitter / layout shift.
- Hover desktop uniquement (@media hover:hover and pointer:fine) ; mobile = press states.
- Une seule transform scale par carte (pas scale parent + scale image).
- Pas de z-index agressif au hover qui reflow toute la rangée.
- Garder fond dark premium anthracite (pas flat noir mort, pas glow excessif).

Références qualité :
- Netflix : rangées stables, hover scale contrôlé (~1.04–1.06), focus clavier clair
- Prime Video : typo lisible, densité confortable, CTAs clairs
- DramaBox : badges HOT/NEW, rangées horizontales, épisodes très scannables

Format de réponse attendu :
A. Score actuel /10 + justification
B. Top 10 bugs UI/UX (sévérité P0/P1/P2)
C. Spec hover « zéro tremblement »
D. Design system tokens (couleurs, type, space, radius, elevation)
E. Wire/specs par écran : Home, Browse, Series, Episode player, Mobile bottom nav
F. Checklist QA multi-device pour 98 %
G. Maquettes / composants React+Tailwind prêts à implémenter (si tu génères du code)

Site : https://tala-show.com/
Stack actuelle : Laravel Blade + Tailwind/Vite, CSS custom (.ts-poster-card, .ts-catalog-row, hero-carousel 3D).
```

---

## 1. Contexte produit

| Élément | Détail |
|--------|--------|
| Produit | **Talashow** — plateforme de streaming (séries / épisodes) |
| URL | https://tala-show.com/ |
| Stack front | Laravel Blade, Vite, Tailwind, CSS custom (`app.css`, `navigation-stable.css`) |
| Langues | FR / EN |
| Cibles | PC Windows, Mac, Android, iPhone |
| Ton visuel actuel | Dark premium anthracite, accent rouge (#dc2626 / #e50914), rangées type DramaBox |
| Objectif | Premium, convivial, **stable à 98 %** (pas de redesign destructeur) |

### Ce qui doit rester intact
- Navigation (home, genres, recherche, abonnement, pièces, donation)
- Carrousel hero + rangées horizontales catalogue
- Page série (épisodes, play, partage)
- Lecteur vidéo / déblocage pièces
- Bottom nav mobile
- i18n FR/EN
- Identité rouge + dark (pas de thème générique AI)

---

## 2. Score expert actuel (baseline Cursor)

| Critère | Note /10 | Commentaire |
|---------|----------|-------------|
| Identité / branding | 7.0 | Dark + rouge OK ; manque encore une signature typographique plus « cinema » |
| Hiérarchie visuelle | 6.5 | Hero fort ; sections catalogue un peu denses / répétitives |
| Pattern Netflix/Prime/DramaBox | 7.5 | Rangées + badges présents ; hover moins maîtrisé que Netflix |
| Stabilité motion | **4.5** | **Tremblement hover** (cause principale du ressenti « pas premium ») |
| Mobile (iOS/Android) | 7.0 | Bottom nav OK ; safe-areas / densités à peaufiner |
| Desktop (PC/Mac) | 6.0 | Zones cliquables corrigées ; hover scale trop agressif |
| Lisibilité | 7.5 | Tokens texte améliorés ; contrastes secondaires à vérifier |
| Accessibilité (focus, touch) | 6.0 | Focus visible présent ; hit targets & motion-reduce incomplets |
| Performance perçue | 7.0 | Fond CSS léger ; images lazy ; SW à surveiller |
| **Score global UI/UX** | **~6.5 / 10** | Potentiel premium réel si motion + densité stabilisés |

**Cible : 9.8 / 10 (98 %)** = Netflix-level stability + DramaBox clarity + Prime readability.

---

## 3. Diagnostic « tremblement au hover » (P0)

### Cause technique probable (code actuel)
Sur `.ts-poster-card` :
1. **Double scale imbriqué** au hover :
   - `.ts-poster-card__media` → `scale(1.08)`
   - `.ts-poster-card__img` → `scale(1.1)`  
   → le navigateur recalcule 2 transforms + overflow + z-index → **jitter / tremblement**.
2. **`will-change: transform` permanent** sur les cartes → surcouche GPU inutile, micro-stutters.
3. **Saut de `z-index`** au hover (`12` / `15`) dans une rangée scrollable → réempilement / clipping / « jump ».
4. Transitions longues (`0.4s` / `0.5s`) + easing agressif sur beaucoup d’éléments en même temps.
5. Hero 3D (`rotateY` + `blur` + `filter`) peut amplifier le ressenti d’instabilité près du catalogue.

### Spec « zéro tremblement » (à imposer à v0 / à l’implémentation)

```css
/* Règle d’or Netflix-like */
@media (hover: hover) and (pointer: fine) {
  .ts-poster-card:hover .ts-poster-card__media {
    transform: scale(1.05); /* UNE seule scale, max 1.06 */
  }
  .ts-poster-card:hover .ts-poster-card__img {
    transform: none; /* JAMAIS de 2e scale */
  }
}

.ts-poster-card__media {
  overflow: hidden;
  transform: translateZ(0);
  transition: transform 180ms ease-out;
  will-change: auto; /* pas permanent */
}

/* Pas de z-index jump qui reflow la rangée */
.ts-poster-card:hover {
  z-index: 2; /* relatif au track seulement, pas 12/15 */
}

@media (prefers-reduced-motion: reduce) {
  .ts-poster-card,
  .ts-poster-card * {
    transition: none !important;
    transform: none !important;
  }
}

@media (hover: none) {
  /* Mobile : aucun hover scale ; active = opacity/brightness */
  .ts-poster-card:active .ts-poster-card__media {
    filter: brightness(0.92);
    transform: none;
  }
}
```

---

## 4. Benchmark Netflix / Prime / DramaBox → Talashow

| Pattern | Netflix | Prime | DramaBox | Talashow aujourd’hui | Action |
|---------|---------|-------|----------|----------------------|--------|
| Rangées horizontales | Oui, snap stable | Oui | Oui, dense | Oui | Garder ; peaufiner snap & gap |
| Hover poster | Scale ~1.05, preview optionnel | Léger | Scale + meta | Scale 1.08+1.10 | **Réduire & simplifier** |
| Hero | Plein écran, CTA fort | Fort | Moins cinéma | Hero 3D + rail | Simplifier 3D (moins blur) |
| Badges HOT/NEW | Limité | Limité | Fort | Présent | Garder, taille lisible |
| Liste épisodes | Classique | Classique | Pills + cards | DramaBox-like | Garder, densifier mobile |
| Bottom nav mobile | Non (app) | Non | Oui | Oui | Garder ; safe-area iPhone |
| Typo | Propre, hiérarchie claire | Très lisible | Compacte | Inter / system | Typo display + body plus nette |
| Dark UI | Noir profond | Anthracite | Sombre | Anthracite premium | Garder direction |

---

## 5. Design system cible (tokens premium)

### Couleurs
```
--ts-bg-deep: #060608
--ts-bg-base: #0b0b0e
--ts-bg-surface: #121216
--ts-bg-elevated: #1a1a20
--ts-accent: #E50914
--ts-accent-hover: #F40612
--ts-text-primary: #FAFAFA
--ts-text-secondary: #D4D4D8
--ts-text-muted: #A1A1AA
--ts-border: rgba(255,255,255,0.08)
```

### Typographie
- Display / titres sections : 1.25–1.5rem, weight 800, tracking -0.02em
- Titre carte : 0.95–1rem, weight 700, 2 lignes max
- Meta (épisodes) : 0.8125–0.875rem, muted
- Line-height lecture : 1.5–1.6

### Spacing & cartes
- Gap rangée mobile : 12px ; desktop : 14–16px
- Largeur poster mobile : ~11.5–12.5rem ; desktop : 13–15rem
- Radius poster : 8–10px (pas pill, pas cards ombre lourdes)
- Section gap vertical : 28–40px (respiration, pas 4px)

### Motion
- Durée hover : **150–200ms**
- Scale max : **1.05**
- Pas de bounce / spring agressif
- `prefers-reduced-motion` respecté partout

---

## 6. Écrans — brief d’évolution (sans casser)

### A. Home
**Garder :** hero + tabs genres + rangées Must-sees / Trending / New / genres.  
**Améliorer :**
- Hero : overlay lisible, CTA unique dominant, rail thumbs moins « tremblant »
- 1 composition claire above-the-fold (marque + 1 titre + 1 CTA + visuel)
- Moins de compétition visuelle (pas de chips/stats en trop dans le hero)
- Hover posters stabilisé

### B. Browse / Genres
**Garder :** tabs catégories + rangées ou grille.  
**Améliorer :** titre + intro lisibles ; grille cohérente ; empty states premium.

### C. Page série
**Garder :** hero compact + pills épisodes + liste cards.  
**Améliorer :** hit targets ≥ 44px iOS ; état « en cours / vu » très clair ; CTA Lecture sticky optionnel mobile.

### D. Player épisode
**Garder :** Video.js / Bunny.  
**Améliorer :** contrôles accessibles ; pas de overflow horizontal ; langue vidéo claire.

### E. Mobile shell
**Garder :** header minimal + bottom nav.  
**Améliorer :** `env(safe-area-inset-*)` ; Tawk au-dessus du bottom nav ; pas de double scroll.

---

## 7. Bugs / dettes UI à traiter (priorités)

### P0 — Bloque le ressenti premium
1. Tremblement hover cartes (double scale + z-index)
2. Layout shift / overlap rangées au hover
3. Zones mortes / pointer-events (déjà partiellement corrigé — à revalider)
4. Hover fantôme sur tactile (scale au touch)

### P1 — Qualité Netflix/Prime
5. Hero 3D trop lourd (blur/filter) → version allégée
6. Densité typo titres trop petite sur certains breakpoints
7. Séparation visuelle faible entre rangées
8. Focus clavier parfois masqué par overlays

### P2 — Polish 98 %
9. Micro-interactions CTA (press, loading)
10. Empty / error / offline states alignés brand
11. Skeleton images plus discrets
12. Harmoniser badges HOT/NEW/VIP (taille, contraste)

---

## 8. Checklist QA multi-device (porte vers 98 %)

### Desktop PC / Mac
- [ ] Hover carte : scale fluide, **aucun tremblement**, voisinage stable
- [ ] Clic poster / « Voir plus » / nav hero : 100 % cliquable
- [ ] Scroll horizontal rangée + flèches : sans jank
- [ ] FR ↔ EN : labels UI changent
- [ ] Zoom 100 % / 125 % / 150 % : pas de débordement

### iPhone (Safari)
- [ ] Bottom nav + safe area
- [ ] Pas de hover sticky après tap
- [ ] Hero hauteur confortable (pas 90vh écrasant)
- [ ] Liste épisodes scrollable, CTA Lecture visible
- [ ] Tawk ne masque pas la nav

### Android (Chrome)
- [ ] Idem iPhone
- [ ] Back gesture / bouton retour app OK
- [ ] Images lazy sans flash layout

### Accessibilité
- [ ] `prefers-reduced-motion`
- [ ] Contraste texte secondaire ≥ AA
- [ ] Focus visible rouge/clair
- [ ] Targets ≥ 44×44 px

---

## 9. Livrables attendus de v0.app

1. **Audit noté** /10 avec top issues  
2. **Spec CSS hover anti-shake** (prête à coller)  
3. **Design tokens** (JSON ou CSS variables)  
4. **Composants** (si code) :
   - `PosterCard` stable
   - `CatalogRow` (track + nav)
   - `HeroBanner` allégé
   - `EpisodeList` mobile/desktop
   - `MobileBottomNav`
5. **Before/After** motion rules  
6. **Plan d’implémentation** en 3 sprints (P0 → P1 → P2) sans casser Laravel Blade

---

## 10. Notes d’implémentation (pour l’équipe Talashow)

Fichiers clés actuels :
- `resources/css/app.css` — posters, rows, hero, episodes
- `resources/css/navigation-stable.css` — pointer-events / clics
- `resources/views/components/catalog/*`
- `resources/views/frontend/home.blade.php`, `series/show.blade.php`
- `resources/views/layouts/app.blade.php`

Après retour v0 : intégrer uniquement les règles qui **réduisent le motion** et **clarifient la hiérarchie**, sans changer les routes ni le backend.

---

## 11. Message court pour coller avec les screenshots

> Voici Talashow (https://tala-show.com/), plateforme streaming dark premium.  
> Besoin d’un audit UI/UX niveau Netflix / Prime / DramaBox.  
> Bug critique : l’UI tremble parfois au survol des cartes.  
> Objectif : stabiliser à 98 % sur PC, Mac, Android, iPhone **sans casser** le design ni les fonctions.  
> Utilise le brief complet ci-joint (score, causes shake, tokens, checklist).  
> Propose specs + composants prêts à implémenter.

---

*Document généré pour partage v0.app — Talashow UI/UX Stabilization Brief — 2026*
