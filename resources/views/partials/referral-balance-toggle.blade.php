{{--
    REUSABLE REFERRAL BALANCE TOGGLE
    ─────────────────────────────────
    Include on any purchase page BEFORE the submit button:

        @include('partials.referral-balance-toggle', [
            'referralBalance' => auth()->user()->referral_balance ?? 0,
            'isPartner'       => auth()->user()->is_partner       ?? false,
        ])

    JavaScript API (global — works across all pages):
    ──────────────────────────────────────────────────
    ReferralToggle.getSummary(totalCost)
        → { useReferral, referralDeduction, walletDeduction }

    ReferralToggle.updateHint(totalCost)
        → updates the inline split hint text under the toggle

    ReferralToggle.isOn()
        → boolean — whether the user has toggled it on

    In your fetch FormData before sending:
        formData.append('use_referral_balance', ReferralToggle.isOn() ? '1' : '0');
--}}

@php
    $refBal    = round((float) ($referralBalance ?? 0), 2);
    $isPartner = (bool) ($isPartner ?? false);
    $show      = !$isPartner && $refBal > 0;
@endphp

@if($show)
<div id="referralToggleWrapper"
     class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 mb-4 transition-all duration-200">

    <div class="flex items-center justify-between gap-3">

        {{-- Left: icon + label --}}
        <div class="flex items-start gap-2 flex-1 min-w-0">
            <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v1m0 6v1m-3.5-4.5a3.5 3.5 0 107 0"/>
            </svg>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-green-800 leading-tight">Use Referral Balance</p>
                <p class="text-xs text-green-600 mt-0.5">
                    Available: <span class="font-bold">₦{{ number_format($refBal, 2) }}</span>
                </p>
                <p id="referralSplitHint" class="text-xs text-gray-500 mt-1 hidden"></p>
            </div>
        </div>

        {{-- Right: toggle switch --}}
        <button type="button"
                id="referralToggleBtn"
                role="switch"
                aria-checked="false"
                aria-label="Toggle referral balance"
                class="relative flex-shrink-0 w-12 h-6 rounded-full
                       bg-gray-300 transition-colors duration-200
                       focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1">
            <span id="referralToggleThumb"
                  class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow
                         transform transition-transform duration-200">
            </span>
        </button>
    </div>
</div>
@endif

{{-- Always present so FormData reads it regardless of eligibility --}}
<input type="hidden" id="useReferralBalanceInput" name="use_referral_balance" value="0">

<script>
(function () {
    var _on          = false;
    var _refBal      = {{ $show ? $refBal : 0 }};
    var _available   = {{ $show ? 'true' : 'false' }};

    function render() {
        var btn   = document.getElementById('referralToggleBtn');
        var thumb = document.getElementById('referralToggleThumb');
        var input = document.getElementById('useReferralBalanceInput');

        if (!btn) return;

        if (_on) {
            btn.classList.remove('bg-gray-300');
            btn.classList.add('bg-green-500');
            btn.setAttribute('aria-checked', 'true');
            thumb.style.transform = 'translateX(24px)';
            if (input) input.value = '1';
        } else {
            btn.classList.remove('bg-green-500');
            btn.classList.add('bg-gray-300');
            btn.setAttribute('aria-checked', 'false');
            thumb.style.transform = 'translateX(0)';
            if (input) input.value = '0';
        }
    }

    window.ReferralToggle = {

        isOn: function () {
            return _on && _available;
        },

        toggle: function () {
            if (!_available) return;
            _on = !_on;
            render();
            // Fire a custom event so pages can react (e.g. update hint)
            document.dispatchEvent(new CustomEvent('referralToggleChanged', { detail: { on: _on } }));
        },

        /**
         * Calculate split for a given total cost.
         * Returns { useReferral, referralDeduction, walletDeduction }
         */
        getSummary: function (totalCost) {
            totalCost = parseFloat(totalCost) || 0;

            if (!this.isOn() || totalCost <= 0) {
                return {
                    useReferral:       false,
                    referralDeduction: 0,
                    walletDeduction:   totalCost,
                };
            }

            var refDeduct    = parseFloat(Math.min(_refBal, totalCost).toFixed(2));
            var walletDeduct = parseFloat((totalCost - refDeduct).toFixed(2));

            return {
                useReferral:       true,
                referralDeduction: refDeduct,
                walletDeduction:   walletDeduct,
            };
        },

        /**
         * Update the inline split hint below the toggle label.
         * Call whenever the purchase amount changes.
         */
        updateHint: function (totalCost) {
            var hint = document.getElementById('referralSplitHint');
            if (!hint) return;

            if (!_on || !_available || !(parseFloat(totalCost) > 0)) {
                hint.textContent = '';
                hint.classList.add('hidden');
                return;
            }

            var s     = this.getSummary(totalCost);
            var parts = [];

            if (s.referralDeduction > 0) {
                parts.push('₦' + s.referralDeduction.toLocaleString('en-NG', { minimumFractionDigits: 2 }) + ' from referral');
            }
            if (s.walletDeduction > 0) {
                parts.push('₦' + s.walletDeduction.toLocaleString('en-NG', { minimumFractionDigits: 2 }) + ' from wallet');
            }

            hint.textContent = parts.join(' + ');
            hint.classList.remove('hidden');
        },

        /**
         * Build a formatted HTML breakdown string for use inside PIN modal summaries.
         * Returns an empty string when referral is not being used.
         */
        getSummaryHtml: function (totalCost) {
            var s = this.getSummary(totalCost);
            if (!s.useReferral) return '';

            var html = '';
            if (s.referralDeduction > 0) {
                html += '<div class="flex justify-between text-green-700">'
                      + '<span class="font-medium">Referral covers:</span>'
                      + '<span>-₦' + s.referralDeduction.toLocaleString('en-NG', { minimumFractionDigits: 2 }) + '</span>'
                      + '</div>';
            }
            if (s.walletDeduction > 0) {
                html += '<div class="flex justify-between">'
                      + '<span class="font-medium">From wallet:</span>'
                      + '<span>₦' + s.walletDeduction.toLocaleString('en-NG', { minimumFractionDigits: 2 }) + '</span>'
                      + '</div>';
            }
            return html;
        },
    };

    // Wire up the button click
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('referralToggleBtn');
        if (btn) {
            btn.addEventListener('click', function () {
                window.ReferralToggle.toggle();
            });
        }
    });
})();
</script>