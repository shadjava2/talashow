@extends('admin.layouts.app')

@section('title', 'Admin - Envoyer une newsletter')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
        <div>
            <h1 class="text-3xl font-bold">Envoyer une newsletter</h1>
            <p class="text-gray-400 text-sm">Envoi aux abonnÃ©s confirmÃ©s (non dÃ©sinscrits).</p>
        </div>
        <a href="{{ route('admin.newsletter.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition">
            Retour
        </a>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700/60">
        <form method="POST" action="{{ route('admin.newsletter.send') }}" class="space-y-4" data-ts-validate="form" novalidate>
            @csrf
            <div>
                <label class="block text-sm mb-2">Titre</label>
                <input name="headline" required maxlength="120" value="{{ old('headline') }}"
                       class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="Ex: Nouveautés Talashow" />
            </div>
            <div>
                <label class="block text-sm mb-2">Contenu (HTML simple)</label>
                <textarea name="content_html" required rows="10" maxlength="20000"
                          class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg"
                          placeholder="<p>Bonjour...</p>">{{ old('content_html') }}</textarea>
                <p class="text-xs text-gray-400 mt-2">
                    Astuce: utilisez des balises simples (<code>&lt;p&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;a&gt;</code>). Le template gère la mise en page.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-sm mb-2">Email de test</label>
                    <input id="test_email" name="test_email" type="email" value="{{ old('test_email') }}"
                           class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="test@domaine.com" />
                </div>
                <div class="flex gap-2">
                    <button id="btn-send-test" type="submit"
                            formaction="{{ route('admin.newsletter.send-test') }}"
                            class="w-full px-4 py-3 bg-white/10 hover:bg-white/20 rounded-lg font-semibold transition">
                        Envoyer test
                    </button>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end">
                <button id="btn-send-all" type="submit" class="px-5 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                    Planifier lâ€™envoi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const form = document.querySelector('form[action*="/newsletter/send"]');
  const testEmail = document.getElementById('test_email');
  const btnTest = document.getElementById('btn-send-test');
  const btnAll = document.getElementById('btn-send-all');

  // Email test requis uniquement si on clique "Envoyer test"
  btnTest?.addEventListener('click', () => {
    if (testEmail) testEmail.required = true;
  });
  btnAll?.addEventListener('click', () => {
    if (testEmail) testEmail.required = false;
  });

  // Anti double-submit
  form?.addEventListener('submit', () => {
    btnTest && (btnTest.disabled = true);
    btnAll && (btnAll.disabled = true);
  });
})();
</script>
@endpush

