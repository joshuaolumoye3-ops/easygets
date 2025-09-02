@extends('layouts.app')

@section('title', 'Social Packages')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-10 font-sans">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-center mb-6">Social Packages</h2>

        <form method="POST" action="{{ route('social.order') }}">
            @csrf

            {{-- Category Dropdown --}}
            <div class="mb-4">
                <label for="category" class="block text-gray-700 font-semibold mb-1">Select Category</label>
                <select id="category" name="category" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">-- Choose Category --</option>
                    @foreach($categories as $category => $services)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Package Dropdown --}}
            <div class="mb-4">
                <label for="service_id" class="block text-gray-700 font-semibold mb-1">Choose Package</label>
                <select id="service_id" name="service_id" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">-- Choose Package --</option>
                    @foreach($categories as $category => $services)
                        @foreach($services as $service)
                            <option value="{{ $service->service }}" data-category="{{ $category }}" data-rate="{{ $service->rate }}" data-min="{{ $service->min }}" data-max="{{ $service->max }}" data-desc="{{ $service->desc }}">
                                {{ $service->name }} (₦{{ number_format($service->rate, 2) }}/1k)
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            {{-- Quantity --}}
            <div class="mb-4">
                <label for="quantity" class="block text-gray-700 font-semibold mb-1">Quantity</label>
                <input type="number" id="quantity" name="quantity" placeholder="10" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            {{-- Link --}}
            <div class="mb-6">
                <label for="link" class="block text-gray-700 font-semibold mb-1">Post/Page Link</label>
                <input type="url" id="link" name="link" placeholder="https://example.com/link" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            {{-- Dynamic Price & Description --}}
            <div class="mb-4 text-center">
                <div id="price-box" class="bg-green-800 text-white font-bold rounded-xl py-4 mb-2 hidden">
                    <p class="text-lg">Amount</p>
                    <p class="text-2xl" id="amount">₦0</p>
                    <p class="text-sm" id="per-qty">Per 1000</p>
                </div>
                <div id="desc-box" class="bg-green-100 text-green-800 rounded-xl py-3 px-4 text-sm hidden">
                    <p class="font-semibold">Description</p>
                    <p id="service-desc"></p>
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-green hover:bg-green-800 text-white font-semibold py-3 rounded-xl transition duration-300">Continue</button>
        </form>
    </div>
</div>

<script>
    const serviceSelect = document.getElementById('service_id');
    const qtyInput = document.getElementById('quantity');
    const amountBox = document.getElementById('amount');
    const perQtyBox = document.getElementById('per-qty');
    const descBox = document.getElementById('service-desc');
    const priceBox = document.getElementById('price-box');
    const descContainer = document.getElementById('desc-box');

    function updateP
