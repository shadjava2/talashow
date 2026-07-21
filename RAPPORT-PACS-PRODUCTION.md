# Rapport technique — Stack PACS Docker : performance, stabilité et risques

**Date :** 16 février 2025  
**Version :** 1.0  
**Classification :** Interne — contexte production clinique

---

## Executive summary

Cloudflare Tunnel et Caddy peuvent provoquer des lenteurs, timeouts et freezes sur DICOMweb (WADO-RS, gros volumes) et OHIF via buffering, timeouts par défaut courts et chemins d'accès multiples (Tailscale vs Cloudflare). La recommandation principale : **retirer Cloudflare Tunnel pour l'accès clinique PACS** et limiter l'accès au LAN + Tailscale avec HTTPS local (Caddy). Actions P0 : activer l'authentification Orthanc, durcir timeouts Caddy, auditer les chemins d'accès (HttpPublicRoot vs URLs réelles).

---

## 1. Cloudflare Tunnel : impact sur DICOMweb et OHIF

### 1.1 Mécanismes de risque

| Risque | Mécanisme | Impact |
|--------|-----------|--------|
| Buffering HTTP | cloudflared bufferise les réponses avant envoi | Lenteur sur streams WADO-RS (multipart), latence perçue |
| Timeouts | Cloudflare : 100s (plans payants), jusqu’à 30s sur plans gratuits | Timeouts sur études lourdes, échecs WADO-RS |
| Compression | Compression automatique côté Cloudflare | Risque de double-compression ou corruption sur binaires DICOM |
| Headers | Modification/ajout de headers (CF-*, X-Forwarded-*) | Incompatibilités CORS, mauvaise détection de schéma (http vs https) |
| DPI / inspection | Trafic transite par les serveurs Cloudflare | Risque RGPD/HIPAA si PHI non chiffrés bout en bout (TLS tunnel OK, mais tiers de confiance) |

### 1.2 Conclusion Cloudflare Tunnel

**Oui, Cloudflare Tunnel peut provoquer lenteurs et freezes UI** — surtout sur :
- WADO-RS (streams multipart, gros volumes)
- QIDO-RS avec nombreuses études
- OHIF (chargement massif de frames)

**Recommandation : supprimer Cloudflare Tunnel** pour un usage PACS clinique. Le tunnel ajoute un saut réseau, des contraintes de buffering/timeout et un tiers de confiance pour des PHI.

---

## 2. Caddy : impact sur DICOMweb et OHIF

### 2.1 Mécanismes de risque

| Risque | Mécanisme | Impact |
|--------|-----------|--------|
| Timeouts | `read_timeout`, `write_timeout` trop courts par défaut | Timeouts sur gros transfers WADO-RS |
| Gzip | `encode gzip` sur réponses binaires (DICOM) | Corruption possible, headers incorrects |
| HTTP/2 | Multiplexing + flow control | Rarement la cause directe, mais peut augmenter la latence perçue |
| Headers CORS | Mauvaise config ou headers manquants | Erreurs CORS côté OHIF, chargement bloqué |
| Reverse proxy buffering | Caddy bufferise par défaut | Latence sur streams, freezes si buffer plein |
| `HttpPublicRoot` vs URLs réelles | Orthanc génère des URLs avec `https://pacs.mondomaine.fr/` | Si accès via Tailscale (IP ou host différent), URLs cassées → échecs OHIF |

### 2.2 Conclusion Caddy

**Oui, Caddy peut influencer les freezes** via :
1. Timeouts insuffisants pour WADO-RS
2. Compression sur binaires DICOM
3. Chemins d’accès incohérents (HttpPublicRoot ≠ URL réelle)

---

## 3. Interactions non déterministes : chemins d’accès multiples

### 3.1 Chemins actuels

