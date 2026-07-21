import './bootstrap';
import '../css/app.css';
import '../css/navigation-stable.css';

// PWA Registration
if ('serviceWorker' in navigator) {
    const host = window.location.hostname;
    const devHosts = Array.isArray(window?.TALASHOW_PWA_DEV_HOSTS) ? window.TALASHOW_PWA_DEV_HOSTS : [];
    const isLocalhost = ['localhost', '127.0.0.1'].includes(host) || devHosts.includes(host);
    window.addEventListener('load', async () => {
        // IMPORTANT: en dev, on désactive le SW pour éviter les pages/erreurs “stales”
        // (ex: ancienne page Ignition mise en cache qui persiste après corrections).
        // Si tu veux forcer le SW en local: TALASHOW_ENABLE_SW_LOCAL=true dans .env (injecté côté front si besoin)
        const forceLocalSw = window?.TALASHOW_ENABLE_SW_LOCAL === true;
        if (isLocalhost && !forceLocalSw) {
            try {
                const regs = await navigator.serviceWorker.getRegistrations();
                await Promise.all(regs.map(r => r.unregister()));
                if (window.caches) {
                    const keys = await caches.keys();
                    await Promise.all(keys.map(k => caches.delete(k)));
                }
                console.log('SW disabled on localhost');
            } catch (e) {
                console.log('SW disable failed: ', e);
            }
            return;
        }

        // PWA install/SW nécessite un contexte sécurisé (HTTPS) sauf exception localhost.
        // Sur IP HTTP (ex: Tailscale), le navigateur bloque => on évite d’insister.
        if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(host)) {
            console.log('SW skipped: insecure context (needs HTTPS or localhost).');
            return;
        }

        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('SW registered: ', registration);
            })
            .catch((registrationError) => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

// Note: UI de bannière d'installation PWA supprimée (demande Talashow).

// Skeleton Loading
function showSkeleton(element) {
    element.classList.add('skeleton');
}

function hideSkeleton(element) {
    element.classList.remove('skeleton');
}

/**
 * Finalise l’affichage d’une image (skeleton + overlay + opacity).
 * Idempotent : safe d’appeler plusieurs fois.
 */
function finalizeImageDisplay(img) {
    if (!img || img.dataset.talashowImgFinalized === '1') return;
    img.dataset.talashowImgFinalized = '1';
    img.classList.remove('skeleton');
    img.classList.remove('opacity-0');
    const parent = img.parentElement;
    if (parent) {
        const overlay = parent.querySelector(':scope > .skeleton');
        overlay?.classList.add('hidden');
    }
}

function tryFinalizeIfDecoded(img) {
    if (img.complete && img.naturalWidth > 0) {
        finalizeImageDisplay(img);
        return true;
    }
    return false;
}

/**
 * Après changement dynamique de src (upload admin), ré-attache load + filets :
 * sans ça, l’écouteur « once » du premier chargement ne se déclenche plus.
 */
window.talashowRevealImageAfterSrcChange = function (img) {
    if (!img) return;
    delete img.dataset.talashowImgFinalized;
    img.addEventListener(
        'load',
        () => {
            if (typeof img.decode === 'function') {
                img.decode()
                    .then(() => finalizeImageDisplay(img))
                    .catch(() => finalizeImageDisplay(img));
            } else {
                finalizeImageDisplay(img);
            }
        },
        { once: true },
    );
    img.addEventListener('error', () => finalizeImageDisplay(img), { once: true });
    queueMicrotask(() => tryFinalizeIfDecoded(img));
    requestAnimationFrame(() => tryFinalizeIfDecoded(img));
};

/**
 * Attache load/error + filets (microtask / rAF) pour ne pas rater le cas
 * « déjà en cache / load avant l’écouteur » (connexion lente ou navigation rapide).
 */
function bindImageFinalize(img, { allowNetRetry = true } = {}) {
    if (!img || img.dataset.talashowImgBindInit === '1') return;
    img.dataset.talashowImgBindInit = '1';

    let settled = false;
    const done = () => {
        if (settled) return;
        settled = true;
        finalizeImageDisplay(img);
    };

    const onLoad = () => {
        if (typeof img.decode === 'function') {
            img.decode().then(done).catch(done);
        } else {
            done();
        }
    };

    if (tryFinalizeIfDecoded(img)) {
        return;
    }

    img.classList.add('skeleton');
    img.addEventListener('load', onLoad, { once: true });
    img.addEventListener(
        'error',
        () => {
            const fb = img.dataset?.fallback;
            if (fb && img.src !== fb && !img.dataset.talashowCfFbTried) {
                img.dataset.talashowCfFbTried = '1';
                settled = false;
                img.dataset.talashowImgFinalized = '';
                img.addEventListener('error', () => done(), { once: true });
                img.src = fb;
                queueMicrotask(() => tryFinalizeIfDecoded(img) && done());
                return;
            }
            const src = img.getAttribute('src') || '';
            const isRemote = /^https?:\/\//i.test(src);
            const isPlaceholder = /placeholder\.svg|\/images\/placeholders\//i.test(src);
            if (
                allowNetRetry &&
                isRemote &&
                !isPlaceholder &&
                !img.dataset.talashowNetRetry
            ) {
                img.dataset.talashowNetRetry = '1';
                settled = false;
                img.dataset.talashowImgFinalized = '';
                try {
                    const u = new URL(src, window.location.href);
                    u.searchParams.set('_ts', String(Date.now()));
                    img.addEventListener('error', () => done(), { once: true });
                    img.src = u.toString();
                    queueMicrotask(() => tryFinalizeIfDecoded(img) && done());
                } catch (_) {
                    done();
                }
                return;
            }
            done();
        },
        { once: true },
    );

    queueMicrotask(() => tryFinalizeIfDecoded(img) && done());
    requestAnimationFrame(() => tryFinalizeIfDecoded(img) && done());
    requestAnimationFrame(() => requestAnimationFrame(() => tryFinalizeIfDecoded(img) && done()));
}

// Progressive skeleton for images (streaming-friendly)
function setupSkeletonImages(root = document) {
    const imgs = root.querySelectorAll('img:not([data-no-skeleton])');
    imgs.forEach((img) => {
        try {
            if (!img.getAttribute('decoding')) img.decoding = 'async';
            if (!img.getAttribute('loading')) {
                const isHeader = !!img.closest('nav, header');
                img.loading = isHeader ? 'eager' : 'lazy';
            }
        } catch (_) {
            // ignore
        }

        const src = img.getAttribute('src') || '';
        if (!img.dataset.fallback && /^https?:\/\/imagedelivery\.net\//i.test(src)) {
            img.dataset.fallback = src.replace(/^(https?:\/\/imagedelivery\.net\/[^/]+\/[^/]+)(?:\/[^/]+)?\/?$/i, '$1/public');
        }

        bindImageFinalize(img, { allowNetRetry: true });
    });
}

// Toggle password visibility (eye button)
function setupPasswordToggles(root = document) {
    const toggles = root.querySelectorAll('[data-toggle-password]');
    toggles.forEach((btn) => {
        const inputId = btn.getAttribute('data-toggle-password');
        if (!inputId) return;
        const input = root.getElementById ? root.getElementById(inputId) : document.getElementById(inputId);
        if (!input) return;

        const iconShow = btn.querySelector('[data-icon="show"]');
        const iconHide = btn.querySelector('[data-icon="hide"]');

        const apply = () => {
            const isText = input.type === 'text';
            if (iconShow) iconShow.classList.toggle('hidden', isText);
            if (iconHide) iconHide.classList.toggle('hidden', !isText);
            btn.setAttribute('aria-pressed', isText ? 'true' : 'false');
        };

        apply();
        btn.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            apply();
            input.focus();
        });
    });
}

