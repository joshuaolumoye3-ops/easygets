@extends('layouts.marketing')
@section('title', 'EasyGets — Top Up, Pay Bills, Stay Connected, All In One Tap')

@section('content')

{{-- ── HERO ── --}}
<section class="bg-gradient-to-b from-white to-brand-light/40 overflow-hidden">
    <div class="max-w-7xl mx-auto px-5 sm:px-8 pt-14 pb-16 grid lg:grid-cols-2 gap-12 items-center">
        <div data-reveal="left">
            <div class="flex items-center gap-3 mb-5">
                <div class="flex -space-x-3">
                    @foreach([1,2,3,4] as $n)
                        <img src="{{ asset('images/stack-'.$n.'.jpg') }}" alt="" class="w-9 h-9 rounded-full border-2 border-white object-cover">
                    @endforeach
                </div>
                <span class="font-extrabold text-brand text-lg">500+<span class="text-gray-800 font-semibold text-base">Users</span></span>
            </div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.08] tracking-tight text-gray-900">
                Top up, Pay bills, Stay connected, all in one tap.
            </h1>
            <p class="mt-6 text-gray-600 text-lg max-w-lg leading-relaxed">
                From airtime and data to electricity, betting, and exam pins, EasyGets makes every transaction fast, secure, and effortless, anytime, anywhere.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="/app/signup" class="px-7 py-4 rounded-full font-bold text-white bg-gray-900 hover:bg-brand transition-colors shadow-lg shadow-brand/10">Get Started Now</a>
                <a href="/app/signin" class="px-7 py-4 rounded-full font-bold text-brand bg-white border-2 border-brand/20 hover:border-brand transition-colors">Login Now</a>
            </div>
        </div>

        <div class="relative" data-reveal="right">
            <img src="{{ asset('images/hero-man.jpg') }}" alt="EasyGets user" class="w-full max-w-md mx-auto rounded-3xl object-cover">

            <img src="{{ asset('images/avatar-1.jpg') }}" alt="" class="float absolute top-6 -right-2 w-14 h-14 rounded-full border-4 border-white shadow-lg object-cover hidden sm:block">
            <img src="{{ asset('images/avatar-2.jpg') }}" alt="" class="float-slow absolute top-1/3 -right-8 w-14 h-14 rounded-full border-4 border-white shadow-lg object-cover hidden sm:block" style="animation-delay:.6s">
            <img src="{{ asset('images/avatar-3.jpg') }}" alt="" class="float absolute bottom-20 -right-4 w-14 h-14 rounded-full border-4 border-white shadow-lg object-cover hidden sm:block" style="animation-delay:1.2s">

            <div class="float-slow absolute left-0 bottom-6 bg-white rounded-2xl shadow-xl p-4 w-64" style="animation-delay:.3s">
                <p class="text-xs text-gray-400">Wallet Balance</p>
                <div class="flex items-center justify-between mt-1">
                    <p class="text-lg font-extrabold text-brand">₦50,000</p>
                    <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded-full">Transactions ›</span>
                </div>
                <div class="flex gap-2 mt-3">
                    <span class="flex-1 text-center text-[11px] font-bold text-white bg-[#06110d] rounded-lg py-2">Get OTP</span>
                    <span class="flex-1 text-center text-[11px] font-bold text-white bg-[#06110d] rounded-lg py-2">Socials</span>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-gray-100 bg-white/70">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 py-10 grid grid-cols-2 md:grid-cols-4 gap-8 text-center stagger">
            @foreach([['500+','Users'],['99.99%','Uptime'],['100%','Secure Payments'],['24/7','Customer Support']] as $i => [$num,$label])
                <div data-reveal style="--i:{{ $i }}">
                    <p class="text-3xl font-extrabold text-gray-900">{{ $num }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── ALL SERVICES ── --}}
