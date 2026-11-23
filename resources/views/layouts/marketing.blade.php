<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EasyGets — Buy & Sell Gift Cards, Pay Bills, Stay Connected')</title>
    <meta name="description" content="@yield('description', 'Buy and sell gift cards at live rates, rent verification numbers, and handle airtime, data, electricity and more — fast, secure, and effortless.')">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: { DEFAULT: '#03624C', dark: '#021f18', light: '#e6f5f1' }, ink: '#06110d' },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Sora', '"Plus Jakarta Sans"', 'ui-sans-serif', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; -webkit-font-smoothing: antialiased; }
        h1, h2, h3 { letter-spacing: -0.02em; }
        [data-reveal] { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s ease; }
        [data-reveal].in { opacity: 1; transform: none; }
        [data-reveal="scale"] { transform: scale(.95); }
        [data-reveal="left"] { transform: translateX(-28px); }
        [data-reveal="right"] { transform: translateX(28px); }
        .stagger > * { transition-delay: calc(var(--i, 0) * 90ms); }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        .float { animation: float 6s ease-in-out infinite; }
        @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .marquee-track { animation: marquee 32s linear infinite; width: max-content; }
        .marquee:hover .marquee-track { animation-play-state: paused; }
        .nav-link { position: relative; }
        .nav-link::after { content:''; position:absolute; left:0; bottom:-6px; width:0; height:2px; background:#03624C; transition:width .25s ease; }
        .nav-link:hover::after, .nav-link.active::after { width:100%; }
        .dot-grid { background-image: radial-gradient(rgba(3,98,76,.14) 1px, transparent 1px); background-size: 22px 22px; }
        details > summary::-webkit-details-marker { display: none; }
        @media (prefers-reduced-motion: reduce) {
            [data-reveal] { opacity: 1; transform: none; transition: none; }
            .float, .marquee-track { animation: none; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased">

    {{-- ── NAV ── --}}
    <header class="fixed top-0 inset-x-0 z-50 bg-white/85 backdrop-blur-xl border-b border-gray-100">
        <nav class="max-w-7xl mx-auto px-5 sm:px-8 h-20 flex items-center justify-between">
            <a href="{{ route('marketing.home') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo.png') }}" alt="EasyGets" class="h-9 w-auto" onerror="this.outerHTML='<span class=&quot;font-display font-extrabold text-xl text-brand&quot;>EASYGETS<span class=&quot;text-gray-900&quot;>.NG</span></span>'">
            </a>
            <div class="hidden md:flex items-center gap-9 text-sm font-semibold text-gray-700">
                <a href="{{ route('marketing.home') }}" class="nav-link {{ request()->routeIs('marketing.home') ? 'active text-brand' : 'hover:text-brand' }} transition-colors">Home</a>
                <a href="{{ route('marketing.services') }}" class="nav-link {{ request()->routeIs('marketing.services') ? 'active text-brand' : 'hover:text-brand' }} transition-colors">Our Services</a>
                <a href="{{ route('marketing.about') }}" class="nav-link {{ request()->routeIs('marketing.about') ? 'active text-brand' : 'hover:text-brand' }} transition-colors">About Us</a>
                <a href="{{ route('marketing.contact') }}" class="nav-link {{ request()->routeIs('marketing.contact') ? 'active text-brand' : 'hover:text-brand' }} transition-colors">Contact Us</a>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="/app/signin" class="hidden sm:inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold text-brand bg-brand-light hover:bg-emerald-100 transition-colors">Sign In</a>
                <a href="/app/signup" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold text-white bg-ink hover:bg-brand transition-colors shadow-sm">Get Started</a>
                <button id="menuBtn" class="md:hidden w-10 h-10 rounded-full flex items-center justify-center text-gray-800 hover:bg-gray-100" aria-label="Menu">
                    <x-icon name="menu" class="w-6 h-6" />
                </button>
            </div>
        </nav>
        <div id="mobileMenu" class="md:hidden hidden border-t border-gray-100 bg-white px-5 pb-5">
            <div class="flex flex-col py-2 text-sm font-semibold text-gray-800">
                <a href="{{ route('marketing.home') }}" class="py-3 border-b border-gray-50">Home</a>
                <a href="{{ route('marketing.services') }}" class="py-3 border-b border-gray-50">Our Services</a>
                <a href="{{ route('marketing.about') }}" class="py-3 border-b border-gray-50">About Us</a>
                <a href="{{ route('marketing.contact') }}" class="py-3 border-b border-gray-50">Contact Us</a>
                <a href="/app/signin" class="py-3 text-brand">Sign In</a>
            </div>
        </div>
    </header>

    <main class="pt-20">
        @yield('content')
    </main>

    {{-- ── FOOTER ── --}}
    <footer class="bg-ink text-gray-300 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 grid grid-cols-2 md:grid-cols-5 gap-10">
            <div class="col-span-2">
                <img src="{{ asset('images/logo.png') }}" alt="EasyGets" class="h-10 w-auto mb-4" onerror="this.outerHTML='<span class=&quot;font-display font-extrabold text-2xl text-white&quot;>EASYGETS<span class=&quot;text-emerald-400&quot;>.NG</span></span>'">
                <p class="text-sm text-gray-400 leading-relaxed max-w-sm">Buy and sell gift cards at live rates, rent verification numbers, and handle airtime, data, electricity and more — fast, secure, and effortless, anytime, anywhere.</p>
                <a href="/app/signup" class="inline-flex mt-5 items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-ink bg-emerald-400 hover:bg-emerald-300 transition-colors">Get Started Now <x-icon name="arrow-right" class="w-4 h-4" /></a>
            </div>
            <div>
                <h4 class="font-display font-bold text-white mb-4">Services</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('marketing.services') }}#giftcards" class="hover:text-emerald-400 transition-colors">Gift Cards</a></li>
                    <li><a href="{{ route('marketing.services') }}#rent-number" class="hover:text-emerald-400 transition-colors">Rent Number</a></li>
                    <li><a href="{{ route('marketing.services') }}#airtime" class="hover:text-emerald-400 transition-colors">Airtime Top-Up</a></li>
                    <li><a href="{{ route('marketing.services') }}#data" class="hover:text-emerald-400 transition-colors">Data Subscription</a></li>
                    <li><a href="{{ route('marketing.services') }}#electricity" class="hover:text-emerald-400 transition-colors">Electricity</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-display font-bold text-white mb-4">Company</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('marketing.services') }}" class="hover:text-emerald-400 transition-colors">Our Services</a></li>
                    <li><a href="{{ route('marketing.about') }}" class="hover:text-emerald-400 transition-colors">About Us</a></li>
                    <li><a href="{{ route('marketing.contact') }}" class="hover:text-emerald-400 transition-colors">Contact Us</a></li>
                    <li><a href="/app/signin" class="hover:text-emerald-400 transition-colors">Login</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-display font-bold text-white mb-4">Legal</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('marketing.terms') }}" class="hover:text-emerald-400 transition-colors">Terms of Service</a></li>
                    <li><a href="{{ route('marketing.privacy') }}" class="hover:text-emerald-400 transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('marketing.refund') }}" class="hover:text-emerald-400 transition-colors">Refund Policy</a></li>
                </ul>
                <h4 class="font-display font-bold text-white mt-6 mb-3">Socials</h4>
                <div class="flex gap-3">
                    <a href="#" aria-label="Facebook" class="w-9 h-9 rounded-full bg-white/10 hover:bg-brand flex items-center justify-center transition-colors">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M22 12a10 10 0 10-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0022 12z"/></svg>
                    </a>
                    <a href="#" aria-label="Instagram" class="w-9 h-9 rounded-full bg-white/10 hover:bg-brand flex items-center justify-center transition-colors">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="white"/></svg>
                    </a>
                    <a href="#" aria-label="TikTok" class="w-9 h-9 rounded-full bg-white/10 hover:bg-brand flex items-center justify-center transition-colors">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M16.6 2h-3.2v13.3a2.9 2.9 0 11-2.9-2.9c.2 0 .5 0 .7.1V9.3a6 6 0 10.6 11.9 6.1 6.1 0 005.8-6.1V8.2a7.6 7.6 0 004.3 1.3V6.3a4.4 4.4 0 01-5.3-4.3z"/></svg>
                    </a>
                    <a href="#" aria-label="YouTube" class="w-9 h-9 rounded-full bg-white/10 hover:bg-brand flex items-center justify-center transition-colors">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M23 7.2a3 3 0 00-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 001 7.2 31 31 0 000 12a31 31 0 001 4.8 3 3 0 002.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5A3 3 0 0023 16.8 31 31 0 0024 12a31 31 0 00-1-4.8zM9.8 15.5V8.5l6 3.5-6 3.5z"/></svg>
                    </a>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-5 sm:px-8 mt-12 pt-6 border-t border-white/10 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-gray-500">
            <p>&copy; {{ now()->year }} EasyGets. All rights reserved.</p>
            <p>Designed by Heckstechie</p>
        </div>
    </footer>

    <a href="https://wa.me/2348022746033" target="_blank" rel="noopener" class="fixed bottom-5 right-5 z-50 flex items-center gap-2 bg-white shadow-lg rounded-full pl-4 pr-1.5 py-1.5 text-xs font-semibold text-gray-700 hover:shadow-xl transition-shadow">
        <span class="hidden sm:inline">Need Help? <span class="font-bold text-brand">Chat with us</span></span>
        <span class="w-9 h-9 rounded-full bg-[#25D366] flex items-center justify-center text-white">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M12 2a10 10 0 00-8.5 15.2L2 22l4.9-1.5A10 10 0 1012 2zm5.8 14.3c-.2.6-1.2 1.2-1.7 1.3-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.6-2.6-1.1-4.3-3.8-4.5-4-.1-.2-1.1-1.4-1.1-2.7s.7-1.9.9-2.1c.2-.2.5-.3.6-.3h.5c.1 0 .3 0 .5.4l.7 1.7c.1.2.1.3 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1.1 2.2 1.4 2.5 1.6.3.1.5.1.6-.1l.6-.7c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.1.4.2.1.1.1.6-.1 1.1z"/></svg>
        </span>
    </a>

    <script>
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.12 });
        document.querySelectorAll('[data-reveal]').forEach(el => io.observe(el));
        document.getElementById('menuBtn')?.addEventListener('click', () => document.getElementById('mobileMenu').classList.toggle('hidden'));
    </script>
    @stack('scripts')
</body>
</html>