// Confirm modal (bootstrap-like, no dependency)
function talashowConfirm({ title = 'Confirmation', message = '', confirmText = 'Confirmer', cancelText = 'Annuler' } = {}) {
    return new Promise((resolve) => {
        const modal = document.getElementById('ts-modal');
        const elTitle = document.getElementById('ts-modal-title');
        const elMsg = document.getElementById('ts-modal-message');
        const btnClose = document.getElementById('ts-modal-close');
        const btnCancel = document.getElementById('ts-modal-cancel');
        const btnConfirm = document.getElementById('ts-modal-confirm');

        if (!modal || !btnCancel || !btnConfirm || !btnClose || !elTitle || !elMsg) {
            resolve(window.confirm(message || title));
            return;
        }

        elTitle.textContent = title;
        elMsg.textContent = message;
        btnConfirm.textContent = confirmText;
        btnCancel.textContent = cancelText;

        const cleanup = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.removeEventListener('keydown', onKeyDown);
            btnCancel.removeEventListener('click', onCancel);
            btnClose.removeEventListener('click', onCancel);
            btnConfirm.removeEventListener('click', onConfirm);
        };

        const onCancel = () => {
            cleanup();
            resolve(false);
        };
        const onConfirm = () => {
            cleanup();
            resolve(true);
        };
        const onKeyDown = (e) => {
            if (e.key === 'Escape') onCancel();
        };

        btnCancel.addEventListener('click', onCancel);
        btnClose.addEventListener('click', onCancel);
        btnConfirm.addEventListener('click', onConfirm);
        document.addEventListener('keydown', onKeyDown);

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        btnConfirm.focus();
    });
}

window.talashowConfirm = talashowConfirm;

// Toast helper (client-side)
function talashowToast({ type = 'error', title = 'Action impossible', messages = [] } = {}) {
    const root = document.getElementById('toast-root');
    if (!root) return;

    const color = type === 'success'
        ? 'bg-green-600/90 border-green-500/40'
        : 'bg-red-600/90 border-red-500/40';

    const toast = document.createElement('div');
    toast.className = `js-toast pointer-events-auto ${color} border text-white px-4 py-3 rounded-xl shadow-lg shadow-black/30 backdrop-blur`;

    const header = document.createElement('div');
    header.className = 'flex items-start justify-between gap-3';

    const elTitle = document.createElement('div');
    elTitle.className = 'text-sm font-semibold';
    elTitle.textContent = title;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'js-toast-close text-white/80 hover:text-white transition';
    btn.setAttribute('aria-label', 'Fermer');
    btn.textContent = '✕';

    header.appendChild(elTitle);
    header.appendChild(btn);

    const body = document.createElement('div');
    body.className = 'mt-1 text-sm text-white/95';
    const arr = Array.isArray(messages) ? messages : [String(messages || '')];
    arr.filter(Boolean).forEach((m) => {
        const line = document.createElement('div');
        line.textContent = String(m);
        body.appendChild(line);
    });

    toast.appendChild(header);
    toast.appendChild(body);

    // prepend
    root.insertBefore(toast, root.firstChild);

    // animate like existing toasts
    toast.style.transform = 'translateY(-6px)';
    toast.style.opacity = '0';
    toast.style.transition = 'transform 180ms ease, opacity 180ms ease';
    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    });

    const hide = () => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-6px)';
        setTimeout(() => toast.remove(), 220);
    };
    btn.addEventListener('click', hide);
    setTimeout(hide, 5500);
}

