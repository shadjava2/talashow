@extends('admin.layouts.app')

@section('title', 'Admin - Utilisateurs')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold">Utilisateurs</h1>
            <p class="text-gray-400 text-sm">Gestion des rôles: Admin / Editor / User</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <input name="search" value="{{ request('search') }}" class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg" placeholder="email ou nom" />
            <button class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg font-semibold">Rechercher</button>
        </form>
    </div>

    <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/60">
        <table class="w-full text-sm">
            <thead class="bg-gray-900/60">
                <tr>
                    <th class="text-left px-4 py-3">Nom</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Statut</th>
                    <th class="text-left px-4 py-3">Rôle</th>
                    <th class="text-right px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr class="border-t border-gray-700/60">
                    <td class="px-4 py-3 font-semibold">{{ $u->name }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        @if($u->is_active)
                            <span class="inline-flex px-2 py-1 rounded bg-green-500/15 text-green-200 text-xs font-semibold">Actif</span>
                        @else
                            <span class="inline-flex px-2 py-1 rounded bg-red-500/15 text-red-200 text-xs font-semibold">Bloqué</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.users.update-role', $u->id) }}" class="flex items-center gap-2" data-ts-validate="form" novalidate>
                            @csrf
                            @method('PUT')
                            <select name="role_id" required class="px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg">
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ (int) $u->role_id === (int) $r->id ? 'selected' : '' }}>
                                        {{ $r->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="px-3 py-2 bg-red-600 hover:bg-red-700 rounded-lg font-semibold">Enregistrer</button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <form
                                method="POST"
                                action="{{ route('admin.users.toggle-active', $u->id) }}"
                                data-ts-confirm
                                data-ts-confirm-title="{{ $u->is_active ? 'Bloquer le compte' : 'Débloquer le compte' }}"
                                data-ts-confirm-message="{{ $u->is_active ? "Bloquer ce compte ? Il ne pourra plus se connecter." : "Débloquer ce compte ?" }}"
                                data-ts-confirm-confirm="{{ $u->is_active ? 'Bloquer' : 'Débloquer' }}"
                                data-ts-confirm-cancel="Annuler"
                            >
                                @csrf
                                @method('PUT')
                                <button
                                    class="px-3 py-2 rounded-lg font-semibold text-sm {{ $u->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }}"
                                >
                                    {{ $u->is_active ? 'Bloquer' : 'Débloquer' }}
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('admin.users.destroy', $u->id) }}"
                                data-ts-confirm
                                data-ts-confirm-title="Supprimer le compte"
                                data-ts-confirm-message="Supprimer ce compte ? (action irréversible)"
                                data-ts-confirm-confirm="Supprimer"
                                data-ts-confirm-cancel="Annuler"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    class="px-3 py-2 rounded-lg font-semibold text-sm bg-gray-900 hover:bg-gray-950 border border-gray-700 text-red-200"
                                >
                                    Supprimer
                                </button>
                            </form>
                        </div>
                        @if($u->is_admin)
                            <div class="mt-2 text-xs text-gray-500">Admin legacy: ON</div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="mt-6">{{ $users->links() }}</div>
    @endif
</div>
@endsection

