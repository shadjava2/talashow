@extends('layouts.app')

@section('title', __('ui.payment.donation.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-bold">{{ __('ui.payment.donation.title') }}</h1>
        </div>
        <a href="{{ route('payment.recharge') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg transition text-sm font-semibold">
            {{ __('ui.payment.donation.see_recharge') }}
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- User Info --}}
        <div class="lg:col-span-1">
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="text-center mb-4">
                    <div class="w-20 h-20 bg-gray-700 rounded-full mx-auto flex items-center justify-center mb-4">
                        <span class="text-2xl font-bold">{{ substr($user->name, 0, 1) }}</span>
                    </div>
                    <h3 class="text-xl font-semibold">{{ $user->name }}</h3>
                    <p class="text-gray-400">{{ __('ui.common.id') }}: {{ $user->id }}</p>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('ui.nav.coins') }}</span>
                        <span class="font-semibold">{{ $user->coins }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('ui.payment.reward_coins') }}</span>
                        <span class="font-semibold">{{ $user->reward_coins }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-700">
                        <div class="flex justify-between">
                            <span class="text-gray-400">{{ __('ui.common.total') }}</span>
                            <span class="font-bold text-red-500">{{ $user->total_coins }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Donation Packs --}}
        <div class="lg:col-span-2">
            <div id="payment-not-configured-alert" class="hidden mb-6 bg-red-600/15 border border-red-600/30 rounded-lg p-4 text-red-100">
                <span class="font-semibold">{{ __('ui.payment.not_configured_title') }}</span>
                {{ __('ui.payment.not_configured_subtitle') }}
            </div>

            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold">{{ __('ui.payment.donation.free_title') }}</h2>
                <p class="text-sm text-gray-300 mt-2">
                    {{ __('ui.payment.donation.free_subtitle') }}
                </p>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm mb-2">{{ __('ui.payment.donation.amount_label', ['currency' => $paypalCurrency]) }}</label>
                        <input
                            id="donation_amount"
                            type="number"
                            inputmode="decimal"
                            min="{{ $minDonation }}"
                            max="{{ $maxDonation }}"
                            step="0.01"
                            placeholder="{{ __('ui.payment.donation.amount_placeholder') }}"
                            class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg"
                        />
                        <p class="text-xs text-gray-400 mt-2">
                            {{ __('ui.payment.donation.thanks_hint') }}
                        </p>
                    </div>
                    <div>
                        <button
                            type="button"
                            id="donation_custom_btn"
                            class="w-full py-3 px-4 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition"
                        >
                            {{ __('ui.payment.donation.give_now') }}
                        </button>
                    </div>
                </div>
            </div>
            <div id="donation-payment-step-wrap" class="bg-gray-800 rounded-lg p-6 hidden">
                <h3 class="font-semibold text-lg mb-2">{{ __('ui.payment.payment_step_title') }}</h3>
                <p class="text-sm text-gray-300 mb-4">{!! __('ui.payment.pay_step_intro_donation') !!}</p>
                <div id="paypal-area" class="{{ $paypalEnabled ? '' : 'hidden' }}">
                    @if($paypalEnabled)
                        <div id="paypal-button-container" class="max-w-md"></div>
                        <p id="paypal-status" class="text-xs text-gray-400 mt-2"></p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const paypalEnabled = {{ $paypalEnabled ? 'true' : 'false' }};
  const alertBox = document.getElementById('payment-not-configured-alert');
  const paymentStepWrap = document.getElementById('donation-payment-step-wrap');
  const customBtn = document.getElementById('donation_custom_btn');
  const amountEl = document.getElementById('donation_amount');

  function showAlert() {
    if (!alertBox) return;
    alertBox.classList.remove('hidden');
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    window.clearTimeout(window.__talaPayAlertTimer);
    window.__talaPayAlertTimer = window.setTimeout(() => {
      alertBox.classList.add('hidden');
    }, 4500);
  }

  customBtn?.addEventListener('click', () => {
    const v = Number(amountEl?.value || 0);
    if (!v || Number.isNaN(v)) {
      alert(@json(__('ui.payment.donation.alert_enter_amount')));
      return;
    }
    const min = Number(amountEl?.min || 1);
    const max = Number(amountEl?.max || 500);
    if (v < min || v > max) {
      alert(@json(__('ui.payment.donation.alert_amount_between')) + ` ${min} ` + @json(__('ui.payment.donation.and')) + ` ${max}.`);
      return;
    }
    if (paymentStepWrap) {
      paymentStepWrap.classList.remove('hidden');
      paymentStepWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    if (!paypalEnabled) {
      showAlert();
      return;
    }
    window.__talaDonationCustomSelection = { kind: 'donation', donation_amount: v };
  });
})();
</script>
@if($paypalEnabled && $paypalClientId)
<script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $paypalCurrency }}&intent=capture"></script>
<script>
(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const statusEl = document.getElementById('paypal-status');
  const btnContainer = document.getElementById('paypal-button-container');
  let selection = null; // { kind, donation_amount }

  function setStatus(msg) { if (statusEl) statusEl.textContent = msg || ''; }

  if (!window.paypal || !btnContainer) return;

  window.paypal.Buttons({
    createOrder: async () => {
      // Donation libre (si présent)
      if (window.__talaDonationCustomSelection) {
        selection = window.__talaDonationCustomSelection;
        window.__talaDonationCustomSelection = null;
        setStatus('Donation libre sélectionnée. Paiement PayPal...');
      }
      if (!selection) {
        setStatus('Choisis d’abord un montant libre.');
        throw new Error('No selection');
      }
      setStatus('Création de la commande PayPal...');
      const res = await fetch('{{ route('payment.paypal.create-order') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
        body: JSON.stringify(selection),
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        setStatus(data.message || 'Paiement indisponible.');
        throw new Error(data.message || 'Create order failed');
      }
      setStatus('Commande créée. Redirection PayPal...');
      return data.orderID;
    },
    onApprove: async (data) => {
      setStatus('Validation du paiement...');
      const res = await fetch('{{ route('payment.paypal.capture-order') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
        body: JSON.stringify({ orderID: data.orderID }),
      });
      const out = await res.json();
      if (!res.ok || !out.success) {
        setStatus(out.message || 'Erreur lors de la validation.');
        return;
      }
      setStatus('Merci pour votre soutien ✅');
      window.location.reload();
    },
    onError: (err) => {
      setStatus('Erreur PayPal: ' + (err?.message || err));
    }
  }).render('#paypal-button-container');
})();
</script>
@endif
@endpush

