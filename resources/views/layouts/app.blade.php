<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('easygets-fav-black.png') }}" />

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'brand-green': {
                            'DEFAULT': '#03624C',
                            'light': '#E6F5F1',
                            'medium': '#157A61',
                        },
                        'brand-gray': {
                            'light': '#F9FAFB',
                            'medium': '#E0E0E0',
                            'dark': '#4F4F4F'
                        }
                    }
                }
            }
        }
    </script>
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
        @if(!empty($activeAnnouncement))
            <div class="bg-green-50 border border-green-200 text-green-900 rounded-2xl mx-auto mt-4 mb-6 px-4 py-4 shadow-sm max-w-6xl w-full mx-4 sm:mx-auto">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-green-700 font-semibold mb-1">Update</p>
                        <h2 class="text-lg font-semibold text-green-900">{{ $activeAnnouncement->title }}</h2>
                        <p class="text-sm text-green-800 mt-1">{{ $activeAnnouncement->body }}</p>
                    </div>
                    <div class="text-right text-xs text-green-700">
                        @if($activeAnnouncement->starts_at)
                            <div>Starts: {{ $activeAnnouncement->starts_at->format('M d, Y H:i') }}</div>
                        @endif
                        @if($activeAnnouncement->ends_at)
                            <div>Ends: {{ $activeAnnouncement->ends_at->format('M d, Y H:i') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($activeAnnouncement))
            <div id="userAnnouncementModal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/90 px-4 py-6">
                <div class="relative w-full max-w-3xl max-h-[85vh] overflow-hidden rounded-[32px] shadow-2xl border border-slate-800/70 bg-slate-900 text-white">
                    <div class="bg-gradient-to-r from-sky-500 via-emerald-500 to-teal-500 px-6 py-6 shadow-lg">
                        <div class="flex items-start justify-between gap-4">
                            <div class="max-w-[calc(100%-4rem)]">
                                <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-[11px] uppercase tracking-[0.35em] text-sky-100 font-semibold">Announcement</span>
                                <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold leading-tight">{{ $activeAnnouncement->title }}</h2>
                                <p class="mt-3 text-sm sm:text-base text-slate-100/90">Important update for your account and services. This announcement is active and visible only while it is current.</p>
                            </div>
                            <button id="closeAnnouncementModal" class="text-white text-4xl leading-none opacity-90 hover:opacity-100 transition" aria-label="Close announcement">×</button>
                        </div>
                    </div>
                    <div class="p-6 sm:p-8 space-y-6 overflow-y-auto max-h-[70vh] bg-white text-slate-800">
                        <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                            <p class="text-base leading-8">{{ $activeAnnouncement->body }}</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @if($activeAnnouncement->starts_at)
                                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-[0.24em] text-slate-500 font-semibold">Starts</p>
                                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ $activeAnnouncement->starts_at->format('M d, Y H:i') }}</p>
                                </div>
                            @endif
                            @if($activeAnnouncement->ends_at)
                                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <p class="text-[11px] uppercase tracking-[0.24em] text-slate-500 font-semibold">Ends</p>
                                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ $activeAnnouncement->ends_at->format('M d, Y H:i') }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <button id="dismissAnnouncementModal" class="w-full sm:w-auto rounded-full border border-slate-300 bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Dismiss</button>
                            <a href="{{ route('history') }}" class="w-full sm:w-auto rounded-full bg-gradient-to-r from-emerald-600 to-teal-500 px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:from-emerald-700 hover:to-teal-600 text-center">View activity</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <main class="flex-grow">
            @yield('content')
        </main>
    </div>

    @stack('scripts')

    @if(!empty($activeAnnouncement))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('userAnnouncementModal');
                const closeBtn = document.getElementById('closeAnnouncementModal');
                const dismissBtn = document.getElementById('dismissAnnouncementModal');

                if (!modal) return;

                function hideModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';

                if (closeBtn) {
                    closeBtn.addEventListener('click', hideModal);
                }
                if (dismissBtn) {
                    dismissBtn.addEventListener('click', hideModal);
                }
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        hideModal();
                    }
                });
            });
        </script>
    @endif
</body>
</html>