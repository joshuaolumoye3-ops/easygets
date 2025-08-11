@extends('layouts.app')

@section('title', 'History')

@section('content')
@php
$activations = [
    [
        'flag' => 'us',
        'number' => '+1 (555) 123-4567',
        'date' => '19 July, 1:25 AM',
        'amount' => '₦2,345',
        'status' => 'Activated',
        'status_color' => 'green-600',
    ],
    [
        'flag' => 'gb',
        'number' => '+1 (555) 123-4567',
        'date' => '19 July, 1:25 AM',
        'amount' => '₦5,345',
        'status' => 'Pending',
        'status_color' => 'yellow-500',
    ],
    [
        'flag' => 'ng',
        'number' => '+1 (555) 123-4567',
        'date' => '19 July, 1:25 AM',
        'amount' => '₦1,345',
        'status' => 'Canceled',
        'status_color' => 'red-600',
    ],
];
$socials = [
    [
        'flag' => 'mx',
        'number' => '+1 (555) 765-4321',
        'date' => '20 July, 3:00 PM',
        'amount' => '₦3,345',
        'status' => 'Activated',
        'status_color' => 'green-600',
    ],
    [
        'flag' => 'br',
        'number' => '+1 (555) 765-4321',
        'date' => '20 July, 3:00 PM',
        'amount' => '₦5,345',
        'status' => 'Activated',
        'status_color' => 'green-600',
    ],
];
@endphp

<div class="max-w-md mx-auto py-6">
    <!-- Header -->
    <div class="relative flex items-center px-4 mb-4 h-12">
        <button class="focus:outline-none z-10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 hover:text-green-700" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.707 4.293a1 1 0 00-1.414 0L5.586 10l5.707 5.707a1 1 0 001.414-1.414L8.414 10l4.293-4.293a1 1 0 000-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <h1 class="absolute inset-0 flex justify-center items-center text-lg font-semibold">History</h1>
    </div>

    <!-- Tabs -->
    <div class="flex justify-between px-4 mb-6">
        <button id="activationsBtn" class="flex-1 text-center py-3 rounded-full font-semibold bg-green-800 text-white mr-2">Activations</button>
        <button id="socialsBtn" class="flex-1 text-center py-3 rounded-full font-semibold bg-black text-white">Socials</button>
    </div>

    <!-- Activations Content -->
    <div id="activationsContent" class="space-y-5 px-4 transition-all duration-500 ease-in-out">
        @foreach($activations as $index => $item)
        <div onclick="openModal('activationModal{{ $index }}')" class="cursor-pointer flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="https://flagcdn.com/w40/{{ $item['flag'] }}.png" alt="flag" class="w-8 h-8 rounded-full object-cover">
                <div>
                    <p class="font-semibold text-gray-800">{{ $item['number'] }}</p>
                    <p class="text-sm text-gray-500">{{ $item['date'] }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="font-semibold text-gray-800">{{ $item['amount'] }}</p>
                <p class="text-xs text-{{ $item['status_color'] }} font-semibold">{{ $item['status'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Socials Content -->
    <div id="socialsContent" class="space-y-5 px-4 hidden transition-all duration-500 ease-in-out">
        @foreach($socials as $index => $item)
        <div onclick="openModal('socialModal{{ $index }}')" class="cursor-pointer flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="https://flagcdn.com/w40/{{ $item['flag'] }}.png" alt="flag" class="w-8 h-8 rounded-full object-cover">
                <div>
                    <p class="font-semibold text-gray-800">{{ $item['number'] }}</p>
                    <p class="text-sm text-gray-500">{{ $item['date'] }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="font-semibold text-gray-800">{{ $item['amount'] }}</p>
                <p class="text-xs text-{{ $item['status_color'] }} font-semibold">{{ $item['status'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Activation Modals -->
@foreach($activations as $index => $item)
<div id="activationModal{{ $index }}" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center hidden">
    <div class="bg-white rounded-2xl w-[90%] max-w-md p-6">
        @include('partials.activation-popup')
    </div>
</div>
@endforeach

<!-- Social Modals -->
@foreach($socials as $index => $item)
<div id="socialModal{{ $index }}" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center hidden">
    <div class="bg-white rounded-2xl w-[90%] max-w-md p-6">
        @include('partials.social-popup')
    </div>
</div>
@endforeach

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}
</script>


@include('partials.footer')

<script>
document.addEventListener('DOMContentLoaded', () => {
    const activationsBtn = document.getElementById('activationsBtn');
    const socialsBtn = document.getElementById('socialsBtn');
    const activationsContent = document.getElementById('activationsContent');
    const socialsContent = document.getElementById('socialsContent');

    socialsBtn.addEventListener('click', () => {
        activationsContent.classList.add('hidden');
        socialsContent.classList.remove('hidden');
        socialsBtn.classList.replace('bg-black', 'bg-green-800');
        activationsBtn.classList.replace('bg-green-800', 'bg-black');
    });

    activationsBtn.addEventListener('click', () => {
        socialsContent.classList.add('hidden');
        activationsContent.classList.remove('hidden');
        activationsBtn.classList.replace('bg-black', 'bg-green-800');
        socialsBtn.classList.replace('bg-green-800', 'bg-black');
    });
});
</script>
@endsection
