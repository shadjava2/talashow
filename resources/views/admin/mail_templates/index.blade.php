@extends('admin.layouts.app')

@section('title', 'Admin - Templates Email')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold">Templates Email</h1>
            <p class="text-gray-400 text-sm">Gérez les templates et associez-les aux scénarios (OTP, mot de passe oublié, etc.).</p>
        </div>
        <a href="{{ route('admin.mail-templates.create') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
            Nouveau template
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-gray-900/30 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-800 flex items-center justify-between">
                <div class="font-semibold">Templates</div>
                <div class="text-xs text-gray-400">{{ $templates->count() }} total</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-900/60">
                        <tr class="text-left text-gray-300">
                            <th class="px-4 py-3">Clé</th>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach($templates as $t)
                            <tr class="hover:bg-white/5">
                                <td class="px-4 py-3 font-mono text-xs text-gray-300">{{ $t->key }}</td>
                                <td class="px-4 py-3 text-gray-200">{{ $t->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-xs {{ $t->is_active ? 'bg-green-600/80 text-white' : 'bg-gray-700 text-white' }}">
                                        {{ $t->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.mail-templates.edit', $t->id) }}" class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 text-white text-xs font-semibold transition">
                                        Éditer
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        @if($templates->isEmpty())
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                    Aucun template. Créez-en un.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-800 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-800">
                <div class="font-semibold">Scénarios (bindings)</div>
                <div class="text-xs text-gray-400 mt-1">Chaque scénario utilise 1 template.</div>
            </div>
            <div class="p-4 space-y-3">
                @foreach($events as $k => $label)
                    @php
                        $b = $bindings->get($k);
                        $tpl = $b?->template;
                    @endphp
                    <div class="border border-gray-800 rounded-lg p-3 bg-gray-950/30">
                        <div class="text-xs text-gray-400">{{ $label }}</div>
                        <div class="mt-1 text-sm text-gray-200 font-mono">{{ $k }}</div>
                        <div class="mt-2 text-xs text-gray-300">
                            Template:
                            @if($tpl)
                                <a href="{{ route('admin.mail-templates.edit', $tpl->id) }}" class="text-red-400 hover:text-red-300">
                                    {{ $tpl->name }}
                                </a>
                            @else
                                <span class="text-gray-500">Non configuré</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

