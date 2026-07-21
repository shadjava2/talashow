@extends('admin.layouts.app')

@section('title', 'Admin - Éditer template email')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold">Éditer template</h1>
            <p class="text-gray-400 text-sm">
                Clé: <span class="font-mono">{{ $template->key }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.mail-templates.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition">
                Retour
            </a>
            <form method="POST" action="{{ route('admin.mail-templates.destroy', $template->id) }}" onsubmit="return confirm('Supprimer ce template ?');">
                @csrf
                @method('DELETE')
                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
                    Supprimer
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <form method="POST" action="{{ route('admin.mail-templates.update', $template->id) }}" class="bg-gray-900/30 border border-gray-800 rounded-xl p-6 space-y-4" data-ts-validate="form" novalidate>
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tpl_key" class="block text-sm mb-2">Clé (unique)</label>
                    <input
                        id="tpl_key"
                        name="key"
                        value="{{ old('key', $template->key) }}"
                        class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg"
                        required
                        minlength="3"
                        maxlength="120"
                        pattern="[A-Za-z0-9._-]+"
                    />
                </div>
                <div>
                    <label for="tpl_name" class="block text-sm mb-2">Nom</label>
                    <input id="tpl_name" name="name" value="{{ old('name', $template->name) }}" class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg" required minlength="2" maxlength="255" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tpl_subject" class="block text-sm mb-2">Sujet</label>
                    <input id="tpl_subject" name="subject" value="{{ old('subject', $template->subject) }}" class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg" maxlength="255" />
                    <p class="text-xs text-gray-400 mt-1">Variables autorisées, ex: <code>@{{app_name}}</code></p>
                </div>
                <div>
                    <label for="tpl_bind_event_key" class="block text-sm mb-2">Scénario (binding)</label>
                    <select id="tpl_bind_event_key" name="bind_event_key" class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg">
                        <option value="">(par défaut: même clé)</option>
                        @foreach($events as $k => $label)
                            @php $cur = old('bind_event_key', $boundEventKey ?: $template->key); @endphp
                            <option value="{{ $k }}" {{ $cur === $k ? 'selected' : '' }}>{{ $label }} — {{ $k }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Le scénario détermine quel mail utilisera ce template.</p>
                </div>
            </div>

            <div>
                <label for="tpl_html" class="block text-sm mb-2">HTML</label>
                <textarea id="tpl_html" name="html" rows="18" maxlength="200000" class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg font-mono text-xs">{{ old('html', $template->html) }}</textarea>
                <p class="text-xs text-gray-400 mt-2">
                    Variables communes: <code>@{{app_name}}</code>, <code>@{{logo_url}}</code>, <code>@{{year}}</code>, <code>@{{now}}</code>.
                    <br>OTP: <code>@{{name}}</code>, <code>@{{otp}}</code>, <code>@{{expires_minutes}}</code>.
                    Reset: <code>@{{reset_url}}</code>, <code>@{{email}}</code>, <code>@{{name}}</code>.
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-200">
                <input type="hidden" name="is_active" value="0" />
                <input type="checkbox" name="is_active" value="1" class="rounded" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                Actif
            </label>

            <div class="pt-2">
                <button class="px-5 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                    Enregistrer
                </button>
            </div>
        </form>

        <div class="bg-gray-900/30 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-800">
                <div class="font-semibold">Aperçu</div>
                <div class="text-xs text-gray-400 mt-1">Scénario: <span class="font-mono">{{ $eventKey }}</span></div>
                @if($previewSubject)
                    <div class="text-xs text-gray-300 mt-1">Sujet: <span class="font-semibold">{{ $previewSubject }}</span></div>
                @endif
            </div>
            <div class="p-4">
                <div class="text-xs text-gray-400 mb-2">Rendu HTML (exemple):</div>
                <div class="border border-gray-800 rounded-lg overflow-hidden bg-white">
                    <iframe
                        title="preview"
                        class="w-full"
                        style="height:520px"
                        srcdoc="{{ e($previewHtml) }}"
                    ></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

