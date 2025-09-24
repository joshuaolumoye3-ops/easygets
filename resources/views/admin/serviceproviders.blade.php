@extends('layouts.admin') 
@section('title', 'Manage Providers')

@section('content')
<main class="pt-[70px] w-full flex flex-col">
    <div class="p-[1.875rem_1.75rem] w-full flex-grow">
        <h1 class="text-xl font-bold text-white mb-6">Providers for {{ $service->name }}</h1>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($providers as $provider)
                <div 
                    class="bg-[#191c24] text-white p-6 rounded-xl shadow hover:bg-[#23272f] cursor-pointer transition"
                    onclick="window.location='{{ route('admin.products', $provider->id) }}'">
                    <h2 class="text-xl font-bold mb-2">{{ $provider->name }}</h2>
                    <p class="text-muted">Click to manage {{ $provider->name }} plans</p>
                </div>
            @endforeach
        </div>
    </div>
</main>
@endsection
