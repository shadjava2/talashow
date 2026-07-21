@extends('layouts.app')

@section('title', __('ui.payment.recharge.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold mb-8">{{ __('ui.payment.recharge.title') }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- User Info -->
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
                        <span id="donation" class="text-gray-400">{{ __('ui.payment.reward_coins') }}</span>
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

        <!-- Payment Options -->
        <div class="lg:col-span-2">
            <!-- Message d'erreur (affiché au clic si PayPal non configuré) -->
            <div id="payment-not-configured-alert" class="hidden mb-6 bg-red-600/15 border border-red-600/30 rounded-lg p-4 text-red-100">
                <span class="font-semibold">{{ __('ui.payment.not_configured_title') }}</span>
                {{ __('ui.payment.not_configured_subtitle') }}
            </div>

            <!-- Subscriptions -->
            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('ui.payment.membership.title') }}</h2>
                <div class="mb-4 bg-green-500/10 border border-green-500/20 rounded-lg p-4 text-sm text-green-100">
                    {!! __('ui.payment.membership.subscription_terms_html') !!}
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Weekly Plan -->
                    <div class="relative">
                        <div class="border-2 border-red-600 rounded-lg p-6 bg-gradient-to-br from-pink-500/10 to-red-500/10">
                            <div class="absolute top-2 right-2 bg-pink-500 text-white text-xs px-2 py-1 rounded">
                                {{ __('ui.payment.membership.most_users_prefer') }}
                            </div>
                            <div class="flex items-center mb-4">
                                <svg class="w-6 h-6 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="font-semibold">{{ __('ui.payment.membership.badge') }}</span>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-baseline">
                                    <span class="text-3xl font-bold">{{ $paypalCurrency }} {{ number_format($prices['weekly'], 2) }}</span>
                                    <span class="text-gray-400 ml-2">{{ __('ui.payment.membership.per_week') }}</span>
                                </div>
                                <div class="text-sm text-gray-400 line-through">USD 19.99</div>
                            </div>
                            <p class="text-sm text-gray-300 mb-4">{{ __('ui.payment.membership.weekly_desc') }}</p>
                            <p class="text-xs text-gray-400 mb-4">{{ __('ui.payment.membership.renewal_hint') }}</p>
                            <button type="button" data-paypal-kind="subscription" data-plan-type="weekly" class="js-paypal-cta w-full py-2 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                                {{ __('ui.payment.choose_offer') }}
                            </button>
                        </div>
                    </div>

                    <!-- Yearly Plan -->
                    <div class="relative">
                        <div class="border-2 border-gray-700 rounded-lg p-6 hover:border-red-600 transition">
                            <div class="flex items-center mb-4">
                                <svg class="w-6 h-6 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="font-semibold">{{ __('ui.payment.membership.badge') }}</span>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-baseline">
                                    <span class="text-3xl font-bold">{{ $paypalCurrency }} {{ number_format($prices['yearly'], 2) }}</span>
                                    <span class="text-gray-400 ml-2">{{ __('ui.payment.membership.per_year') }}</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-300 mb-4">{{ __('ui.payment.membership.yearly_desc') }}</p>
                            <p class="text-xs text-gray-400 mb-4">{{ __('ui.payment.membership.renewal_hint') }}</p>
                            <button type="button" data-paypal-kind="subscription" data-plan-type="yearly" class="js-paypal-cta w-full py-2 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                                {{ __('ui.payment.choose_offer') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coins Packages -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('ui.payment.coins.title') }}</h2>
                <div class="mb-4 bg-amber-500/10 border border-amber-500/20 rounded-lg p-4 text-sm text-amber-100">
                    {!! __('ui.payment.coins.terms_html', ['days' => 7]) !!}
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($packages as $key => $package)
                    <div class="relative">
                        <div class="border border-gray-700 rounded-lg p-4 hover:border-red-600 transition text-center">
                            <div class="text-4xl mb-2">🪙</div>
                            <div class="font-bold mb-1">{{ trans_choice('ui.payment.coins.coins_count', (int) $package['coins'], ['count' => (int) $package['coins']]) }}</div>
                            @if($package['reward'] > 0)
                            <div class="text-sm text-green-400 mb-2">+{{ trans_choice('ui.payment.coins.reward_count', (int) $package['reward'], ['count' => (int) $package['reward']]) }}</div>
                            @endif
                            <div class="font-semibold text-lg mb-3">{{ $paypalCurrency }} {{ number_format($package['price'], 2) }}</div>
                            <button type="button" data-paypal-kind="coins" data-coin-package="{{ $key }}" class="js-paypal-cta w-full py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
                                {{ __('ui.payment.choose_offer') }}
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div id="payment-step-wrap" class="mt-6 bg-gray-900/30 border border-gray-700/60 rounded-lg p-4 hidden">
                    <h3 class="font-semibold text-lg mb-2">{{ __('ui.payment.payment_step_title') }}</h3>
                    <p class="text-sm text-gray-300 mb-4">{!! __('ui.payment.pay_step_intro') !!}</p>
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
</div>
@endsection

@push('scripts')
<script>
(() => {
  const paypalEnabled = {{ $paypalEnabled ? 'true' : 'false' }};
  const alertBox = document.getElementById('payment-not-configured-alert');
  const paymentStepWrap = document.getElementById('payment-step-wrap');

  function showAlert() {
    if (!alertBox) return;
    alertBox.classList.remove('hidden');
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    window.clearTimeout(window.__talaPayAlertTimer);
    window.__talaPayAlertTimer = window.setTimeout(() => {
      alertBox.classList.add('hidden');
    }, 4500);
  }

  document.querySelectorAll('.js-paypal-cta').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (paymentStepWrap) {
        paymentStepWrap.classList.remove('hidden');
        paymentStepWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      if (!paypalEnabled) {
        showAlert();
      }
    });
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
  const __talaPay = {
    choose_first: @json(__('ui.payment.choose_first')),
    selection_saved: @json(__('ui.payment.selection_saved')),
  };
  let selection = null; // { kind, plan_type?, coin_package? }

  function setStatus(msg) { if (statusEl) statusEl.textContent = msg || ''; }

  document.querySelectorAll('.js-paypal-cta').forEach((btn) => {
    btn.addEventListener('click', () => {
      const kind = btn.getAttribute('data-paypal-kind');
      const planType = btn.getAttribute('data-plan-type');
      const coinPackage = btn.getAttribute('data-coin-package');
      selection = { kind, plan_type: planType, coin_package: coinPackage };
      setStatus(__talaPay.selection_saved);
    });
  });

  if (!window.paypal || !btnContainer) return;

  window.paypal.Buttons({
    createOrder: async () => {
      if (!selection) {
        setStatus(__talaPay.choose_first);
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
      setStatus('Paiement confirmé ✅');
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
