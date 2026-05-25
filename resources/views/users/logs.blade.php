@extends('layouts.app')

@section('title', 'Buy Logs')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-[Inter]">
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
        <div class="flex items-center mb-4">
            <div class="bg-brand-green-light rounded-full w-12 h-12 flex items-center justify-center mr-4">
                <img src="/assets/sim-icon.svg" class="w-7 h-7 text-brand-green" alt="Logs">
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Buy Logs</h1>
                <p class="text-sm text-gray-500">Get premium logs for your needs. Fast, reliable, and secure delivery.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
            @forelse($products as $product)
            <div class="border rounded-xl p-4 flex flex-col justify-between shadow-sm bg-gray-50 hover:shadow-md transition group">
                <div>
                    <div class="flex items-center mb-2">
                        <span class="inline-block bg-brand-green-light rounded-full px-3 py-1 text-xs font-semibold text-brand-green mr-2">{{ $product->country }}</span>
                        <span class="text-xs text-gray-400">{{ $product->country_code }}</span>
                    </div>
                    <h2 class="font-bold text-gray-800 text-lg mb-1">{{ $product->name }}</h2>
                    <p class="text-gray-600 text-sm mb-2">{{ $product->description }}</p>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <span class="font-bold text-brand-green text-lg">₦{{ number_format($product->price, 2) }}</span>
                    <form action="{{ route('logs.checkout', $product->id) }}" method="GET">
                        <button type="submit" class="bg-brand-green hover:bg-green-700 text-white font-bold py-2 px-5 rounded-lg shadow-sm transition-colors">Buy Now</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="col-span-2 text-center py-12">
                <img src="/assets/empty.svg" class="mx-auto mb-4 w-20 h-20 opacity-60" alt="No logs">
                <h3 class="text-lg font-semibold text-gray-600 mb-2">No logs available</h3>
                <p class="text-gray-400">Please check back later for new logs products.</p>
            </div>
            @endforelse
        </div>
    </div>
    <div class="text-center text-xs text-gray-400">Powered by HecksTechie &mdash; Secure logs delivery</div>
</div>
@endsection
