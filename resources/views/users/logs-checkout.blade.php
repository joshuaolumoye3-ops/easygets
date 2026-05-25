@extends('layouts.app')

@section('title', 'Checkout Logs')

@section('content')
<div class="max-w-lg mx-auto py-10 px-4 sm:px-6 lg:px-8 font-[Inter]">
    <div class="bg-white rounded-2xl shadow-lg p-7 mb-8">
        <div class="flex items-center mb-4">
            <div class="bg-brand-green-light rounded-full w-12 h-12 flex items-center justify-center mr-4">
                <img src="/assets/sim-icon.svg" class="w-7 h-7 text-brand-green" alt="Logs">
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">Checkout Logs</h1>
                <p class="text-sm text-gray-500">Confirm your logs order below.</p>
            </div>
        </div>
        <div class="mb-6">
            <div class="flex items-center mb-2">
                <span class="inline-block bg-brand-green-light rounded-full px-3 py-1 text-xs font-semibold text-brand-green mr-2">{{ $product->country }}</span>
                <span class="text-xs text-gray-400">{{ $product->country_code }}</span>
            </div>
            <h2 class="font-bold text-gray-800 text-lg mb-1">{{ $product->name }}</h2>
            <p class="text-gray-600 text-sm mb-2">{{ $product->description }}</p>
            <div class="font-bold text-brand-green text-lg">₦{{ number_format($product->price, 2) }}</div>
        </div>
        <form action="{{ route('logs.process', $product->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="quantity" class="block text-xs font-semibold text-gray-600 mb-1">Quantity</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" max="10" class="w-full border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-green text-gray-800 font-semibold" required>
            </div>
            <button type="submit" class="w-full bg-brand-green hover:bg-green-700 text-white font-bold py-3 rounded-lg shadow-sm transition-colors">Confirm & Pay</button>
        </form>
        <a href="{{ route('logs') }}" class="block text-center text-xs text-gray-400 mt-4 hover:underline">Back to logs</a>
    </div>
</div>
@endsection