<section class="py-24 bg-brand-light/40">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div data-reveal="left">
            <span class="text-brand font-bold text-sm uppercase tracking-wide"></span>
            <h2 class="text-3xl sm:text-4xl font-extrabold max-w-xl text-brand">All Your Digital Services in One Place</h2>
            <p class="text-gray-500 mt-4 max-w-xl">Top up, pay bills, and stay connected in seconds, everything you need, right at your fingertips.</p>
            <a href="{{ route('marketing.services') }}" class="inline-flex mt-6 px-6 py-3 rounded-full bg-brand text-white font-bold text-sm hover:bg-emerald-700 transition-colors">See All Services</a>
        </div>

        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger">
            @foreach([
                ['📞','Airtime Top-Up','Stay connected anytime. Instantly recharge any network in seconds.'],
                ['📶','Data Subscription','Fast, affordable data plans to keep you online and streaming without limits.'],
                ['⚡','Electricity','Pay your electricity bills easily and enjoy uninterrupted power from home.'],
                ['⚡','Electricity','Renew your cable or satellite subscriptions in just a few clicks.'],
                ['🛜','eSIM','Get connected without a physical SIM, activate your eSIM instantly.'],
                ['🚀','Social Boost','Grow your social presence fast with real followers, views, and engagement.'],
            ] as $i => [$icon,$title,$desc])
                <div data-reveal style="--i:{{ $i }}" class="rounded-2xl p-7 bg-brand text-white hover:-translate-y-1 hover:shadow-xl transition-all">
                    <div class="w-11 h-11 rounded-lg bg-white/15 flex items-center justify-center text-xl">{{ $icon }}</div>
                    <h3 class="font-bold mt-5 text-lg">{{ $title }}</h3>
                    <p class="text-sm text-emerald-50/80 mt-2 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── WHY CHOOSE US ── --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div data-reveal>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-brand">Why Choose EasyGets?</h2>
            <p class="text-gray-500 mt-4 max-w-xl">From instant delivery to unbeatable rates, we make every transaction simple and stress-free</p>
        </div>
        <div class="mt-12 rounded-3xl bg-brand-light/50 p-8 sm:p-10 grid sm:grid-cols-2 lg:grid-cols-4 gap-8 stagger">
            @foreach([['01','Instant Delivery','Every transaction happens in seconds because your time matters.'],['02','Trusted & Secure','We use advanced encryption and verified payment gateways to keep your details safe.'],['03','Affordable Pricing','Enjoy unbeatable rates on all services, more value for every naira spent.'],['04','Instant Delivery','Our automated system runs round the clock. Top-up or pay bills anytime, day or night.']] as $i => [$n,$t,$d])
                <div data-reveal style="--i:{{ $i }}">
                    <span class="inline-flex w-10 h-10 rounded-lg bg-brand text-white items-center justify-center text-sm font-extrabold">{{ $n }}</span>
                    <h3 class="font-bold mt-4 text-brand">{{ $t }}</h3>
                    <p class="text-sm text-gray-600 mt-2 leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── GET STARTED STEPS ── --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div data-reveal>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-brand">Get Started In Minutes</h2>
            <p class="text-gray-500 mt-4 max-w-xl">Join smart users like you already using EasyGets to top up, pay bills, and stay connected in seconds.</p>
        </div>
        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-5 stagger">
            @foreach([['👤','Create an Account','Join EasyGets and unlock instant access to all digital services.','Sign Up'],['📱','Pick a Service','From airtime to electricity, choose what you need in seconds.',null],['🔒','Pay Securely','Use card or bank transfer, safe and seamless.',null],['⏱','Enjoy Instant Delivery','No waiting. No stress. Everything delivered in seconds',null]] as $i => [$icon,$t,$d,$link])
                <div data-reveal style="--i:{{ $i }}" class="rounded-2xl p-7 bg-brand text-white hover:-translate-y-1 hover:shadow-xl transition-all flex flex-col justify-between min-h-[220px]">
                    <div>
                        <div class="text-2xl">{{ $icon }}</div>
                        <h3 class="font-bold mt-5 text-lg">{{ $t }}</h3>
                        <p class="text-sm text-emerald-50/80 mt-2 leading-relaxed">{{ $d }}</p>
                    </div>
                    @if($link)
                        <a href="/app/signup" class="text-sm font-semibold text-white mt-4 inline-flex items-center gap-1">{{ $link }} ↗</a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid md:grid-cols-3 gap-5 stagger">
            <div class="rounded-2xl p-7 bg-brand text-white flex gap-4 items-start" data-reveal>
                <div class="flex-1">
                    <h3 class="font-bold text-lg">Proven Reliability</h3>
                    <p class="text-sm text-emerald-50/80 mt-2 leading-relaxed">We've helped users across Nigeria stay connected, powered, and online. From airtime and data to electricity and exam pins, EasyGets delivers every service fast, secure, and without hassle.</p>
                </div>
                <img src="{{ asset('images/reliability.jpg') }}" alt="" class="w-20 h-20 rounded-xl object-cover flex-shrink-0 hidden sm:block">
            </div>
            <div class="rounded-2xl p-7 bg-brand text-white text-center" data-reveal style="--i:1">
                <p class="text-4xl font-extrabold">4.9+</p>
                <p class="text-yellow-300 tracking-widest mt-1">★★★★★</p>
                <p class="text-sm text-emerald-50/80 mt-3 leading-relaxed">Easygets is transforming how Nigerians handle everyday payments with seamless, secure, and instant access to digital services that keep life running smoothly.</p>
            </div>
            <div class="rounded-2xl bg-brand text-white overflow-hidden" data-reveal style="--i:2">
                @foreach([['500+','Users'],['99.99%','Uptime'],['100%','Secure Payments']] as $i => [$n,$l])
                    <div class="flex justify-between items-center px-7 py-5 {{ $i < 2 ? 'border-b border-white/15' : '' }} {{ $i === 2 ? 'bg-emerald-600/40' : '' }}">
                        <span class="font-extrabold text-xl">{{ $n }}</span><span class="text-xs text-emerald-50/70">{{ $l }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ── SMART WALLET ── --}}