| Chemin | URL type | HttpPublicRoot | Conséquence |
|--------|----------|----------------|--------------|
| LAN direct | `http://192.168.100.x:8042` | `https://pacs.mondomaine.fr/` | URLs Orthanc pointent vers pacs.mondomaine.fr → CORS / fetch vers domaine différent |
| Tailscale direct | `http://100.105.173.3:8042` | idem | Même incohérence |
| Via Caddy (LAN) | `https://pacs.mondomaine.fr` | cohérent | OK si résolution DNS correcte |
| Via Cloudflare | `https://pacs.mondomaine.fr` (via tunnel) | cohérent | OK en URL mais latence + buffering |

### 3.2 Comportements non déterministes

- **HttpPublicRoot fixe** : Orthanc génère des URLs absolues. Si l’utilisateur accède via IP (Tailscale ou LAN), les requêtes partent vers `pacs.mondomaine.fr` → CORS, DNS, ou échec selon config.
- **Mélange Tailscale / Cloudflare** : latence et stabilité très variables selon le chemin utilisé.
- **Redirects** : si Caddy redirige HTTP→HTTPS ou ajoute des redirects, OHIF peut suivre des URLs incorrectes.

---

## 4. Root cause analysis — hypothèses par probabilité

| Probabilité | Hypothèse | Preuve attendue | Test |
|-------------|-----------|-----------------|------|
| **Élevée** | Timeouts Caddy/Cloudflare sur gros WADO-RS | Logs 502/504, timeouts dans navigateur | `curl -w "%{time_total}"` sur `/dicom-web/.../instances/.../frames/1` |
| **Élevée** | HttpPublicRoot incohérent selon chemin d’accès | Erreurs CORS, 404 sur ressources | Accès OHIF via IP vs via pacs.mondomaine.fr |
| **Moyenne** | Buffering cloudflared sur streams | Latence élevée uniquement via Cloudflare | Comparer RTT via Tailscale vs Cloudflare |
| **Moyenne** | Compression gzip sur DICOM | Réponses corrompues ou headers erronés | Vérifier `Content-Encoding` dans Caddy |
| **Moyenne** | PostgreSQL IndexConnectionsCount / contention | Lenteurs QIDO-RS sous charge | `SELECT * FROM pg_stat_activity` pendant tests |
| **Faible** | Ressources Orthanc (threads, mémoire) | CPU/mémoire saturés | `docker stats`, logs Orthanc |

---

## 5. Architecture recommandée “commercial grade”

### 5.1 Cible : accès privé uniquement

```
[Clinique LAN] ──► Caddy (HTTPS) ──► Orthanc + OHIF + pacs-cmk
[Tailscale]   ──► Caddy (HTTPS) ──► idem
```

- **Pas de Cloudflare Tunnel** pour le trafic clinique.
- Caddy : TLS local (certificat auto-signé ou Tailscale HTTPS) pour LAN + Tailscale.
- UFW : ports 80/443 ouverts uniquement sur tailscale0 et interfaces LAN.

### 5.2 Si accès public requis

- Réseau segmenté : PACS jamais exposé directement.
- Auth forte (OAuth2/OIDC, mFA).
- Audit complet (qui accède, quand, quoi).
- Contrat Cloudflare BAA si utilisé (US) ; pour l’UE, analyse RGPD et sous-traitants.

### 5.3 Recommandation Cloudflare Tunnel

| Usage | Recommandation | Justification |
|-------|----------------|---------------|
| PACS clinique, PHI | **Supprimer** | Risque buffering/timeouts, tiers pour PHI, complexité inutile |
| Télétravail | Préférer **Tailscale** | Réseau privé, pas de tiers, latence moindre |
| Démo / externe | Possible avec **auth + audit + BAA** | Uniquement si contraintes métier fortes |

---

## 6. Checklist de tests reproductibles

### 6.1 Depuis Ubuntu (serveur)

