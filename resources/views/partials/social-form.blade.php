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

        <form>
            <div class="mb-4">
                <label for="category" class="block text-gray-700 font-semibold mb-1">Select Category</label>
                <select id="category" name="category" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option>Anyoption Promo (Cheapest and fastest)</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="package" class="block text-gray-700 font-semibold mb-1">Choose Package</label>
                <select id="package" name="package" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option>Instagram Followers</option>
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

            <div class="mb-4 text-center">
                <div class="bg-brand-green text-white font-bold rounded-xl py-4 mb-2">
                    <p class="text-lg">Amount</p>
                    <p class="text-2xl">₦2,083</p>
                    <p class="text-sm">Per 10</p>
                </div>
                <div class="bg-green-100 text-brand-green rounded-xl py-3 px-4 text-sm">
                    <p class="font-semibold">Description</p>
                    <p>You will be charged ₦2,083 for Instagram followers boost. You can continue to place your order.</p>
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
