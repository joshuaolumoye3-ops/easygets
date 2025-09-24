@extends('layouts.admin') 
@section('title', 'Manage Services')

@section('content')
<main class="pt-[70px] w-full flex flex-col">
    <div class="p-[1.875rem_1.75rem] w-full flex-grow">
        
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-[#191c24] p-4 rounded-2xl shadow-md mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-white">
                Manage Services
            </h1>
        </div>

        <!-- Services List -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($services as $service)
                <div 
                    class="bg-[#191c24] text-white p-6 rounded-xl shadow hover:bg-[#23272f] cursor-pointer transition"
                    onclick="window.location='{{ route('admin.providers', $service->id) }}'">
                    <h2 class="text-xl font-bold mb-2">{{ $service->name }}</h2>
                    <p class="text-muted">Click to manage {{ $service->name }} providers</p>
                </div>
            @endforeach
        </div>
    </div>
</main>
@endsection