```bash
# Connexions
ss -tlnp | grep -E '8042|4242|80|443'

# Orthanc /system
curl -s http://127.0.0.1:8042/system | jq .

# DICOMweb QIDO-RS (remplacer STUDY_UID)
curl -s "http://127.0.0.1:8042/dicom-web/studies?PatientID=*" -H "Accept: application/dicom+json"

# DICOMweb WADO-RS (timing)
curl -w "\nTime: %{time_total}s\n" -o /dev/null -s "http://127.0.0.1:8042/dicom-web/studies/STUDY_UID/series/SERIES_UID/instances/INST_UID/frames/1"

# Logs Orthanc
docker logs orthanc --tail 100

# Logs Caddy
docker logs ohif-proxy --tail 100

# PostgreSQL
docker exec -it postgres psql -U orthanc -d orthanc -c "SELECT count(*) FROM pg_stat_activity;"
docker exec -it postgres psql -U orthanc -d orthanc -c "SELECT * FROM pg_stat_activity WHERE state != 'idle';"
```

### 6.2 Depuis Windows (client)

```powershell
# Test connectivité
Test-NetConnection -ComputerName 100.105.173.3 -Port 443
Test-NetConnection -ComputerName 192.168.100.x -Port 443

# curl (si disponible)
curl -w "Time: %{time_total}s`n" -o NUL -s "https://pacs.mondomaine.fr/dicom-web/studies"
```

### 6.3 Tests de charge légers

```bash
# Concurrence WADO-RS (5 requêtes parallèles)
for i in {1..5}; do
  curl -w "Req $i: %{time_total}s\n" -o /tmp/frame_$i.dcm -s "http://127.0.0.1:8042/dicom-web/studies/STUDY/series/SERIES/instances/INST/frames/1" &
done
wait
```

---

## 7. Réglages concrets (snippets)

### 7.1 orthanc.json (extrait)

```json
{
  "AuthenticationEnabled": true,
  "RegisteredUsers": {
    "user": "password_hash_bcrypt"
  },
  "RemoteAccessAllowed": true,
  "HttpsCACertificate": "/path/to/ca.pem",
  "HttpRequestTimeout": 300,
  "HttpCompressionEnabled": false,
  "HttpPublicRoot": "https://pacs.mondomaine.fr/",
  "DicomWeb": {
    "Enable": true,
    "Root": "/dicom-web/",
    "PublicRoot": "https://pacs.mondomaine.fr/dicom-web/"
  },
  "ConcurrentJobs": 4,
  "JobsEngineThreadsCount": 6,
  "ZipLoaderThreadsCount": 4,
  "PostgreSQL": {
    "IndexConnectionsCount": 20
  }
}
```

- **AuthenticationEnabled : true** (P0)
- **HttpRequestTimeout : 300** (au lieu de défaut ~30)
- **HttpCompressionEnabled : false** (éviter double-compression avec Caddy)
- **IndexConnectionsCount : 20** (30 peut être excessif)

### 7.2 Caddyfile (ohif-proxy)

```caddyfile
{
    admin off
}

