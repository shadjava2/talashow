@extends('layouts.app')

@section('title', __('ui.payment.recharge.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold mb-8 ts-page-title">{{ __('ui.payment.recharge.title') }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- User Info -->
        <div class="lg:col-span-1">
            <div class="ts-surface ts-surface--pad">
                <div class="text-center mb-4">
                    <div class="ts-avatar-circle">
                        <span>{{ substr($user->name, 0, 1) }}</span>
                    </div>
                    <h3 class="text-xl font-semibold" style="color: var(--ts-text-primary)">{{ $user->name }}</h3>
                    <p class="ts-text-muted text-sm">{{ __('ui.common.id') }}: {{ $user->id }}</p>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="ts-text-muted">{{ __('ui.nav.coins') }}</span>
                        <span class="font-semibold" style="color: var(--ts-text-primary)">{{ $user->coins }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span id="donation" class="ts-text-muted">{{ __('ui.payment.reward_coins') }}</span>
                        <span class="font-semibold" style="color: var(--ts-text-primary)">{{ $user->reward_coins }}</span>
                    </div>
                    <div class="pt-3" style="border-top: 1px solid var(--ts-border)">
                        <div class="flex justify-between">
                            <span class="ts-text-muted">{{ __('ui.common.total') }}</span>
                            <span class="font-bold text-red-600">{{ $user->total_coins }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Options -->
        <div class="lg:col-span-2">
            <div id="payment-not-configured-alert" class="hidden mb-6 ts-callout ts-callout--danger">
                <span class="font-semibold">{{ __('ui.payment.not_configured_title') }}</span>
                {{ __('ui.payment.not_configured_subtitle') }}
            </div>

            <!-- Subscriptions -->
            <div class="ts-surface ts-surface--pad mb-6">
                <h2 class="text-xl font-semibold mb-4" style="color: var(--ts-text-primary)">{{ __('ui.payment.membership.title') }}</h2>
                <div class="mb-4 ts-callout ts-callout--success">
                    {!! __('ui.payment.membership.subscription_terms_html') !!}
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Weekly Plan -->
                    <div class="relative">
                        <div class="ts-plan-card ts-plan-card--featured">
                            <div class="absolute top-2 right-2 bg-pink-600 text-white text-xs px-2 py-1 rounded font-semibold">
                                {{ __('ui.payment.membership.most_users_prefer') }}
                            </div>
                            <div class="flex items-center mb-4">
                                <svg class="w-6 h-6 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="font-semibold" style="color: var(--ts-text-primary)">{{ __('ui.payment.membership.badge') }}</span>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-baseline">
                                    <span class="text-3xl font-bold" style="color: var(--ts-text-primary)">{{ $paypalCurrency }} {{ number_format($prices['weekly'], 2) }}</span>
                                    <span class="ts-text-muted ml-2">{{ __('ui.payment.membership.per_week') }}</span>
                                </div>
                                <div class="text-sm ts-text-muted line-through">USD 19.99</div>
                            </div>
                            <p class="text-sm ts-text-secondary mb-4">{{ __('ui.payment.membership.weekly_desc') }}</p>
                            <p class="text-xs ts-text-muted mb-4">{{ __('ui.payment.membership.renewal_hint') }}</p>
                            <button type="button" data-paypal-kind="subscription" data-plan-type="weekly" class="js-paypal-cta ts-cta-accent w-full py-2.5 rounded-lg font-semibold transition">
                                {{ __('ui.payment.choose_offer') }}
                            </button>
                        </div>
                    </div>

                    <!-- Yearly Plan -->
                    <div class="relative">
                        <div class="ts-plan-card">
                            <div class="flex items-center mb-4">
                                <svg class="w-6 h-6 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="font-semibold" style="color: var(--ts-text-primary)">{{ __('ui.payment.membership.badge') }}</span>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-baseline">
                                    <span class="text-3xl font-bold" style="color: var(--ts-text-primary)">{{ $paypalCurrency }} {{ number_format($prices['yearly'], 2) }}</span>
                                    <span class="ts-text-muted ml-2">{{ __('ui.payment.membership.per_year') }}</span>
                                </div>
                            </div>
                            <p class="text-sm ts-text-secondary mb-4">{{ __('ui.payment.membership.yearly_desc') }}</p>
                            <p class="text-xs ts-text-muted mb-4">{{ __('ui.payment.membership.renewal_hint') }}</p>
                            <button type="button" data-paypal-kind="subscription" data-plan-type="yearly" class="js-paypal-cta ts-cta-accent w-full py-2.5 rounded-lg font-semibold transition">
                                {{ __('ui.payment.choose_offer') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coins Packages -->
            <div class="ts-surface ts-surface--pad">
                <h2 class="text-xl font-semibold mb-4" style="color: var(--ts-text-primary)">{{ __('ui.payment.coins.title') }}</h2>
                <div class="mb-4 ts-callout ts-callout--warn">
                    {!! __('ui.payment.coins.terms_html', ['days' => 7]) !!}
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($packages as $key => $package)
                    <div class="relative">
                        <div class="ts-plan-card text-center">
                            <div class="text-4xl mb-2" aria-hidden="true">🪙</div>
                            <div class="font-bold mb-1" style="color: var(--ts-text-primary)">{{ trans_choice('ui.payment.coins.coins_count', (int) $package['coins'], ['count' => (int) $package['coins']]) }}</div>
                            @if($package['reward'] > 0)
                            <div class="text-sm mb-2 font-semibold" style="color: var(--ts-success-text)">+{{ trans_choice('ui.payment.coins.reward_count', (int) $package['reward'], ['count' => (int) $package['reward']]) }}</div>
                            @endif
                            <div class="font-semibold text-lg mb-3" style="color: var(--ts-text-primary)">{{ $paypalCurrency }} {{ number_format($package['price'], 2) }}</div>
                            <button type="button" data-paypal-kind="coins" data-coin-package="{{ $key }}" class="js-paypal-cta ts-cta-accent w-full py-2 rounded-lg text-sm font-semibold transition">
                                {{ __('ui.payment.choose_offer') }}
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div id="payment-step-wrap" class="mt-6 ts-surface ts-surface--pad hidden" style="background: var(--ts-btn-ghost-bg)">
                    <h3 class="font-semibold text-lg mb-2" style="color: var(--ts-text-primary)">{{ __('ui.payment.payment_step_title') }}</h3>
                    <p class="text-sm ts-text-secondary mb-4">{!! __('ui.payment.pay_step_intro') !!}</p>
                    <div id="paypal-area" class="{{ $paypalEnabled ? '' : 'hidden' }}">
                        @if($paypalEnabled)
                            <div id="paypal-button-container" class="max-w-md"></div>
                            <p id="paypal-status" class="text-xs ts-text-muted mt-2"></p>
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
  let selection = null;

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
