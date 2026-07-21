@extends('admin.layouts.app')

@section('title', 'Admin - Nouveau template email')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold">Nouveau template email</h1>
            <p class="text-gray-400 text-sm">Créez un template HTML complet avec variables (ex: <code>@{{logo_url}}</code>).</p>
        </div>
        <a href="{{ route('admin.mail-templates.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition">
            Retour
        </a>
    </div>

    <form method="POST" action="{{ route('admin.mail-templates.store') }}" class="bg-gray-900/30 border border-gray-800 rounded-xl p-6 space-y-4" data-ts-validate="form" novalidate>
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="tpl_key" class="block text-sm mb-2">Clé (unique)</label>
                <input
                    id="tpl_key"
                    name="key"
                    value="{{ old('key') }}"
                    class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg"
                    placeholder="auth.otp"
                    required
                    minlength="3"
                    maxlength="120"
                    pattern="[A-Za-z0-9._-]+"
                />
                <p class="text-xs text-gray-400 mt-1">Ex: <code>auth.otp</code>, <code>auth.password_reset</code></p>
            </div>
            <div>
                <label for="tpl_name" class="block text-sm mb-2">Nom</label>
                <input id="tpl_name" name="name" value="{{ old('name') }}" class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg" required minlength="2" maxlength="255" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="tpl_subject" class="block text-sm mb-2">Sujet</label>
                <input id="tpl_subject" name="subject" value="{{ old('subject') }}" class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg" placeholder="Talashow - ..." maxlength="255" />
            </div>
            <div>
                <label for="tpl_bind_event_key" class="block text-sm mb-2">Associer au scénario</label>
                <select id="tpl_bind_event_key" name="bind_event_key" class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg">
                    <option value="">(par défaut: même clé)</option>
                    @foreach($events as $k => $label)
                        <option value="{{ $k }}" {{ old('bind_event_key') === $k ? 'selected' : '' }}>{{ $label }} — {{ $k }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Permet de choisir quel scénario utilise ce template.</p>
            </div>
        </div>

        <div>
            <label for="tpl_html" class="block text-sm mb-2">HTML</label>
            <textarea id="tpl_html" name="html" rows="16" maxlength="200000" class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg font-mono text-xs">{{ old('html') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">
                Variables communes: <code>@{{app_name}}</code>, <code>@{{logo_url}}</code>, <code>@{{year}}</code>, <code>@{{now}}</code>.
            </p>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-200">
            <input type="hidden" name="is_active" value="0" />
            <input type="checkbox" name="is_active" value="1" class="rounded" {{ old('is_active', '1') ? 'checked' : '' }}>
            Actif
        </label>

        <div class="pt-2 flex items-center gap-3">
            <button class="px-5 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                Créer
            </button>
        </div>
    </form>
</div>
@endsection

