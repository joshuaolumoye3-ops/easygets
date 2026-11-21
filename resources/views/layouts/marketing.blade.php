<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EasyGets — Gift Cards, Airtime, Data & Bills, All In One Tap')</title>
    <meta name="description" content="@yield('description', 'Buy and sell gift cards instantly, rent verification numbers, top up airtime and data, pay bills and more — fast, secure, all in one place.')">
    <link rel="shortcut icon" href="{{ asset('easygets-fav-black.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: { DEFAULT: '#03624C', dark: '#021f18', light: '#e6f5f1' } },
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [data-reveal] { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s ease; }
        [data-reveal].in { opacity: 1; transform: translateY(0); }
        [data-reveal="scale"] { transform: scale(.94); }
        [data-reveal="scale"].in { transform: scale(1); }
        [data-reveal="left"] { transform: translateX(-28px); }
        [data-reveal="left"].in { transform: translateX(0); }
        [data-reveal="right"] { transform: translateX(28px); }
        [data-reveal="right"].in { transform: translateX(0); }
        .stagger > * { transition-delay: calc(var(--i, 0) * 90ms); }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
        .float { animation: float 5s ease-in-out infinite; }
        @keyframes floatSlow { 0%,100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-10px) rotate(2deg); } }
        .float-slow { animation: floatSlow 7s ease-in-out infinite; }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        .shimmer { background: linear-gradient(110deg, transparent 40%, rgba(255,255,255,.35) 50%, transparent 60%); background-size: 200% 100%; animation: shimmer 2.8s ease-in-out infinite; }
        .grad-hero { background: radial-gradient(1200px 500px at 90% -10%, rgba(3,98,76,.15), transparent), linear-gradient(180deg, #ffffff 0%, #f3fbf8 100%); }
        .marquee-track { animation: marquee 22s linear infinite; }
        @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .nav-link { position: relative; }
        .nav-link::after { content:''; position:absolute; left:0; bottom:-4px; width:0; height:2px; background:#03624C; transition:width .25s ease; }
        .nav-link:hover::after, .nav-link.active::after { width:100%; }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased">

    {{-- ── NAV ── --}}
    <header class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
        <nav class="max-w-7xl mx-auto px-5 sm:px-8 h-20 flex items-center justify-between">
            <a href="{{ route('marketing.home') }}" class="flex items-center gap-2">
                <img src="{{ asset('easygets-logo.svg') }}" alt="EasyGets" class="h-8" onerror="this.outerHTML='<span class=&quot;font-extrabold text-xl text-brand&quot;>EASYGETS<span class=&quot;text-gray-900&quot;>.NG</span></span>'">
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm font-semibold text-gray-700">
                <a href="{{ route('marketing.home') }}" class="nav-link {{ request()->routeIs('marketing.home') ? 'active text-brand' : 'hover:text-brand' }} transition-colors">Home</a>
                <a href="{{ route('marketing.services') }}" class="nav-link {{ request()->routeIs('marketing.services') ? 'active text-brand' : 'hover:text-brand' }} transition-colors">Our Services</a>
                <a href="{{ route('marketing.about') }}" class="nav-link {{ request()->routeIs('marketing.about') ? 'active text-brand' : 'hover:text-brand' }} transition-colors">About Us</a>
                <a href="{{ route('marketing.contact') }}" class="nav-link {{ request()->routeIs('marketing.contact') ? 'active text-brand' : 'hover:text-brand' }} transition-colors">Contact Us</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="/app/signin" class="hidden sm:inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold text-brand bg-brand-light hover:bg-emerald-100 transition-colors">Sign In</a>
                <a href="/app/signup" class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-bold text-white bg-gray-900 hover:bg-brand transition-colors shadow-sm">Get Started</a>
            </div>
        </nav>
    </header>

    <main class="pt-20">
        @yield('content')
    </main>

    {{-- ── FOOTER ── --}}
    <footer class="bg-[#06110d] text-gray-300 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 grid grid-cols-2 md:grid-cols-5 gap-10">
            <div class="col-span-2">
                <span class="font-extrabold text-2xl text-white">EASYGETS<span class="text-brand">.NG</span></span>
                <p class="mt-4 text-sm text-gray-400 leading-relaxed max-w-sm">Buy and sell gift cards instantly, rent verification numbers, and handle airtime, data, electricity and more — fast, secure, and effortless, anytime, anywhere.</p>
                <a href="/app/signup" class="inline-flex mt-5 items-center px-5 py-2.5 rounded-full text-sm font-bold text-gray-900 bg-brand hover:bg-emerald-400 transition-colors">Get Started Now</a>
            </div>
            <div>
                <h4 class="font-bold text-white mb-4">Services</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('marketing.services') }}#giftcards" class="hover:text-brand transition-colors">Gift Cards</a></li>
                    <li><a href="{{ route('marketing.services') }}#rent-number" class="hover:text-brand transition-colors">Rent Number</a></li>
                    <li><a href="{{ route('marketing.services') }}#airtime" class="hover:text-brand transition-colors">Airtime Top-Up</a></li>
                    <li><a href="{{ route('marketing.services') }}#data" class="hover:text-brand transition-colors">Data Subscription</a></li>
                    <li><a href="{{ route('marketing.services') }}#electricity" class="hover:text-brand transition-colors">Electricity</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-4">Company</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('marketing.services') }}" class="hover:text-brand transition-colors">Our Services</a></li>
                    <li><a href="{{ route('marketing.about') }}" class="hover:text-brand transition-colors">About Us</a></li>
                    <li><a href="{{ route('marketing.contact') }}" class="hover:text-brand transition-colors">Contact Us</a></li>
                    <li><a href="/app/signin" class="hover:text-brand transition-colors">Login</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-4">Legal</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('marketing.terms') }}" class="hover:text-brand transition-colors">Terms of Service</a></li>
                    <li><a href="{{ route('marketing.privacy') }}" class="hover:text-brand transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('marketing.refund') }}" class="hover:text-brand transition-colors">Refund Policy</a></li>
                </ul>
                <div class="flex gap-3 mt-5">
                    @foreach(['facebook','instagram','tiktok','youtube'] as $icon)
                        <a href="#" class="w-9 h-9 rounded-full bg-white/10 hover:bg-brand flex items-center justify-center transition-colors" aria-label="{{ ucfirst($icon) }}">
                            <span class="text-xs">{{ strtoupper(substr($icon,0,2)) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-5 sm:px-8 mt-12 pt-6 border-t border-white/10 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-gray-500">
            <p>&copy; {{ now()->year }} EasyGets. All rights reserved.</p>
            <p>Designed by Heckstechie</p>
        </div>
    </footer>

    <a href="https://wa.me/2348022746033" target="_blank" rel="noopener" class="fixed bottom-5 right-5 z-50 flex items-center gap-2 bg-white shadow-lg rounded-full pl-4 pr-1.5 py-1.5 text-xs font-semibold text-gray-700 hover:shadow-xl transition-shadow">
        Need Help? <span class="font-bold text-brand">Chat with us</span>
        <span class="w-8 h-8 rounded-full bg-[#25D366] flex items-center justify-center text-white">💬</span>
    </a>

    <script>
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.15 });
        document.querySelectorAll('[data-reveal]').forEach((el, i) => {
            el.style.setProperty('--i', el.closest('.stagger') ? Array.from(el.parentElement.children).indexOf(el) : 0);
            io.observe(el);
        });
    </script>
    @stack('scripts')
</body>
</html>