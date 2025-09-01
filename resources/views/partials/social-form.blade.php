<!-- Social Form Modal -->
<div id="socialModal" class="fixed inset-0 z-50 bg-white w-full h-full overflow-y-auto hidden animate-slide-up">
    <div class="w-full max-w-md mx-auto p-4 sm:p-6 relative">
        <!-- Back Arrow Button -->
        <button onclick="closeSocialModal()" class="absolute left-4 top-4 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-800 hover:text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <h2 class="text-2xl font-bold text-center mt-12 mb-6">Social Packages</h2>

        <form method="POST" action="{{ route('social.order') }}">
            @csrf

            <div class="mb-4">
                <label for="category" class="block text-gray-700 font-semibold mb-1">Select Category</label>
                <select id="category" name="category" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    @foreach($categories as $category => $services)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="package" class="block text-gray-700 font-semibold mb-1">Choose Package</label>
                <select id="package" name="service_id" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                   @foreach($categories as $category => $services)
                        @foreach($services as $service)
                            <option value="{{ $service->id }}"
                                data-category="{{ $category }}"
                                data-rate="{{ $service->rate }}"
                                data-min="{{ $service->min }}"
                                data-max="{{ $service->max }}"
                                data-desc="{{ $service->desc }}">
                                {{ $service->name }} (₦{{ number_format($service->rate, 2) }}/1k)
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="quantity" class="block text-gray-700 font-semibold mb-1">Quantity</label>
                <input type="number" id="quantity" name="quantity" placeholder="10" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-6">
                <label for="link" class="block text-gray-700 font-semibold mb-1">Post/Page Link</label>
                <input type="url" id="link" name="link" placeholder="https://example.com/link" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <!-- Dynamic Amount Box -->
            <div class="mb-4 text-center">
                <div class="bg-brand-green text-white font-bold rounded-xl py-4 mb-2">
                    <p class="text-lg">Amount</p>
                    <p id="amountDisplay" class="text-2xl">₦0</p>
                    <p id="perUnitDisplay" class="text-sm"></p>
                </div>
                <div class="bg-green-100 text-brand-green rounded-xl py-3 px-4 text-sm">
                    <p class="font-semibold">Description</p>
                    <p id="descDisplay">Select a package to see details.</p>
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-green hover:bg-green-800 text-white font-semibold py-3 rounded-xl transition duration-300">
                Continue
            </button>
        </form>
    </div>
</div>

<style>
    .animate-slide-up {
        animation: slideUp .3s ease-out;
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const packageSelect = document.getElementById("package");
    const qtyInput = document.getElementById("quantity");
    const amountDisplay = document.getElementById("amountDisplay");
    const perUnitDisplay = document.getElementById("perUnitDisplay");
    const descDisplay = document.getElementById("descDisplay");

    function calculateAmount() {
        const selected = packageSelect.options[packageSelect.selectedIndex];
        const rate = parseFloat(selected.dataset.rate || 0);
        const min = parseInt(selected.dataset.min || 0);
        const max = parseInt(selected.dataset.max || 0);
        const desc = selected.dataset.desc || "No description available.";
        const qty = parseInt(qtyInput.value || 0);

        if (qty >= min && qty <= max) {
            // Formula: (rate per 1000) * qty / 1000 + 5% markup
            const baseAmount = (rate * qty) / 1000;
            const amount = baseAmount * 1.05; // adds 5%

            amountDisplay.textContent = "₦" + amount.toFixed(2);
            perUnitDisplay.textContent = `Min: ${min}, Max: ${max}`;
            descDisplay.textContent = desc + ` (Includes 5% fee)`;
        } else {
            amountDisplay.textContent = "₦0";
            perUnitDisplay.textContent = `Enter between ${min} and ${max}`;
            descDisplay.textContent = desc;
        }
    }

    packageSelect.addEventListener("change", calculateAmount);
    qtyInput.addEventListener("input", calculateAmount);

    // Initialize
    calculateAmount();
});
</script>

