@extends('layouts.app')

{{-- You can set a unique title for each page --}}
@section('title', 'Dashboard')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-10 font-sans">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-center mb-6">Social Packages</h2>

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
                <div class="bg-green-800 text-white font-bold rounded-xl py-4 mb-2">
                    <p class="text-lg">Amount</p>
                    <p class="text-2xl">₦2,083</p>
                    <p class="text-sm">Per 10</p>
                </div>
                <div class="bg-green-100 text-green-800 rounded-xl py-3 px-4 text-sm">
                    <p class="font-semibold">Description</p>
                    <p>You will be charged ₦2,083 for Instagram followers boost. You can continue to place your order.</p>
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-green hover:bg-green-800 text-white font-semibold py-3 rounded-xl transition duration-300">Continue</button>
        </form>
    </div>
</div>
@endsection