<section class="relative py-28 bg-[#06110d] text-white overflow-hidden">
    <div class="absolute inset-0 hidden lg:block" style="clip-path: polygon(0 0, 55% 0, 35% 100%, 0 100%); background: linear-gradient(135deg,#ffffff,#f3fbf8);"></div>
    <div class="max-w-7xl mx-auto px-5 sm:px-8 relative grid lg:grid-cols-2 gap-10 items-center">
        <div data-reveal="left">
            <h2 class="text-3xl sm:text-4xl font-extrabold">Smart Wallet System</h2>
            <p class="text-gray-300 mt-4 max-w-md">Manage your funds effortlessly with our secure and intelligent wallet features.</p>
            <a href="/app/signup" class="inline-flex mt-7 px-7 py-3.5 rounded-full font-bold bg-brand hover:bg-emerald-600 transition-colors">Create an account</a>
        </div>
        <div class="relative flex justify-center lg:justify-end" data-reveal="right">
            <img src="{{ asset('images/wallet-phone.jpg') }}" alt="Wallet app" class="w-full max-w-sm rounded-2xl shadow-2xl">
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 sm:px-8 mt-16 grid sm:grid-cols-2 lg:grid-cols-4 gap-5 stagger">
        @foreach([['01','Virtual Account','Get a dedicated account number for quick deposits and seamless transactions.'],['02','Auto-Funding','Enjoy instant wallet updates whenever you make a transfer, no delays.'],['03','Transaction History','Keep track of every top-up, bill, or payment with a detailed record system.'],['04','Spending Insights','Understand your usage and manage expenses better with real-time analytics.']] as $i => [$n,$t,$d])
            <div data-reveal style="--i:{{ $i }}" class="rounded-2xl p-6 bg-white/5 border border-white/10">
                <span class="inline-flex w-9 h-9 rounded-lg bg-brand text-xs font-extrabold items-center justify-center">{{ $n }}</span>
                <h3 class="font-bold mt-4">{{ $t }}</h3>
                <p class="text-sm text-gray-400 mt-2 leading-relaxed">{{ $d }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ── TESTIMONIALS ── --}}