window.talashowToast = talashowToast;

function setupSeriesEngagement() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const likeBtn = document.getElementById('ts-series-like-btn');
    const favBtn = document.getElementById('ts-series-favorite-btn');

    async function post(url) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({}),
        });
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, status: res.status, data };
    }

    function toastLoginRequired() {
        window.talashowToast?.({
            type: 'error',
            title: 'Connexion requise',
            messages: ["Connectez-vous pour effectuer cette action."],
        });
    }

    if (likeBtn) {
        likeBtn.addEventListener('click', async () => {
            const url = likeBtn.getAttribute('data-url');
            if (!url) return;
            if (!csrf) {
                toastLoginRequired();
                return;
            }

            likeBtn.disabled = true;
            try {
                const { ok, status, data } = await post(url);
                if (!ok) {
                    if (status === 401) toastLoginRequired();
                    else {
                        window.talashowToast?.({ type: 'error', title: 'Action impossible', messages: ['Une erreur est survenue.'] });
                    }
                    return;
                }

                const liked = !!data?.liked;
                likeBtn.dataset.liked = liked ? '1' : '0';
                const icon = likeBtn.querySelector('svg');
                icon?.classList.toggle('text-red-400', liked);
                icon?.classList.toggle('text-white/80', !liked);

                const countEl = document.getElementById('ts-like-count');
                if (countEl && typeof data?.likes_count === 'number') {
                    countEl.textContent = String(data.likes_count);
                }
            } finally {
                likeBtn.disabled = false;
            }
        });
    }

    if (favBtn) {
        favBtn.addEventListener('click', async () => {
            const url = favBtn.getAttribute('data-url');
            if (!url) return;
            if (!csrf) {
                toastLoginRequired();
                return;
            }

            favBtn.disabled = true;
            try {
                const { ok, status, data } = await post(url);
                if (!ok) {
                    if (status === 401) toastLoginRequired();
                    else {
                        window.talashowToast?.({ type: 'error', title: 'Action impossible', messages: ['Une erreur est survenue.'] });
                    }
                    return;
                }

                const favorited = !!data?.favorited;
                favBtn.dataset.favorited = favorited ? '1' : '0';

                // Styles
                favBtn.classList.toggle('bg-red-600/20', favorited);
                favBtn.classList.toggle('border-red-500/30', favorited);
                favBtn.classList.toggle('bg-white/10', !favorited);
                favBtn.classList.toggle('border-white/10', !favorited);

                const icon = favBtn.querySelector('svg');
                icon?.classList.toggle('text-amber-300', favorited);
                icon?.classList.toggle('text-white/80', !favorited);

                const label = favBtn.querySelector('.ts-fav-label');
                if (label) {
                    label.textContent = favorited ? 'En favoris' : 'Ajouter en favoris';
                }
            } finally {
                favBtn.disabled = false;
            }
        });
    }
}

function setupEpisodeViewTracking() {
    const trackEl = document.querySelector('[data-ts-view-track]');
    const videoEl = document.getElementById('video-player');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!trackEl || !videoEl) return;

    const url = trackEl.getAttribute('data-view-url');
    const episodeId = trackEl.getAttribute('data-episode-id');
    if (!url || !episodeId) return;

    let sent = false;
    let inFlight = false;
    let playTimer = null;

    // Video.js: plus fiable que les events natifs selon les navigateurs / HLS.
    let vjsPlayer = null;
    try {
        if (typeof window.videojs === 'function' && videoEl.classList.contains('video-js')) {
            vjsPlayer = window.videojs('video-player');
        }
    } catch (_) {
        vjsPlayer = null;
    }

    const getCurrentTime = () => {
        try {
            return vjsPlayer ? Number(vjsPlayer.currentTime() || 0) : Number(videoEl.currentTime || 0);
        } catch (_) {
            return 0;
        }
    };
    const isPaused = () => {
        try {
            return vjsPlayer ? !!vjsPlayer.paused() : !!videoEl.paused;
        } catch (_) {
            return true;
        }
    };

    const on = (event, handler) => {
        if (vjsPlayer) vjsPlayer.on(event, handler);
        else videoEl.addEventListener(event, handler);
    };
    const off = (event, handler) => {
        if (vjsPlayer) vjsPlayer.off(event, handler);
        else videoEl.removeEventListener(event, handler);
    };

    const send = async () => {
        if (sent || inFlight) return false;
        inFlight = true;

        try {
            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrf || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({}),
            });

            // Si pas connecté => 401 => on ignore (exigence: vues par compte connecté)
            if (!res.ok) return false;
            const data = await res.json().catch(() => ({}));
            if (!data?.success) return false;

            const countEl = document.getElementById('ts-views-count');
            if (countEl && typeof data?.episode_views_count === 'number') {
                countEl.textContent = String(data.episode_views_count);
            }
            sent = true;
            return true;
        } catch (_) {
            // no-op
            return false;
        } finally {
            inFlight = false;
        }
    };

    function clearPlayTimer() {
        if (playTimer) {
            window.clearTimeout(playTimer);
            playTimer = null;
        }
    }

    // "YouTube-like": on compte la vue quand la lecture démarre réellement.
    // On valide après ~5s de lecture, même si l'utilisateur a "seek" directement à 1:22.
    const onPlaying = () => {
        // Si déjà au-delà de 5s => on peut compter immédiatement
        if (getCurrentTime() >= 5) {
            send().then((ok) => {
                if (ok) cleanup();
            });
            return;
        }
        clearPlayTimer();
        playTimer = window.setTimeout(() => {
            // Si toujours en lecture et on a avancé un minimum
            if (!isPaused() && getCurrentTime() >= 5) {
                send().then((ok) => {
                    if (ok) cleanup();
                });
            }
        }, 5000);
    };

    // Fallback si playing n'est pas émis (certains environnements)
    const onTimeUpdate = () => {
        if (getCurrentTime() >= 5) {
            send().then((ok) => {
                if (ok) cleanup();
            });
        }
    };

    const onPause = () => {
        clearPlayTimer();
    };
    const cleanup = () => {
        clearPlayTimer();
        off('playing', onPlaying);
        off('timeupdate', onTimeUpdate);
        off('pause', onPause);
    };

    on('playing', onPlaying);
    on('timeupdate', onTimeUpdate);
    on('pause', onPause);
}

