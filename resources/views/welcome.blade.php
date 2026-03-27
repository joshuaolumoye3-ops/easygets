@extends('layouts.app')

{{-- You can set a unique title for each page --}}
@section('title', 'Dashboard')

@section('content')
<div class="max-w-md mx-auto bg-white pb-28">

    <header class="p-4 flex items-center space-x-3">
        <img src="https://placehold.co/48x48/E6F5F1/0D5443?text=A" alt="User Avatar" class="w-12 h-12 rounded-full">
        <div>
            <p class="text-sm text-gray-500">Welcome Back,</p>
            <h1 class="font-bold text-lg text-gray-800">Adeleke Adebisi</h1>
        </div>
    </header>

    <div class="px-4">
        <div class="bg-brand-green text-white rounded-xl p-5 shadow-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm opacity-80">Wallet Balance</p>
                    <p class="text-3xl font-bold mt-1">₦50,000<span class="text-lg font-medium">.00</span></p>
                </div>
                <a href="#" class="text-xs font-semibold opacity-90">Transactions ></a>
            </div>
            <div class="mt-4">
                <button class="w-full bg-white text-brand-green font-bold py-3 rounded-lg shadow-md hover:bg-gray-100 transition-colors">
                    + Add Funds
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 my-5">
            <button class="bg-gray-800 text-white font-bold py-3 rounded-lg shadow hover:bg-gray-700 transition-colors">Get OTP</button>
            <button class="bg-gray-200 text-gray-800 font-bold py-3 rounded-lg shadow hover:bg-gray-300 transition-colors">Bookie</button>
        </div>

        <div>
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold text-gray-800">Quick Pick</h2>
                <a href="#" class="text-sm font-semibold text-brand-green">See All</a>
            </div>
            <div class="grid grid-cols-4 gap-4 text-center">
                <a href="#" class="flex flex-col items-center space-y-1">
                    <div class="w-14 h-14 bg-blue-600 rounded-full flex items-center justify-center text-white">
                       <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0 0 3.583 0 8.049c0 4.021 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.93 6.75-7.951z"/></svg>
                    </div>
                    <span class="text-xs text-gray-600">Facebook</span>
                </a>
                <a href="#" class="flex flex-col items-center space-y-1">
                    <div class="w-14 h-14 bg-green-500 rounded-full flex items-center justify-center text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.1-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943c-.049-.084-.182-.133-.38-.232z"/></svg>
                    </div>
                    <span class="text-xs text-gray-600">WhatsApp</span>
                </a>
                </div>
        </div>

        <div class="mt-8">
             <div class="grid grid-cols-4 gap-x-4 gap-y-6 text-center">
                <a href="#" class="space-y-2">
                    <div class="bg-brand-green-light rounded-lg w-full aspect-square flex items-center justify-center">
                        <svg class="w-7 h-7 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-xs font-medium text-gray-700">Internet</span>
                </a>
                 </div>
        </div>

        <div class="mt-8">
            <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold text-gray-800">Transactions</h2>
                <a href="#" class="text-sm font-semibold text-brand-green">View more +</a>
            </div>
            <div class="space-y-4">
                <div class="flex items-center space-x-4">
                    <div class="bg-brand-green-light rounded-full w-12 h-12 flex-shrink-0 flex items-center justify-center">
                         <svg class="w-6 h-6 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="flex-grow">
                        <p class="font-semibold text-gray-800">Airtel Ng Ntw 2041...</p>
                        <p class="text-xs text-gray-500">11:35 AM | 20 Jul, 2020</p>
                    </div>
                    <div class="font-bold text-gray-800">₦ 500</div>
                </div>
                 </div>
        </div>
    </div>
</div>
@endsection