pacs.mondomaine.fr {
    reverse_proxy orthanc:8042 {
        transport http {
            read_timeout 300s
            write_timeout 300s
            dial_timeout 10s
        }
        flush_interval -1
    }
    encode zstd gzip
    @dicom path /dicom-web/* /system*
    handle @dicom {
        encode off
        reverse_proxy orthanc:8042 {
            transport http {
                read_timeout 300s
                write_timeout 300s
            }
            flush_interval -1
        }
    }
    log {
        output stdout
        format console
        level INFO
    }
}
```

- Timeouts 300s pour DICOMweb
- Pas de compression sur `/dicom-web/*` et `/system*`
- `flush_interval -1` pour limiter le buffering sur streams

### 7.3 UFW — règles minimales

```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow from 192.168.0.0/16 to any port 80,443 proto tcp
ufw allow from 192.168.100.0/24 to any port 80,443 proto tcp
ufw allow from 192.168.200.0/24 to any port 80,443 proto tcp
ufw allow in on tailscale0 to any port 80,443 proto tcp
ufw allow 22/tcp
ufw enable
ufw status verbose
```

---

## 8. Risques légaux / sécurité (PHI)

### 8.1 Points sensibles

- Données de santé (PHI) : réglementations RGPD, loi santé (France), éventuellement HIPAA si US.
- Cloudflare : sous-traitant, flux transitant par leurs serveurs — nécessité d’analyse et de garanties contractuelles (BAA, DPA).
- Pas d’authentification : accès non contrôlé aux études DICOM.

### 8.2 Plan de durcissement

| Priorité | Action | Détail |
|----------|--------|--------|
| P0 | Activer l’authentification Orthanc | `RegisteredUsers` + HTTPS |
| P0 | TLS partout | Caddy avec certificat valide (Let’s Encrypt local ou mDNS) |
| P0 | Supprimer Cloudflare Tunnel | Pour trafic clinique |
| P1 | Segmentation réseau | PACS sur VLAN dédié, pas d’accès direct depuis Internet |
| P1 | Logs et audit | Logs Caddy + Orthanc, rotation, rétention 1 an minimum |
| P1 | Sauvegardes | PostgreSQL + stockage Orthanc, plan de reprise |
| P2 | MFA | Si exposition externe obligatoire |
| P2 | Chiffrement au repos | PostgreSQL + disque |

---

## 9. Tableau symptôme → cause → preuve → test

| Symptôme | Cause probable | Preuve attendue | Test |
|----------|----------------|-----------------|------|
| OHIF freeze au chargement | Timeout WADO-RS | 504 Caddy/cloudflared, erreur fetch | `curl -w "%{time_total}"` sur frame |
| Erreurs CORS | HttpPublicRoot / origine | CORS error dans console navigateur | Accès via IP vs hostname |
| Lenteur via Cloudflare | Buffering cloudflared | RTT élevé uniquement via tunnel | Comparer Tailscale vs tunnel |
| Images corrompues | Gzip sur binaires | `Content-Encoding: gzip` sur WADO-RS | Inspecter headers réponse |
| QIDO lent | Surcharge PostgreSQL | `pg_stat_activity` chargé | Requête pendant usage OHIF |
| Redirect loop | Caddy HTTP→HTTPS + Orthanc | 301/302 en chaîne | `curl -v` sur racine |

---

## 10. Recommandations priorisées

### P0 (immédiat)

1. Activer `AuthenticationEnabled` et `RegisteredUsers` dans orthanc.json
2. Augmenter les timeouts Caddy (300s) et désactiver la compression sur DICOMweb
3. Supprimer Cloudflare Tunnel pour le trafic clinique
4. Vérifier que HttpPublicRoot correspond au chemin d’accès réel (Tailscale ou LAN)

### P1 (courant)

1. Mettre à jour orthanc.json (HttpRequestTimeout, HttpCompressionEnabled, IndexConnectionsCount)
2. Durcir UFW et ne garder que les règles nécessaires
3. Mettre en place logs structurés et rotation
4. Plan de sauvegarde PostgreSQL + Orthanc

### P2 (amélioration)

1. TLS avec certificat fiable (ACME local ou Tailscale)
2. Audit d’accès (qui, quand, quoi)
3. Tests de charge automatisés

### Quick wins

- Timeouts Caddy 300s
- `encode off` sur /dicom-web/*
- `flush_interval -1` sur reverse_proxy
- Désactiver Cloudflare Tunnel

### Refactor

- Architecture “accès privé uniquement” (Tailscale + Caddy)
- Authentification centralisée (OAuth2/OIDC)
- Monitoring (Prometheus + Grafana)

---

## Annexe A — Commandes de diagnostic (copier-coller)

```bash
# Serveur Ubuntu
ss -tlnp | grep -E '80|443|8042'
curl -s http://127.0.0.1:8042/system | jq .IsHttpServerSecure
docker stats --no-stream
docker logs orthanc --tail 50 2>&1 | grep -i error
docker logs ohif-proxy --tail 50 2>&1
```

---

## Annexe B — Références

- Orthanc Book : https://book.orthanc-server.com/
- DICOMweb : WADO-RS, QIDO-RS (IHE RAD)
- Caddy reverse_proxy : https://caddyserver.com/docs/caddyfile/directives/reverse_proxy
- Cloudflare Tunnel : https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/

---

*Rapport généré le 16 février 2025. Relecture technique recommandée avant déploiement en production.*
