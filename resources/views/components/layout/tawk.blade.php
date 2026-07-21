@php
    $settings = $settings ?? app(\App\Services\SettingsService::class);

    $tawkEnabledRaw = (string) ($settings->get('tawk_to_enabled', (string) config('services.tawk.enabled', true)) ?? 'true');
    $tawkEnabled = in_array(strtolower($tawkEnabledRaw), ['1', 'true', 'yes', 'on'], true);

    $tawkUrl = trim((string) $settings->get('tawk_to_embed_url', ''));
    if ($tawkUrl === '') {
        $tawkUrl = trim((string) config('services.tawk.embed_url', ''));
    }
@endphp

@if($tawkEnabled && $tawkUrl !== '')
<script>
(function () {
    if (window.__TALASHOW_TAWK_LOADED) return;
    window.__TALASHOW_TAWK_LOADED = true;

    window.Tawk_API = window.Tawk_API || {};
    window.Tawk_LoadStart = new Date();

    function loadTawk() {
        var s1 = document.createElement('script');
        var s0 = document.getElementsByTagName('script')[0];
        s1.async = true;
        s1.src = @json($tawkUrl);
        s1.charset = 'UTF-8';
        s1.setAttribute('crossorigin', '*');
        s1.onerror = function () {
            console.warn('[Talashow] Tawk.to : échec de chargement du script.');
        };
        s0.parentNode.insertBefore(s1, s0);
    }

    if (document.readyState === 'complete') {
        setTimeout(loadTawk, 400);
    } else {
        window.addEventListener('load', function () { setTimeout(loadTawk, 400); }, { once: true });
    }
})();
</script>
@endif
