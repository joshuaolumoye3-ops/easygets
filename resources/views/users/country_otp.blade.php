@extends('layouts.app')

@section('title', 'Countries')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-0 pt-0">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-4xl">

        <!-- Header -->
        <div class="relative flex items-center justify-center mb-6">
            <a href="dashboard" class="absolute left-0 text-gray-600 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">Countries</h1>
        </div>

        <!-- Search Bar -->
        <div class="relative mb-8">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input 
                type="text" 
                placeholder="Search 163 Countries..."
                class="w-full pl-10 pr-4 py-3 bg-gray-100 border border-transparent rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition duration-200"
            >
        </div>

        <!-- Popular Countries -->
        <div class="mb-8">
            <div class="flex space-x-4 overflow-x-auto pb-4 -mx-4 px-4">
                {{-- These could be dynamically generated from your controller --}}
                <a href="#" class="flex-shrink-0 w-16 h-16 flex items-center justify-center rounded-full hover:opacity-80 transition-opacity">
                    <img class="w-12 h-12 rounded-full object-cover" src="https://flagcdn.com/w160/us.png" alt="USA Flag">
                </a>
                <a href="#" class="flex-shrink-0 w-16 h-16 flex items-center justify-center rounded-full hover:opacity-80 transition-opacity">
                    <img class="w-12 h-12 rounded-full object-cover" src="https://flagcdn.com/w160/gb.png" alt="UK Flag">
                </a>
                <a href="#" class="flex-shrink-0 w-16 h-16 flex items-center justify-center rounded-full hover:opacity-80 transition-opacity">
                    <img class="w-12 h-12 rounded-full object-cover" src="https://flagcdn.com/w160/ca.png" alt="Canada Flag">
                </a>
                <a href="#" class="flex-shrink-0 w-16 h-16 flex items-center justify-center rounded-full hover:opacity-80 transition-opacity">
                    <img class="w-12 h-12 rounded-full object-cover" src="https://flagcdn.com/w160/de.png" alt="Germany Flag">
                </a>
                <a href="#" class="flex-shrink-0 w-16 h-16 flex items-center justify-center rounded-full hover:opacity-80 transition-opacity">
                    <img class="w-12 h-12 rounded-full object-cover" src="https://flagcdn.com/w160/fr.png" alt="France Flag">
                </a>
                <a href="#" class="flex-shrink-0 w-16 h-16 flex items-center justify-center rounded-full hover:opacity-80 transition-opacity">
                    <img class="w-12 h-12 rounded-full object-cover" src="https://flagcdn.com/w160/in.png" alt="India Flag">
                </a>
                 <a href="#" class="flex-shrink-0 w-16 h-16 flex items-center justify-center rounded-full hover:opacity-80 transition-opacity">
                    <img class="w-12 h-12 rounded-full object-cover" src="https://flagcdn.com/w160/au.png" alt="Australia Flag">
                </a>
            </div>
        </div>

        <!-- All Services List -->
        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4">All Services</h2>
            <div class="space-y-2">
                {{-- You would loop through your $countries collection here --}}
                @php
                    $countries = [
                        ['code' => 'tl', 'name' => 'East Timor', 'count' => '25,000'],
                        ['code' => 'do', 'name' => 'Dominican Republic', 'count' => '13,991'],
                        ['code' => 'tj', 'name' => 'Tajikistan', 'count' => '29,133'],
                        ['code' => 'in', 'name' => 'India', 'count' => '31,878'],
                        ['code' => 'ao', 'name' => 'Angola', 'count' => '5,733'],
                        ['code' => 'pr', 'name' => 'Puerto Rico', 'count' => '5,083'],
                    ];
                @endphp

                @foreach ($countries as $country)
                <a href="#" class="flex items-center p-4 bg-white rounded-lg hover:bg-gray-50 transition-colors duration-200">
                    <img class="w-10 h-10 rounded-full mr-4 object-cover" src="https://flagcdn.com/w160/{{ $country['code'] }}.png" alt="{{ $country['name'] }} Flag">
                    <span class="font-semibold text-gray-700">{{ $country['name'] }}</span>
                    <div class="ml-auto flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600 mr-2" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.518.759a1.156 1.156 0 00-.432 1.636 14.942 14.942 0 007.85 7.85 1.156 1.156 0 001.636-.432l.758-1.518a1 1 0 011.06-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        <span class="font-bold text-green-600">{{ $country['count'] }}</span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