// Confirmations "pro" sur formulaires (remplace confirm())
function setupConfirmForms() {
    const forms = Array.from(document.querySelectorAll('form[data-ts-confirm]'));
    if (!forms.length) return;

    forms.forEach((form) => {
        form.addEventListener('submit', async (e) => {
            // si le submit est déclenché après confirmation, on laisse passer
            if (form.dataset.tsConfirmed === '1') return;

            e.preventDefault();

            const title = form.getAttribute('data-ts-confirm-title') || 'Confirmation';
            const message = form.getAttribute('data-ts-confirm-message') || 'Confirmer cette action ?';
            const confirmText = form.getAttribute('data-ts-confirm-confirm') || 'Confirmer';
            const cancelText = form.getAttribute('data-ts-confirm-cancel') || 'Annuler';

            const ok = await (window.talashowConfirm
                ? window.talashowConfirm({ title, message, confirmText, cancelText })
                : Promise.resolve(window.confirm(message))
            );

            if (!ok) return;

            // Anti double submit
            try {
                const btn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (btn) btn.disabled = true;
            } catch (_) {}

            form.dataset.tsConfirmed = '1';
            form.submit();
        });
    });
}

function setupFormClientValidation() {
    const forms = Array.from(document.querySelectorAll('form[data-ts-validate]'));
    if (!forms.length) return;

    const focusField = (el) => {
        try {
            el?.focus?.();
            const rect = el?.getBoundingClientRect?.();
            if (rect && (rect.top < 0 || rect.bottom > window.innerHeight)) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (_) {
            // ignore
        }
    };

    const getLabel = (el) => {
        const custom = el?.getAttribute?.('data-ts-label');
        if (custom) return custom;
        const id = el?.getAttribute?.('id');
        if (id) {
            const lab = document.querySelector(`label[for="${CSS?.escape ? CSS.escape(id) : id}"]`);
            const t = lab?.textContent?.trim();
            if (t) return t;
        }
        const n = el?.getAttribute?.('name');
        return n ? n : 'ce champ';
    };

    const buildMessage = (el) => {
        const label = getLabel(el);
        const v = el?.validity;
        if (!v) return `Veuillez vérifier ${label}.`;

        if (v.valueMissing) return `Veuillez renseigner ${label}.`;
        if (v.typeMismatch) {
            if ((el.getAttribute('type') || '').toLowerCase() === 'email') return 'Veuillez saisir un email valide.';
            return `Format invalide pour ${label}.`;
        }
        if (v.tooShort) {
            const min = el.getAttribute('minlength') || '';
            return min ? `${label} doit contenir au moins ${min} caractères.` : `${label} est trop court.`;
        }
        if (v.patternMismatch) return `Format invalide pour ${label}.`;
        if (v.rangeUnderflow) return `Le montant est trop bas (minimum ${el.getAttribute('min')}).`;
        if (v.rangeOverflow) return `Le montant est trop élevé (maximum ${el.getAttribute('max')}).`;
        if (v.badInput) return `Valeur invalide pour ${label}.`;
        // fallback (souvent déjà en FR selon le navigateur)
        return el.validationMessage || `Veuillez vérifier ${label}.`;
    };

    forms.forEach((form) => {
        form.addEventListener('submit', (e) => {
            // 1) Match validation (data-ts-match)
            const matchEls = Array.from(form.querySelectorAll('[data-ts-match]'));
            matchEls.forEach((el) => el?.setCustomValidity?.(''));
            for (const el of matchEls) {
                const sel = el.getAttribute('data-ts-match') || '';
                if (!sel) continue;
                const other = document.querySelector(sel);
                if (other && String(el.value || '') !== String(other.value || '')) {
                    el.setCustomValidity('Ne correspond pas');
                }
            }

            // 2) HTML5 validity
            if (form.checkValidity()) return;

            e.preventDefault();
            const invalids = Array.from(form.querySelectorAll(':invalid'));
            const messages = invalids.slice(0, 4).map(buildMessage);
            talashowToast({ type: 'error', title: 'Action impossible', messages });
            focusField(invalids[0]);
        });
    });
}

function setupPasswordStrengthUi() {
    const pwd = document.getElementById('password');
    const pwd2 = document.getElementById('password_confirmation');
    const bar = document.getElementById('ts-password-strength-bar');
    const txt = document.getElementById('ts-password-strength-text');
    const matchTxt = document.getElementById('ts-password-match-text');

    // Only on pages that have these fields (register)
    if (!pwd || !bar || !txt) return;

    const scorePassword = (value) => {
        const v = String(value || '');
        let score = 0;
        if (v.length >= 8) score += 1;
        if (v.length >= 12) score += 1;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score += 1;
        if (/\d/.test(v)) score += 1;
        if (/[^A-Za-z0-9]/.test(v)) score += 1;
        // 0..5
        return Math.max(0, Math.min(5, score));
    };

    const renderStrength = () => {
        const v = pwd.value || '';
        const s = scorePassword(v);
        const pct = Math.round((s / 5) * 100);
        bar.style.width = `${pct}%`;

        let label = '—';
        let cls = 'bg-red-500';
        if (!v) {
            label = '—';
            cls = 'bg-red-500';
        } else if (s <= 1) {
            label = 'Faible';
            cls = 'bg-red-500';
        } else if (s === 2) {
            label = 'Moyen';
            cls = 'bg-amber-400';
        } else if (s === 3) {
            label = 'Bon';
            cls = 'bg-yellow-400';
        } else {
            label = 'Fort';
            cls = 'bg-green-400';
        }

        bar.classList.remove('bg-red-500', 'bg-amber-400', 'bg-yellow-400', 'bg-green-400');
        bar.classList.add(cls);
        txt.textContent = `Force du mot de passe : ${label}`;
    };

    const renderMatch = () => {
        if (!matchTxt || !pwd2) return;
        const a = pwd.value || '';
        const b = pwd2.value || '';
        if (!b) {
            matchTxt.textContent = 'Confirmation : —';
            matchTxt.className = 'text-xs text-gray-300 mt-2';
            return;
        }
        if (a === b) {
            matchTxt.textContent = 'Confirmation : OK';
            matchTxt.className = 'text-xs text-green-300 mt-2';
            return;
        }
        matchTxt.textContent = 'Confirmation : ne correspond pas';
        matchTxt.className = 'text-xs text-red-300 mt-2';
    };

    renderStrength();
    renderMatch();
    pwd.addEventListener('input', () => { renderStrength(); renderMatch(); });
    pwd2?.addEventListener('input', renderMatch);
}

function setupNewsletterModal() {
    const modal = document.getElementById('ts-newsletter-modal');
    const btnOpen = document.querySelector('[data-newsletter-open]');
    const btnClose = document.getElementById('ts-newsletter-close');
    const btnCancel = document.getElementById('ts-newsletter-cancel');
    const btnResend = document.getElementById('ts-newsletter-resend');
    const form = document.getElementById('ts-newsletter-form');
    const input = document.getElementById('ts-newsletter-email');
    if (!modal || !form || !input) return;

    const open = ({ prefillEmail = null, openedBy = 'manual' } = {}) => {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modal.dataset.openedBy = openedBy;
        const prefill = (prefillEmail || window?.TALASHOW_NEWSLETTER_PREFILL_EMAIL || '').toString().trim();
        if (prefill && !String(input.value || '').trim()) {
            input.value = prefill;
        }
        setTimeout(() => input.focus(), 50);
    };
    const close = () => {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        // Anti-spam: si ouvert automatiquement et l’utilisateur ferme -> ne plus afficher pendant 7 jours.
        if (modal.dataset.openedBy === 'auto') {
            localStorage.setItem('talashow_newsletter_invite_dismissed_at', String(Date.now()));
        }
    };

    if (btnOpen) btnOpen.addEventListener('click', () => open({ openedBy: 'manual' }));
    btnClose?.addEventListener('click', close);
    btnCancel?.addEventListener('click', close);
    btnResend?.addEventListener('click', async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const email = String(input.value || '').trim();
        if (!email) {
            talashowToast({ type: 'error', title: 'Action impossible', messages: ['Veuillez saisir votre email.'] });
            input.focus();
            return;
        }
        try {
            const res = await fetch('/newsletter/resend', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                },
                body: JSON.stringify({ email }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data?.success === false) {
                talashowToast({ type: 'error', title: 'Action impossible', messages: [data?.message || 'Renvoi impossible.'] });
                return;
            }
            talashowToast({ type: 'success', title: 'Succès', messages: [data?.message || 'Lien renvoyé.'] });
        } catch (_) {
            talashowToast({ type: 'error', title: 'Action impossible', messages: ['Erreur réseau. Réessayez.'] });
        }
    });
    modal.addEventListener('click', (e) => {
        if (e.target === modal.firstElementChild) close(); // backdrop
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) close();
    });

    form.addEventListener('submit', async (e) => {
        // La validation générique est déjà en place, mais on garde un garde-fou.
        if (!form.checkValidity()) return;

        e.preventDefault();
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const email = String(input.value || '').trim();
        const source = form.querySelector('input[name="source"]')?.value || 'application_cta';

        try {
            const res = await fetch('/newsletter/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                },
                body: JSON.stringify({ email, source }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data?.success === false) {
                talashowToast({ type: 'error', title: 'Action impossible', messages: [data?.message || 'Inscription newsletter impossible.'] });
                return;
            }
            talashowToast({ type: 'success', title: 'Succès', messages: [data?.message || 'Merci !'] });
            close();
            form.reset();
            localStorage.setItem('talashow_newsletter_invite_dismissed_at', String(Date.now()));
        } catch (err) {
            talashowToast({ type: 'error', title: 'Action impossible', messages: ['Erreur réseau. Réessayez.'] });
        }
    });

    // Expose open method (auto invite)
    window.talashowOpenNewsletterModal = () => open({ openedBy: 'auto' });
}

