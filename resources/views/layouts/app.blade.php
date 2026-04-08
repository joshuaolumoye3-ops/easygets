<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('easygets-fav-black.png') }}" />

    <style>
        body {
            font-family: 'Inter', 'sans-serif';
        }
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="antialiased bg-white">

    {{-- Flash Message Popup --}}
    @if(session('success') || session('error') || session('info') || $errors->any())
        <div id="flash-message" 
            class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50 px-4 py-3 rounded-lg shadow-lg 
            {{ session('success') ? 'bg-green-500' : '' }}
            {{ session('error') ? 'bg-red-500' : '' }}
            {{ session('info') ? 'bg-blue-500' : '' }}
            {{ $errors->any() ? 'bg-red-500' : '' }}
            text-white text-center min-w-[300px] max-w-[500px]">
            
            @if(session('success'))
                {{ session('success') }}
            @elseif(session('error'))
                {{ session('error') }}
            @elseif(session('info'))
                {{ session('info') }}
            @elseif($errors->any())
                {{ $errors->first() }}
            @endif
        </div>

        <script>
            setTimeout(() => {
                const flash = document.getElementById('flash-message');
                if(flash){
                    flash.classList.add('opacity-0', 'transition', 'duration-700');
                    setTimeout(() => flash.remove(), 700);
                }
            }, 5000); // Disappear after 5 seconds
        </script>
    @endif

    <div id="app" class="min-h-screen flex flex-col">
        <main class="flex-grow">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>