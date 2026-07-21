@extends('admin.layouts.app')

@section('title', 'Admin - Paramètres')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold">Paramètres</h1>
            <p class="text-gray-400 text-sm">Personnalise les libellés affichés sur le site.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-gray-800 rounded-lg p-6 space-y-4" data-ts-validate="form" novalidate>
        @csrf
        @method('PUT')

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Général</div>
            <div class="text-xs text-gray-400">
                Choisis le fuseau horaire utilisé par Talashow pour afficher les dates/heures (admin + frontend).
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            @php
                $tzValue = old('app_timezone', $values['app_timezone'] ?? config('app.timezone', 'UTC'));
                $tzIds = \DateTimeZone::listIdentifiers();
                $tzGroups = [];
                foreach ($tzIds as $tz) {
                    $parts = explode('/', $tz, 2);
                    $group = $parts[0] ?? 'Other';
                    if (!isset($tzGroups[$group])) $tzGroups[$group] = [];
                    $tzGroups[$group][] = $tz;
                }
                ksort($tzGroups);
            @endphp
            <label class="block text-sm mb-2">Fuseau horaire</label>
            <select name="app_timezone" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required>
                {{-- Raccourcis --}}
                @foreach(['Africa/Kinshasa', 'Europe/Paris', 'UTC'] as $quick)
                    @if(in_array($quick, $tzIds, true))
                        <option value="{{ $quick }}" {{ $tzValue === $quick ? 'selected' : '' }}>{{ $quick }}</option>
                    @endif
                @endforeach
                <option disabled>──────────</option>
                {{-- Tous les fuseaux (groupés) --}}
                @foreach($tzGroups as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach($items as $tz)
                            <option value="{{ $tz }}" {{ $tzValue === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-2">
                Recommandé (RDC): <code>Africa/Kinshasa</code>. France: <code>Europe/Paris</code>.
            </p>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Bunny Storage — images (dash.bunny.net)</div>
            <div class="text-xs text-gray-400 space-y-2">
                <p>Posters, jaquettes, miniatures et logo : upload vers une <strong>Storage Zone</strong>, diffusion via votre <strong>pull zone</strong> (URL CDN complète, ex. <code class="text-amber-200/90">https://talashow.b-cdn.net</code>).</p>
                <p>Les secrets sont stockés <strong>chiffrés</strong> en base ; laisse le mot de passe vide pour conserver la valeur actuelle.</p>
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-3">Identifiants Bunny Storage</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-2">Nom de la zone (storage)</label>
                    <input name="bunny_storage_zone_name" value="{{ old('bunny_storage_zone_name', $values['bunny_storage_zone_name']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="ex. talashow-media" />
                </div>
                <div>
                    <label class="block text-sm mb-2">Région API (storage)</label>
                    <input name="bunny_storage_region" value="{{ old('bunny_storage_region', $values['bunny_storage_region'] ?? 'de') }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="de, ny, uk…" />
                    <p class="text-xs text-gray-500 mt-1.5"><strong>de</strong> = Falkenstein (hôte API <code class="text-gray-300">storage.bunnycdn.com</code>, comme sur la page FTP Bunny). Autres : <strong>ny</strong>, <strong>la</strong>, <strong>uk</strong>, <strong>sg</strong>, <strong>syd</strong>…</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-2">URL CDN (pull zone, avec https://)</label>
                    <input name="bunny_storage_cdn_url" type="url" value="{{ old('bunny_storage_cdn_url', $values['bunny_storage_cdn_url']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="https://xxxx.b-cdn.net" />
                    <p class="text-xs text-gray-500 mt-1.5">À prendre dans Bunny : <strong>Pull Zone</strong> → hostname du type <code class="text-gray-300">votrezone.b-cdn.net</code>. Ne pas utiliser <code class="text-gray-300">storage.bunnycdn.com</code> (c’est l’API d’upload, pas l’URL publique des images).</p>
                </div>
                <div>
                    <label class="block text-sm mb-2">Vérifier SSL (upload API)</label>
                    @php $bss = old('bunny_storage_verify_ssl', (string) ($values['bunny_storage_verify_ssl'] ?? 'true')); @endphp
                    <select name="bunny_storage_verify_ssl" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required>
                        <option value="true" {{ $bss === 'true' || $bss === '1' ? 'selected' : '' }}>true (production)</option>
                        <option value="false" {{ $bss === 'false' || $bss === '0' ? 'selected' : '' }}>false (dev Windows si cURL 60)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-2">Mot de passe API (Storage zone password)</label>
                    <input name="bunny_storage_api_key" type="password" value="" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="{{ !empty($values['bunny_storage_api_key_set']) ? '•••••••• (configuré)' : 'Coller le mot de passe FTP/API de la zone' }}" />
                </div>
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Bunny Stream — vidéos (dash.bunny.net)</div>
            <div class="text-xs text-gray-400 space-y-2">
                <p>La lecture utilise <strong>Bunny Stream</strong> : hostname CDN (ex. <code class="text-amber-200/90">vz-xxxxx.b-cdn.net</code> sans <code>https://</code>), <strong>Library ID</strong> et <strong>Stream API key</strong>.</p>
            </div>
        </div>

        <input type="hidden" name="video_provider" value="bunny" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4 md:col-span-2">
                <div class="font-semibold mb-3">Identifiants Bunny Stream</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-2">Library ID</label>
                        <input name="bunny_stream_library_id" value="{{ old('bunny_stream_library_id', $values['bunny_stream_library_id']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="ex. 123456" inputmode="numeric" />
                    </div>
                    <div>
                        <label class="block text-sm mb-2">CDN hostname (pull zone / stream)</label>
                        <input name="bunny_stream_cdn_hostname" value="{{ old('bunny_stream_cdn_hostname', $values['bunny_stream_cdn_hostname']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="vz-xxxxx.b-cdn.net" />
                    </div>
                    <div>
                        <label class="block text-sm mb-2">Vérifier SSL (appels API / upload)</label>
                        @php $bssl = old('bunny_verify_ssl', (string) ($values['bunny_verify_ssl'] ?? 'true')); @endphp
                        <select name="bunny_verify_ssl" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required>
                            <option value="true" {{ $bssl === 'true' || $bssl === '1' ? 'selected' : '' }}>true (production)</option>
                            <option value="false" {{ $bssl === 'false' || $bssl === '0' ? 'selected' : '' }}>false (dev Windows si erreur cURL 60)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-2">URLs HLS signées (CDN token)</label>
                        @php $bsu = old('bunny_stream_signed_urls', (string) ($values['bunny_stream_signed_urls'] ?? 'false')); @endphp
                        <select name="bunny_stream_signed_urls" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required>
                            <option value="false" {{ $bsu === 'false' || $bsu === '0' ? 'selected' : '' }}>Non</option>
                            <option value="true" {{ $bsu === 'true' || $bsu === '1' ? 'selected' : '' }}>Oui (renseigner les clés ci-dessous)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-2">Stream API key (AccessKey)</label>
                        <input name="bunny_stream_api_key" type="password" value="" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="{{ !empty($values['bunny_stream_api_key_set']) ? '•••••••• (configuré)' : 'Coller la clé API de la Video Library' }}" />
                    </div>
                    <div>
                        <label class="block text-sm mb-2">Secret webhook (HMAC, optionnel)</label>
                        <input name="bunny_stream_webhook_secret" type="password" value="" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="{{ !empty($values['bunny_stream_webhook_secret_set']) ? '•••••••• (configuré)' : 'Pour POST /webhooks/bunny/stream' }}" />
                    </div>
                    <div>
                        <label class="block text-sm mb-2">Token security key (URLs signées)</label>
                        <input name="bunny_stream_token_security_key" type="password" value="" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="{{ !empty($values['bunny_stream_token_security_key_set']) ? '•••••••• (configuré)' : 'Dashboard Bunny → CDN → Token authentication' }}" />
                    </div>
                    <div>
                        <label class="block text-sm mb-2">Token key (référence zone, si requis)</label>
                        <input name="bunny_stream_token_key" type="password" value="" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="{{ !empty($values['bunny_stream_token_key_set']) ? '•••••••• (configuré)' : 'Optionnel selon config pull zone' }}" />
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">Les champs secrets vides conservent la valeur déjà enregistrée. Les valeurs sensibles sont stockées chiffrées en base.</p>
            </div>
        </div>

        <hr class="border-gray-700/60 my-2" />

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Branding (logo)</div>
            <div class="text-xs text-gray-400">Le logo peut être uploadé vers Bunny Storage (CDN) et l’URL est enregistrée en base.</div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                @php
                    $logoUrl = old('site_logo_url', $values['site_logo_url'] ?? null);
                    $logoPreview = $logoUrl ?: asset('logo.svg');
                @endphp
                <div class="flex items-center gap-3">
                    <img id="site_logo_preview" src="{{ $logoPreview }}" alt="Logo" class="h-10 w-auto rounded-md shadow-lg shadow-black/20" data-no-skeleton onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}'">
                    <div class="text-sm text-gray-300">
                        <div class="font-semibold">Logo du site</div>
                        <div class="text-xs text-gray-400">Affiché sur le frontend et le backoffice.</div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="file" id="site_logo_file" accept="image/*" class="text-sm">
                    <button type="button" id="site_logo_upload_btn" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold">
                        Uploader (Bunny)
                    </button>
                </div>
            </div>

            <div class="mt-3">
                <label class="block text-sm mb-2">URL du logo (CDN Bunny)</label>
                        <input name="site_logo_url" id="site_logo_url" type="url" value="{{ $logoUrl }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="https://votre-zone.b-cdn.net/branding/...." />
                <p id="site_logo_status" class="text-xs text-gray-400 mt-2"></p>
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Tarifs (configurables)</div>
            <div class="text-xs text-gray-400">L’admin peut modifier les prix sans mise à jour du code.</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
                <div class="font-semibold mb-3">Abonnements</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-2">Prix / Semaine (USD)</label>
                        <input name="price_subscription_weekly" type="number" inputmode="decimal" step="0.01" min="0" value="{{ old('price_subscription_weekly', $values['price_subscription_weekly']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm mb-2">Prix / An (USD)</label>
                        <input name="price_subscription_yearly" type="number" inputmode="decimal" step="0.01" min="0" value="{{ old('price_subscription_yearly', $values['price_subscription_yearly']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                    </div>
                </div>
            </div>

            <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
                <div class="font-semibold mb-3">Pièces (packs)</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach([500,1000,2000,3000] as $p)
                        <div class="border border-gray-700/60 rounded-lg p-3 bg-gray-900/40">
                            <div class="font-semibold mb-2">{{ $p }} pièces</div>
                            <label class="block text-sm mb-1">Prix (USD)</label>
                            <input name="coins_pack_{{ $p }}_price" type="number" inputmode="decimal" step="0.01" min="0" value="{{ old('coins_pack_'.$p.'_price', $values['coins_pack_'.$p.'_price']) }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg" />
                            <label class="block text-sm mb-1 mt-2">Bonus</label>
                            <input name="coins_pack_{{ $p }}_reward" type="number" inputmode="numeric" step="1" min="0" value="{{ old('coins_pack_'.$p.'_reward', $values['coins_pack_'.$p.'_reward']) }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">PayPal (moyen de paiement)</div>
            <div class="text-xs text-gray-400">
                Les utilisateurs peuvent payer via <strong>compte PayPal</strong> ou <strong>carte (Visa/Mastercard)</strong> via PayPal. Secret chiffré en base.
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm mb-2">Mode</label>
                <select name="paypal_mode" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg">
                    <option value="sandbox" {{ old('paypal_mode', $values['paypal_mode']) === 'sandbox' ? 'selected' : '' }}>sandbox</option>
                    <option value="live" {{ old('paypal_mode', $values['paypal_mode']) === 'live' ? 'selected' : '' }}>live</option>
                </select>
            </div>
            <div>
                <label class="block text-sm mb-2">Devise</label>
                <input name="paypal_currency" value="{{ old('paypal_currency', $values['paypal_currency']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg uppercase" required minlength="3" maxlength="3" pattern="[A-Za-z]{3}" />
                <p class="text-xs text-gray-400 mt-1">Ex: USD, EUR</p>
            </div>
            <div>
                <label class="block text-sm mb-2">Client ID</label>
                <input name="paypal_client_id" value="{{ old('paypal_client_id', $values['paypal_client_id']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
            </div>
        </div>

        <div>
            <label class="block text-sm mb-2">Client Secret</label>
            <input name="paypal_client_secret" type="password" value="" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="{{ $values['paypal_client_secret_set'] ? '•••••••• (configuré)' : 'Non configuré' }}" />
            <p class="text-xs text-gray-400 mt-1">Laisse vide pour conserver.</p>
        </div>

        <hr class="border-gray-700/60 my-2" />

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Live chat (Tawk.to)</div>
            <div class="text-xs text-gray-400">
                Colle ici l’URL <code>https://embed.tawk.to/...</code> ou tout le script Tawk.to. Le site utilisera cette configuration sans mise à jour de code.
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm mb-2">Activer Tawk.to</label>
                @php $tawkEnabled = old('tawk_to_enabled', (string) ($values['tawk_to_enabled'] ?? 'true')); @endphp
                <select name="tawk_to_enabled" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required>
                    <option value="true" {{ $tawkEnabled === 'true' || $tawkEnabled === '1' ? 'selected' : '' }}>Oui</option>
                    <option value="false" {{ $tawkEnabled === 'false' || $tawkEnabled === '0' ? 'selected' : '' }}>Non</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm mb-2">Widget code / URL</label>
                @php
                    $tawkCurrent = (string) ($values['tawk_to_embed_url'] ?? '');
                    $tawkDefaultText = $tawkCurrent !== '' ? $tawkCurrent : '';
                @endphp
                <textarea
                    name="tawk_to_widget"
                    rows="4"
                    maxlength="5000"
                    class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg font-mono text-xs"
                    placeholder="https://embed.tawk.to/xxxx/xxxx (ou colle le script complet ici)"
                >{{ old('tawk_to_widget', $tawkDefaultText) }}</textarea>
                <p class="text-xs text-gray-400 mt-1">
                    Valeur actuelle : {{ $tawkCurrent !== '' ? $tawkCurrent : 'Non configuré' }}. Laisse vide pour conserver.
                </p>
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Emails (SMTP) — configuration en base de données</div>
            <div class="text-xs text-gray-400">
                Permet l’envoi des OTP et des emails “Mot de passe oublié”. Le mot de passe est stocké <strong>chiffré</strong>. Laisse le champ mot de passe vide pour conserver.
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-2">SMTP Host</label>
                <input name="smtp_host" value="{{ old('smtp_host', $values['smtp_host']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="mail.tala-show.com" />
            </div>
            <div>
                <label class="block text-sm mb-2">SMTP Port</label>
                <input name="smtp_port" type="number" inputmode="numeric" min="1" max="65535" value="{{ old('smtp_port', $values['smtp_port']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="465" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm mb-2">Encryption</label>
                @php $enc = old('smtp_encryption', $values['smtp_encryption'] ?? 'tls'); @endphp
                <select name="smtp_encryption" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg">
                    <option value="tls" {{ $enc === 'tls' ? 'selected' : '' }}>tls</option>
                    <option value="ssl" {{ $enc === 'ssl' ? 'selected' : '' }}>ssl</option>
                    <option value="none" {{ $enc === 'none' ? 'selected' : '' }}>none</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Pour cPanel souvent: <strong>ssl</strong> + port <strong>465</strong>.</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm mb-2">SMTP Username</label>
                <input name="smtp_username" value="{{ old('smtp_username', $values['smtp_username']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="contact@tala-show.com" />
            </div>
        </div>

        <div>
            <label class="block text-sm mb-2">SMTP Password</label>
            <input name="smtp_password" type="password" value="" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="{{ $values['smtp_password_set'] ? '•••••••• (configuré)' : 'Non configuré' }}" />
            <p class="text-xs text-gray-400 mt-1">Laisse vide pour conserver.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-2">From address</label>
                <input name="mail_from_address" type="email" value="{{ old('mail_from_address', $values['mail_from_address']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="contact@tala-show.com" />
            </div>
            <div>
                <label class="block text-sm mb-2">From name</label>
                <input name="mail_from_name" value="{{ old('mail_from_name', $values['mail_from_name']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="Talashow" />
            </div>
        </div>

        <hr class="border-gray-700/60 my-2" />

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Communauté (liens du footer)</div>
            <div class="text-xs text-gray-400">Ces liens sont affichés dans le footer du site. Laisse vide pour masquer un lien.</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-2">Email (footer) — “Écrivez-nous”</label>
                <input name="footer_contact_email" type="email" value="{{ old('footer_contact_email', $values['footer_contact_email']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="contact@tala-show.com" />
                <p class="text-xs text-gray-400 mt-1">Optionnel (affiché comme email cliquable).</p>
            </div>
            <div>
                <label class="block text-sm mb-2">Téléphone (footer) — “Contactez-nous”</label>
                <input name="footer_phone" value="{{ old('footer_phone', $values['footer_phone'] ?? '') }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="+243 000 000 000" />
                <p class="text-xs text-gray-400 mt-1">Optionnel (affiché comme lien tel:).</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-2">Libellé “Coopération d'affaires”</label>
                <input name="footer_business_label" value="{{ old('footer_business_label', $values['footer_business_label']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="Coopération d'affaires" />
                <p class="text-xs text-gray-400 mt-1">Optionnel (affiché seulement si l’URL est renseignée).</p>
            </div>
            <div>
                <label class="block text-sm mb-2">URL “Coopération d'affaires”</label>
                <input name="footer_business_url" value="{{ old('footer_business_url', $values['footer_business_url']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="https://..." />
                <p class="text-xs text-gray-400 mt-1">Laisse vide pour masquer le lien.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm mb-2">Facebook URL</label>
                <input name="social_facebook_url" value="{{ old('social_facebook_url', $values['social_facebook_url']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="https://facebook.com/..." />
            </div>
            <div>
                <label class="block text-sm mb-2">Youtube URL</label>
                <input name="social_youtube_url" value="{{ old('social_youtube_url', $values['social_youtube_url']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="https://youtube.com/@..." />
            </div>
            <div>
                <label class="block text-sm mb-2">Tiktok URL</label>
                <input name="social_tiktok_url" value="{{ old('social_tiktok_url', $values['social_tiktok_url']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="https://tiktok.com/@..." />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm mb-2">Préfixe court</label>
                <input name="episode_label_short" value="{{ old('episode_label_short', $values['episode_label_short']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                <p class="text-xs text-gray-400 mt-1">Ex: EP</p>
            </div>
            <div>
                <label class="block text-sm mb-2">Singulier</label>
                <input name="episode_label_singular" value="{{ old('episode_label_singular', $values['episode_label_singular']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                <p class="text-xs text-gray-400 mt-1">Ex: Épisode</p>
            </div>
            <div>
                <label class="block text-sm mb-2">Pluriel</label>
                <input name="episode_label_plural" value="{{ old('episode_label_plural', $values['episode_label_plural']) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                <p class="text-xs text-gray-400 mt-1">Ex: Épisodes</p>
            </div>
        </div>

        <div class="pt-2">
            <button class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                Enregistrer
            </button>
        </div>
    </form>

    {{-- Form séparé (ne pas imbriquer dans le form des paramètres) --}}
    <div class="mt-4 bg-gray-800 rounded-lg p-6 space-y-3">
        <div class="font-semibold">Tester l’envoi email</div>
        <div class="text-xs text-gray-400">
            Envoie un email “test SMTP” vers une adresse de ton choix (utilise le template <code>system.test_email</code>).
        </div>
        <form method="POST" action="{{ route('admin.settings.test-email') }}" class="flex flex-col md:flex-row gap-3">
            @csrf
            <input name="test_email_to" value="{{ old('test_email_to', $values['footer_contact_email'] ?? '') }}" class="flex-1 px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="destinataire@exemple.com" />
            <button type="submit" class="px-4 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                Envoyer un test
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const input = document.getElementById('site_logo_file');
  const btn = document.getElementById('site_logo_upload_btn');
  const urlField = document.getElementById('site_logo_url');
  const preview = document.getElementById('site_logo_preview');
  const status = document.getElementById('site_logo_status');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  if (!btn || !input || !urlField || !preview) return;

  function setStatus(msg, isError = false) {
    if (!status) return;
    status.textContent = msg || '';
    status.className = 'text-xs mt-2 ' + (isError ? 'text-red-300' : 'text-gray-400');
  }

  btn.addEventListener('click', async () => {
    const file = input.files?.[0];
    if (!file) {
      setStatus('Choisis un fichier image.', true);
      return;
    }

    btn.disabled = true;
    btn.classList.add('opacity-60', 'cursor-not-allowed');
    setStatus('Upload en cours… Ne quitte pas la page.');

    try {
      const fd = new FormData();
      fd.append('type', 'logo');
      fd.append('file', file);

      const res = await fetch('{{ route('admin.media.upload-image') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: fd,
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        setStatus(data.message || 'Upload échoué.', true);
        return;
      }

      urlField.value = data.url;
      preview.src = data.url;
      window.talashowRevealImageAfterSrcChange?.(preview);
      setStatus('Logo uploadé ✅ Pense à cliquer sur “Enregistrer” pour sauvegarder.');
    } catch (e) {
      setStatus('Erreur: ' + (e?.message || e), true);
    } finally {
      btn.disabled = false;
      btn.classList.remove('opacity-60', 'cursor-not-allowed');
    }
  });
})();
</script>
@endpush