function setupNewsletterAutoInvite() {
    // Pop-up uniquement pour utilisateurs connectés NON abonnés (calculé côté server)
    if (window?.TALASHOW_NEWSLETTER_INVITE_ELIGIBLE !== true) return;

    // Anti-spam: une fois tous les 7 jours
    const dismissedAt = Number(localStorage.getItem('talashow_newsletter_invite_dismissed_at') || '0');
    const sevenDays = 7 * 24 * 60 * 60 * 1000;
    if (dismissedAt && (Date.now() - dismissedAt) < sevenDays) return;

    // Ne pas déclencher sur certaines pages sensibles (OTP, reset) pour ne pas perturber.
    const path = window.location.pathname || '';
    if (/\/verify-otp|\/reset-password|\/forgot-password|\/login|\/register/i.test(path)) return;

    window.setTimeout(() => {
        if (typeof window.talashowOpenNewsletterModal === 'function') {
            window.talashowOpenNewsletterModal();
        }
    }, 7000); // "qlq seconde"
}

// Image Lazy Loading
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    // show skeleton while actual image loads
                    img.classList.add('skeleton');
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                observer.unobserve(img);
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// Notification Permission
function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                console.log('Notification permission granted');
            }
        });
    }
}

function setupScheduledLocalClock() {
    // Ajoute une horloge locale "Il est HH:MM:SS chez nous !" à côté du label "Bientôt disponible"
    // sur les pages programmées (série/épisode) pour éviter les confusions de fuseau horaire.
    const countdown = document.getElementById('ts-countdown');
    if (!countdown) return;

    const candidates = Array.from(document.querySelectorAll('.tracking-wider'));
    const label = candidates.find((el) => /bient[oô]t\s+disponible/i.test(el.textContent || ''));
    if (!label) return;
    if (label.dataset.tsClockMounted === '1') return;
    label.dataset.tsClockMounted = '1';

    const platformTz = (window && window.TALASHOW_PLATFORM_TZ) ? String(window.TALASHOW_PLATFORM_TZ) : null;

    // Afficher le fuseau horaire appliqué par Talashow (remplace le message "UTC" si présent)
    if (platformTz) {
        const note = countdown.nextElementSibling;
        if (note && note.classList && note.classList.contains('text-gray-400')) {
            let tzName = '';
            try {
                if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
                    const parts = new Intl.DateTimeFormat('fr-FR', {
                        timeZone: platformTz,
                        timeZoneName: 'short',
                    }).formatToParts(new Date());
                    tzName = parts.find(p => p.type === 'timeZoneName')?.value || '';
                }
            } catch (_) {
                tzName = '';
            }
            const extra = tzName ? ` (${tzName})` : '';
            note.innerHTML = `Fuseau Talashow : <span class="font-semibold text-gray-200">${platformTz}</span>${extra}.`;
        }
    }

    const span = document.createElement('span');
    span.className = 'ml-2 normal-case text-gray-300';
    span.textContent = 'Il est --:--:-- chez nous !';
    label.appendChild(span);

    const pad2 = (n) => String(n).padStart(2, '0');
    const tick = () => {
        const d = new Date();
        // Si le fuseau Talashow est disponible, on affiche l'heure "plateforme" (pas le fuseau du navigateur).
        if (platformTz && typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
            try {
                const parts = new Intl.DateTimeFormat('fr-FR', {
                    timeZone: platformTz,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                }).formatToParts(d);
                const get = (t) => parts.find(p => p.type === t)?.value || '';
                const hh = get('hour') || pad2(d.getHours());
                const mm = get('minute') || pad2(d.getMinutes());
                const ss = get('second') || pad2(d.getSeconds());
                span.textContent = `Il est ${hh}:${mm}:${ss} chez nous !`;
                return;
            } catch (_) {
                // fallback browser time
            }
        }
        span.textContent = `Il est ${pad2(d.getHours())}:${pad2(d.getMinutes())}:${pad2(d.getSeconds())} chez nous !`;
    };
    tick();
    const t = window.setInterval(tick, 1000);
    window.addEventListener('pagehide', () => window.clearInterval(t), { once: true });
}

