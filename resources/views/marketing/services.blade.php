@extends('layouts.marketing')
@section('title', 'Our Services — EasyGets')

@section('content')
<section class="bg-[#06110d] text-white py-24 text-center">
    <div class="max-w-3xl mx-auto px-5" data-reveal>
        <h1 class="text-4xl sm:text-5xl font-extrabold">Our Services</h1>
        <p class="text-gray-400 mt-4 text-lg">Gift cards, instant numbers, bills and more — everything you need, right at your fingertips.</p>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">

        <div class="grid lg:grid-cols-2 gap-8 mb-16">
            <div id="giftcards" class="rounded-3xl p-8 sm:p-10 text-white relative overflow-hidden" style="background:linear-gradient(135deg,#03624C,#021f18)" data-reveal="left">
                <span class="text-4xl">🎁</span>
                <h2 class="text-2xl font-extrabold mt-5">Gift Cards <span class="text-xs font-bold bg-white/15 px-2 py-1 rounded-full ml-2 align-middle">Flagship</span></h2>
                <p class="text-emerald-50/80 mt-3 leading-relaxed">Buy top gift card brands with instant code delivery, or sell your unused cards for cash straight to your withdrawable balance — all at transparent, live rates.</p>
            </div>
            <div id="rent-number" class="rounded-3xl p-8 sm:p-10 bg-gray-50 border border-gray-100" data-reveal="right">
                <span class="text-4xl">📲</span>
                <h2 class="text-2xl font-extrabold mt-5 text-gray-900">Rent Number <span class="text-xs font-bold bg-brand-light text-brand px-2 py-1 rounded-full ml-2 align-middle">Flagship</span></h2>
                <p class="text-gray-500 mt-3 leading-relaxed">Instantly rent a one-time or long-term verification number for WhatsApp, Telegram, Google, and hundreds of other platforms, across multiple countries.</p>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 stagger">
            @foreach([
                ['📞','airtime','Airtime Top-Up','Stay connected anytime. Instantly recharge any network in seconds.'],
                ['📶','data','Data Subscription','Fast, affordable data plans to keep you online and streaming without limits.'],
                ['⚡','electricity','Electricity','Pay your electricity bills easily and enjoy uninterrupted power from home.'],
                ['📺','tv','TV/Cable','Renew your cable or satellite subscriptions in just a few clicks.'],
                ['🛜','esim','eSIM','Get connected without a physical SIM, activate your eSIM instantly.'],
                ['🚀','social-boost','Social Boost','Grow your social presence fast with real followers, views, and engagement.'],
                ['🎓','jamb','JAMB e-PINs','Get your official JAMB e-PINs instantly and stress-free — 100% valid for registration.'],
                ['📄','waec','WAEC Result & Exam PINs','Purchase genuine WAEC result checker and exam PINs in seconds with verified authenticity.'],
                ['🎰','betting','Betting Wallet Funding','Top up your favorite betting accounts instantly — Bet9ja, SportyBet, 1xBet and more.'],
            ] as $i => [$icon,$id,$title,$desc])
                <div id="{{ $id }}" data-reveal style="--i:{{ $i }}" class="rounded-2xl p-7 bg-white border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-brand-light flex items-center justify-center text-2xl">{{ $icon }}</div>
                    <h3 class="font-bold mt-4">{{ $title }}</h3>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-24 bg-[#06110d] text-white relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-5 sm:px-8 grid lg:grid-cols-2 gap-10 items-center">
        <div data-reveal="left">
            <h2 class="text-3xl sm:text-4xl font-extrabold">Smart Wallet System</h2>
            <p class="text-gray-400 mt-4 max-w-md">Manage your funds effortlessly with our secure and intelligent wallet — fund it instantly, trade gift cards, and track every transaction.</p>
            <a href="/app/signup" class="inline-flex mt-7 px-7 py-3.5 rounded-full font-bold bg-brand hover:bg-emerald-400 transition-colors">Create an Account</a>
        </div>
        <div class="grid grid-cols-2 gap-4 stagger">
            @foreach([['01','Dedicated Account','Get a dedicated account number for quick deposits and seamless transactions.'],['02','Auto-Funding','Enjoy instant wallet updates whenever you make a transfer — no delays.'],['03','Transaction History','Keep track of every top-up, bill, or gift card trade in one place.'],['04','Withdrawable Balance','Sell gift cards and withdraw your earnings straight to your bank.']] as $i => [$n,$t,$d])
                <div data-reveal style="--i:{{ $i }}" class="rounded-2xl p-5 bg-white/5 border border-white/10">
                    <span class="inline-flex w-8 h-8 rounded-lg bg-brand text-xs font-extrabold items-center justify-center">{{ $n }}</span>
                    <h3 class="font-bold mt-3 text-sm">{{ $t }}</h3>
                    <p class="text-xs text-gray-400 mt-2 leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="relative py-28 bg-gray-900 overflow-hidden">
    <div class="absolute inset-0 opacity-20" style="background:radial-gradient(600px 300px at 80% 20%, #03624C, transparent)"></div>
    <div class="relative max-w-3xl mx-auto px-5 text-center text-white" data-reveal="scale">
        <h2 class="text-3xl sm:text-4xl font-extrabold">Go Easy with EasyGets!</h2>
        <p class="mt-4 text-gray-300">Sign up today and enjoy lightning-fast transactions, unbeatable prices, and total peace of mind.</p>
        <a href="/app/signup" class="inline-flex mt-8 px-8 py-4 rounded-full font-bold bg-brand hover:bg-emerald-400 transition-colors">Create an Account</a>
    </div>
</section>
@endsection