<section class="py-24 bg-[#06110d] text-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="text-center max-w-xl mx-auto" data-reveal>
            <h2 class="text-3xl sm:text-4xl font-extrabold">What Our Users Say</h2>
            <p class="text-gray-400 mt-4">We are trusted by everyday people across Nigeria who count on EasyGets for speed, security, and convenience.</p>
        </div>
        <div class="mt-12 grid md:grid-cols-3 gap-6 stagger">
            @foreach([['t-samuel.jpg','Samuel Toluwani'],['t-mary.jpg','Mary Anne'],['t-chike.jpg','Chike Nobis']] as $i => [$img,$name])
                <div data-reveal style="--i:{{ $i }}" class="rounded-2xl p-6 bg-white/5 border border-white/10">
                    <div class="text-emerald-400 tracking-widest">★★★★★</div>
                    <p class="text-sm text-gray-300 mt-3 leading-relaxed">Easygets has completely simplified how I pay my bills. I recharge my meter and buy data in seconds, it's smooth and super reliable</p>
                    <div class="flex items-center gap-3 mt-5">
                        <img src="{{ asset('images/'.$img) }}" alt="{{ $name }}" class="w-10 h-10 rounded-full object-cover">
                        <div><p class="font-bold text-sm">{{ $name }}</p><p class="text-xs text-gray-400">User</p></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── FAQ ── --}}
<section class="py-24 bg-[#06110d] text-white">
    <div class="max-w-4xl mx-auto px-5 sm:px-8">
        <div class="text-center" data-reveal>
            <h2 class="text-3xl sm:text-4xl font-extrabold">Frequently Asked Questions</h2>
            <p class="text-gray-400 mt-3">Got questions? We've got answers.</p>
        </div>
        <div class="mt-10 grid sm:grid-cols-2 gap-3">
            @foreach([
                ['How do I create an account?','Tap "Get Started", enter your details and verify your email or phone, you\'ll be set up in minutes.'],
                ['Can I use Easygets at any time?','Yes, EasyGets is available 24/7, so you can top up, pay bills, or access any service anytime.'],
                ['How fast are transactions?','Transactions are processed instantly in most cases, with delivery happening in seconds.'],
                ['What if my transaction fails?','No funds leave your wallet on a failed transaction. See our Refund Policy for full details.'],
                ['Is Easygets secure?','Yes, every transaction is encrypted, PIN-protected, and processed through verified payment gateways.'],
                ['How do I contact customer support?','Reach us via live chat, WhatsApp, or the Contact Us page, our team responds promptly.'],
            ] as $i => [$q,$a])
                <details class="group rounded-xl bg-white/5 border border-white/10 px-5 py-4 open:bg-white/10 transition-colors">
                    <summary class="flex justify-between items-center cursor-pointer font-semibold text-sm list-none">
                        {{ $q }}
                        <span class="ml-3 text-brand group-open:rotate-45 transition-transform">+</span>
                    </summary>
                    <p class="text-sm text-gray-400 mt-3 leading-relaxed">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ── FINAL CTA ── --}}
<section class="relative py-28 overflow-hidden">
    <img src="{{ asset('images/cta-bg.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
    <div class="absolute inset-0 bg-[#06110d]/70"></div>
    <div class="relative max-w-3xl mx-auto px-5 text-center text-white" data-reveal="scale">
        <h2 class="text-3xl sm:text-4xl font-extrabold">Go Easy with Easygets!</h2>
        <p class="mt-4 text-gray-200">Sign up today and enjoy lightning-fast transactions, unbeatable prices, and total peace of mind.</p>
        <a href="/app/signup" class="inline-flex mt-8 px-8 py-4 rounded-full font-bold bg-brand hover:bg-emerald-400 transition-colors">Create an account</a>
    </div>
</section>

@endsection