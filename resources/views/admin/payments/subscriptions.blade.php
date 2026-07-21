@extends('admin.layouts.app')

@section('title', 'Abonnements')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Abonnements</h1>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-gray-800">
            <form class="flex flex-wrap gap-3">
                <select name="plan_type" class="px-3 py-2 bg-gray-950 border border-gray-800 rounded-lg text-sm">
                    <option value="">Plan</option>
                    @foreach(['weekly' => 'Hebdo', 'yearly' => 'Annuel'] as $k => $label)
                        <option value="{{ $k }}" {{ request('plan_type') === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="is_active" class="px-3 py-2 bg-gray-950 border border-gray-800 rounded-lg text-sm">
                    <option value="">Statut</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Actif</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactif</option>
                </select>
                <button class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold">Filtrer</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-950">
                    <tr>
                        <th class="text-left p-4">Utilisateur</th>
                        <th class="text-left p-4">Plan</th>
                        <th class="text-left p-4">Début</th>
                        <th class="text-left p-4">Fin</th>
                        <th class="text-left p-4">Statut</th>
                        <th class="text-right p-4">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscriptions as $s)
                        <tr class="border-t border-gray-800">
                            <td class="p-4">
                                <div class="font-semibold">{{ $s->user->name }}</div>
                                <div class="text-gray-400 text-xs">{{ $s->user->email }}</div>
                            </td>
                            <td class="p-4 text-gray-300">{{ $s->plan_type }}</td>
                            <td class="p-4 text-gray-300">{{ $s->starts_at?->format('d/m/Y') }}</td>
                            <td class="p-4 text-gray-300">{{ $s->ends_at?->format('d/m/Y') }}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded text-xs {{ $s->is_active ? 'bg-green-600' : 'bg-gray-700' }}">
                                    {{ $s->is_active ? 'actif' : 'inactif' }}
                                </span>
                            </td>
                            <td class="p-4 text-right font-semibold">${{ number_format($s->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-800">
            {{ $subscriptions->links() }}
        </div>
    </div>
</div>
@endsection

