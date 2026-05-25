@forelse($products as $product)
<div class="border rounded-xl p-4 flex flex-col justify-between shadow-sm bg-gray-50 hover:shadow-md transition group">
    <div>
        <div class="flex items-center mb-2">
            <span class="inline-block bg-brand-green-light rounded-full px-3 py-1 text-xs font-semibold text-brand-green mr-2">{{ $product->country }}</span>
            <span class="text-xs text-gray-400">{{ $product->country_code }}</span>
        </div>
        <h2 class="font-bold text-gray-800 text-lg mb-1">{{ $product->name }}</h2>
        <p class="text-gray-600 text-sm mb-2">{{ Str::limit($product->description, 80) }}</p>
    </div>
    <div class="flex items-center justify-between mt-2">
        <span class="font-bold text-brand-green text-lg">₦{{ number_format($product->price, 2) }}</span>
        <a href="{{ route('logs.checkout', $product->id) }}" class="bg-brand-green hover:bg-green-700 text-white font-bold py-2 px-5 rounded-lg shadow-sm transition-colors">Buy Now</a>
    </div>
</div>
@empty
<div class="col-span-2 text-center py-12">
    <img src="/assets/empty.svg" class="mx-auto mb-4 w-20 h-20 opacity-60" alt="No logs">
    <h3 class="text-lg font-semibold text-gray-600 mb-2">No logs found</h3>
    <p class="text-gray-400">Try adjusting your search criteria.</p>
</div>
@endforelse