// Theme dark / light
function setupThemeToggle() {
    const KEY = 'talashow-theme';

    const getTheme = () => {
        const attr = document.documentElement.getAttribute('data-theme');
        if (attr === 'light' || attr === 'dark') return attr;
        try {
            const saved = localStorage.getItem(KEY);
            if (saved === 'light' || saved === 'dark') return saved;
        } catch (e) {}
        return 'dark';
    };

    const readLabels = () => {
        const btn = document.querySelector('[data-ts-theme-toggle]');
        return {
            dark: btn?.dataset?.labelDark || 'Dark',
            light: btn?.dataset?.labelLight || 'Light',
        };
    };

    const applyTheme = (theme) => {
        const next = theme === 'light' ? 'light' : 'dark';
        const labels = readLabels();
        document.documentElement.setAttribute('data-theme', next);
        try {
            localStorage.setItem(KEY, next);
        } catch (e) {}
        window.__TALASHOW_THEME__ = next;

        const meta = document.querySelector('meta[data-ts-theme-color]');
        if (meta) {
            meta.setAttribute('content', next === 'light' ? '#f7f8fb' : '#0b0b0e');
        }

        document.querySelectorAll('[data-ts-theme-label]').forEach((el) => {
            el.textContent = labels[next] || next;
        });

        document.querySelectorAll('[data-ts-theme-toggle]').forEach((btn) => {
            const switchTo = next === 'light' ? labels.dark : labels.light;
            const base = btn.getAttribute('title') || 'Theme';
            btn.setAttribute('aria-label', `${base}: ${switchTo}`);
            btn.setAttribute('aria-pressed', next === 'light' ? 'true' : 'false');
        });
    };

    applyTheme(getTheme());

    document.querySelectorAll('[data-ts-theme-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            applyTheme(getTheme() === 'light' ? 'dark' : 'light');
        });
    });
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', () => {
    setupSkeletonImages();
    setupPasswordToggles();
    setupConfirmForms();
    setupFormClientValidation();
    setupPasswordStrengthUi();
    setupNewsletterModal();
    setupNewsletterAutoInvite();
    setupSeriesEngagement();
    setupEpisodeViewTracking();
    setupScheduledLocalClock();
    setupThemeToggle();

    // Header sticky translucide au scroll
    const header = document.querySelector('[data-ts-header]');
    if (header) {
        const onScroll = () => {
            header.classList.toggle('is-scrolled', window.scrollY > 12);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // Toasts (pro)
    const toasts = document.querySelectorAll('.js-toast');
    toasts.forEach((toast) => {
        toast.style.transform = 'translateY(-6px)';
        toast.style.opacity = '0';
        toast.style.transition = 'transform 180ms ease, opacity 180ms ease';
        requestAnimationFrame(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        });

        const close = toast.querySelector('.js-toast-close');
        const hide = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-6px)';
            setTimeout(() => toast.remove(), 220);
        };
        close?.addEventListener('click', hide);
        setTimeout(hide, 5500);
    });

    // Hero : fond + coverflow 3D + texte gauche fluide
    const root = document.querySelector('[data-hero-carousel]');
    if (root) {
        const slides = Array.from(root.querySelectorAll('[data-hero-slide]'));
        const bgs = Array.from(root.querySelectorAll('[data-hero-bg]'));
        const prevBtn = root.querySelector('[data-hero-prev]');
        const nextBtn = root.querySelector('[data-hero-next]');
        const dots = Array.from(root.querySelectorAll('[data-hero-thumb]'));
        const infoEl = root.querySelector('[data-hero-info]');
        const titleEl = root.querySelector('[data-hero-title]');
        const descEl = root.querySelector('[data-hero-desc]');
        const metaEl = root.querySelector('[data-hero-meta]');
        const playEl = root.querySelector('[data-hero-play]');
        const moreEl = root.querySelector('[data-hero-more]');
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const isMobile = () => window.matchMedia('(max-width: 768px)').matches;
        let index = 0;
        let timer = null;
        let infoTimer = null;

        const mod = (n, m) => ((n % m) + m) % m;
        const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

        function syncInfo(slide, animate) {
            if (!slide) return;
            const applyText = () => {
                const title = slide.dataset.title || '';
                const desc = slide.dataset.desc || '';
                const genres = slide.dataset.genres || '';
                const episodes = slide.dataset.episodes || '';
                const url = slide.dataset.url || '#';
                if (titleEl) titleEl.textContent = title;
                if (descEl) descEl.textContent = desc;
                if (metaEl) metaEl.textContent = [genres, episodes].filter(Boolean).join(' · ');
                if (playEl) playEl.setAttribute('href', url);
                if (moreEl) moreEl.setAttribute('href', url);
            };

            if (!animate || reduced || !infoEl) {
                applyText();
                infoEl?.classList.remove('is-fading');
                return;
            }

            infoEl.classList.add('is-fading');
            clearTimeout(infoTimer);
            infoTimer = window.setTimeout(() => {
                applyText();
                requestAnimationFrame(() => infoEl.classList.remove('is-fading'));
            }, 180);
        }

        function apply(animateInfo = true) {
            const mobile = isMobile();
            slides.forEach((el, i) => {
                const offset = i - index;
                const abs = Math.abs(offset);
                const active = offset === 0;
                let tx, ry, sc, tz, op, z;

                if (reduced) {
                    tx = 0; ry = 0; sc = active ? 1 : 0.9; tz = 0; op = active ? 1 : 0; z = active ? 8 : 1;
                } else if (mobile) {
                    tx = offset * 72;
                    ry = 0;
                    sc = active ? 1 : Math.max(0.8, 1 - abs * 0.1);
                    tz = 0;
                    op = abs > 2 ? 0 : (active ? 1 : Math.max(0.4, 1 - abs * 0.25));
                    z = 10 - abs;
                } else {
                    tx = offset * 36;
                    ry = clamp(offset, -3, 3) * -28;
                    sc = active ? 1 : Math.max(0.72, 1 - abs * 0.1);
                    tz = active ? 60 : -abs * 70;
                    op = abs > 3 ? 0 : (active ? 1 : Math.max(0.35, 1 - abs * 0.2));
                    z = 20 - abs;
                }

                el.style.setProperty('--tx', String(tx));
                el.style.setProperty('--ry', String(ry));
                el.style.setProperty('--sc', String(sc));
                el.style.setProperty('--tz', String(tz));
                el.style.setProperty('--op', String(op));
                el.style.setProperty('--z', String(z));
                el.classList.toggle('is-active', active);
                el.setAttribute('aria-hidden', active ? 'false' : 'true');
                el.tabIndex = active ? 0 : -1;
            });

            bgs.forEach((bg) => {
                bg.classList.toggle('is-active', Number(bg.dataset.index) === index);
            });

            dots.forEach((d) => {
                const on = Number(d.dataset.index) === index;
                d.classList.toggle('is-active', on);
                if (on) d.setAttribute('aria-current', 'true');
                else d.removeAttribute('aria-current');
            });

            syncInfo(slides[index], animateInfo);
        }

        function go(next, animateInfo = true) {
            index = mod(next, slides.length);
            apply(animateInfo);
        }

        function start() {
            stop();
            if (reduced || slides.length < 2) return;
            timer = window.setInterval(() => go(index + 1), 5500);
        }

        function stop() {
            if (timer) window.clearInterval(timer);
            timer = null;
        }

        prevBtn?.addEventListener('click', (e) => { e.stopPropagation(); go(index - 1); start(); });
        nextBtn?.addEventListener('click', (e) => { e.stopPropagation(); go(index + 1); start(); });
        dots.forEach((d) => {
            d.addEventListener('click', (e) => { e.stopPropagation(); go(Number(d.dataset.index)); start(); });
        });
        slides.forEach((s) => {
            s.addEventListener('click', () => {
                const i = Number(s.dataset.index);
                if (i === index) {
                    const url = s.dataset.url;
                    if (url) window.location.href = url;
                } else {
                    go(i);
                    start();
                }
            });
        });

        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);

        let x0 = null;
        root.addEventListener('touchstart', (e) => { x0 = e.touches?.[0]?.clientX ?? null; }, { passive: true });
        root.addEventListener('touchend', (e) => {
            if (x0 === null) return;
            const x1 = e.changedTouches?.[0]?.clientX ?? x0;
            const dx = x1 - x0;
            x0 = null;
            if (Math.abs(dx) > 40) {
                go(index + (dx < 0 ? 1 : -1));
                start();
            }
        }, { passive: true });

        window.addEventListener('resize', () => apply(false), { passive: true });
        go(0, false);
        start();
    }

    document.querySelectorAll('[data-catalog-reveal]').forEach((el) => {
        el.classList.add('is-visible');
    });

    const initCatalogRow = (row) => {
        if (row.dataset.catalogRowInit === '1') return;
        row.dataset.catalogRowInit = '1';

        const viewport = row.querySelector('.ts-catalog-row__viewport');
        const prev = row.querySelector('[data-catalog-row-prev]');
        const next = row.querySelector('[data-catalog-row-next]');
        if (!viewport) return;

        const scrollStep = () => Math.max(320, Math.floor(viewport.clientWidth * 0.78));

        const updateNav = () => {
            const max = viewport.scrollWidth - viewport.clientWidth - 2;
            if (prev) prev.disabled = viewport.scrollLeft <= 2;
            if (next) next.disabled = viewport.scrollLeft >= max;
        };

        prev?.addEventListener('click', () => {
            viewport.scrollBy({ left: -scrollStep(), behavior: 'smooth' });
        });
        next?.addEventListener('click', () => {
            viewport.scrollBy({ left: scrollStep(), behavior: 'smooth' });
        });

        viewport.addEventListener('scroll', updateNav, { passive: true });
        window.addEventListener('resize', updateNav, { passive: true });
        updateNav();
    };

    const catalogRows = document.querySelectorAll('[data-catalog-row]');
    if (catalogRows.length && 'IntersectionObserver' in window) {
        const rowObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    initCatalogRow(entry.target);
                    rowObserver.unobserve(entry.target);
                });
            },
            { rootMargin: '120px 0px', threshold: 0.01 }
        );
        catalogRows.forEach((row) => rowObserver.observe(row));
    } else {
        catalogRows.forEach(initCatalogRow);
    }
});

// Video Progress Tracking
function trackVideoProgress(videoElement, episodeId) {
    let progressTimer;

    videoElement.addEventListener('timeupdate', () => {
        clearTimeout(progressTimer);
        progressTimer = setTimeout(() => {
            const progress = Math.floor(videoElement.currentTime);
            const duration = Math.floor(videoElement.duration);

            if (progress > 0 && duration > 0) {
                fetch(`/episode/${episodeId}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ progress, duration })
                }).catch(err => console.error('Progress tracking error:', err));
            }
        }, 5000);
    });
}

export { showSkeleton, hideSkeleton, trackVideoProgress, requestNotificationPermission };
