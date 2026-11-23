@extends('layouts.marketing')
@section('title', 'EasyGets — Buy & Sell Gift Cards, Pay Bills, Stay Connected')

@section('content')

{{-- ═════════ HERO ═════════ --}}
<section class="relative overflow-hidden bg-white">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute inset-0 dot-grid opacity-60 [mask-image:radial-gradient(ellipse_at_top_right,black,transparent_70%)]"></div>
        <div class="absolute -top-40 -right-40 w-[620px] h-[620px] rounded-full bg-brand/10 blur-3xl"></div>
        <div class="absolute top-1/2 -left-48 w-[460px] h-[460px] rounded-full bg-emerald-200/30 blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-5 sm:px-8 pt-14 sm:pt-20 pb-16 sm:pb-24 grid lg:grid-cols-2 gap-14 items-center">
        <div data-reveal="left" class="text-center lg:text-left">
            <div class="inline-flex items-center gap-2 bg-brand-light text-brand font-bold text-xs sm:text-sm px-4 py-2 rounded-full">
                <x-icon name="gift" class="w-4 h-4" /> Buy &amp; sell gift cards at live rates
            </div>
            <h1 class="font-display text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold leading-[1.08] text-ink mt-6">
                Trade gift cards.<br class="hidden sm:block"> Pay bills. <span class="text-brand">Stay connected.</span>
            </h1>
            <p class="mt-6 text-gray-600 text-base sm:text-lg max-w-xl mx-auto lg:mx-0 leading-relaxed">
                Sell your gift cards for cash or buy top brands instantly. Rent verification numbers, top up airtime and data, and pay electricity — all in one secure, effortless place.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center lg:justify-start gap-3 sm:gap-4">
                <a href="/app/signup" class="inline-flex items-center justify-center gap-2 px-7 py-4 rounded-full font-bold text-white bg-ink hover:bg-brand transition-colors shadow-lg shadow-brand/15">Get Started Now <x-icon name="arrow-right" class="w-4 h-4" /></a>
                <a href="/app/signin" class="inline-flex items-center justify-center px-7 py-4 rounded-full font-bold text-brand bg-white border-2 border-brand/20 hover:border-brand transition-colors">Login Now</a>
            </div>
            <div class="mt-9 flex items-center justify-center lg:justify-start gap-4">
                <div class="flex -space-x-3">
                    @foreach([1,2,3,4] as $n)
                        <img src="{{ asset('images/stack-'.$n.'.jpg') }}" alt="" class="w-10 h-10 rounded-full border-2 border-white object-cover">
                    @endforeach
                </div>
                <div class="text-left">
                    <div class="flex text-amber-400 gap-0.5">@for($s=0;$s<5;$s++)<x-icon name="star" :fill="true" class="w-3.5 h-3.5" />@endfor</div>
                    <p class="text-sm text-gray-600"><span class="font-bold text-ink">500+</span> users trade with EasyGets</p>
                </div>
            </div>
        </div>

        <div class="relative flex justify-center lg:justify-end">
            <div class="relative w-full max-w-md lg:max-w-lg">
                <div class="absolute -inset-8 bg-brand/10 rounded-[3rem] blur-3xl"></div>
                <div class="float relative">
                    <img src="{{ asset('images/hero.png') }}" alt="EasyGets app" class="relative w-full h-auto block" loading="eager">
                </div>
                <div class="hidden sm:flex absolute -left-6 top-10 items-center gap-3 bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-gray-100 px-4 py-3 float" style="animation-delay:.4s">
                    <span class="w-10 h-10 rounded-xl bg-brand text-white flex items-center justify-center"><x-icon name="gift" class="w-5 h-5" /></span>
                    <div><p class="text-xs text-gray-500">Gift card sold</p><p class="text-sm font-extrabold text-ink">+₦148,500 credited</p></div>
                </div>
                <div class="hidden sm:flex absolute -right-4 bottom-12 items-center gap-3 bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-gray-100 px-4 py-3 float" style="animation-delay:1.2s">
                    <span class="w-10 h-10 rounded-xl bg-emerald-100 text-brand flex items-center justify-center"><x-icon name="message" class="w-5 h-5" /></span>
                    <div><p class="text-xs text-gray-500">Number rented</p><p class="text-sm font-extrabold text-ink">SMS received · 2s</p></div>
                </div>
                <p class="hidden sm:block absolute -bottom-6 left-4 text-[10px] text-gray-400">Illustrative example</p>
            </div>
        </div>
    </div>

    <div class="relative border-t border-gray-100 bg-white/80 backdrop-blur">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 py-10 grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8 text-center stagger">
            @foreach([['500+','Active users'],['99.99%','Uptime'],['100%','Secure payments'],['24/7','Customer support']] as $i => [$num,$label])
                <div data-reveal style="--i:{{ $i }}">
                    <p class="font-display text-2xl sm:text-3xl font-extrabold text-ink">{{ $num }}</p>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═════════ BRAND MARQUEE ═════════ --}}
<section class="py-10 bg-white border-b border-gray-100">
    <p class="text-center text-xs font-bold uppercase tracking-[0.18em] text-gray-400 mb-6">Trade the cards people ask for most</p>
    <div class="marquee overflow-hidden [mask-image:linear-gradient(90deg,transparent,black_12%,black_88%,transparent)]">
        <div class="marquee-track flex gap-4">
            @php $brands = ['Amazon','iTunes','Steam','Google Play','Xbox','PlayStation','Razer','Nike','Walmart','eBay','Sephora','Netflix']; @endphp
            @foreach(array_merge($brands, $brands) as $b)
                <span class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-gray-200 bg-gray-50 text-sm font-semibold text-gray-700 whitespace-nowrap">
                    <x-icon name="gift" class="w-4 h-4 text-brand" /> {{ $b }}
                </span>
            @endforeach
        </div>
    </div>
</section>

{{-- ═════════ GIFT CARDS FLAGSHIP ═════════ --}}
<section class="py-24 bg-brand-light/40">
    <div class="max-w-7xl mx-auto px-5 sm:px-8 grid lg:grid-cols-5 gap-12 items-start">
        <div class="lg:col-span-2 lg:sticky lg:top-28" data-reveal="left">
            <span class="inline-flex items-center gap-2 text-brand font-bold text-xs uppercase tracking-[0.16em]"><x-icon name="gift" class="w-4 h-4" /> Gift Cards</span>
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-ink mt-4 leading-tight">Gift cards, done right.</h2>
            <p class="text-gray-600 mt-4 leading-relaxed">Whether you're cashing out an unused card or grabbing one for yourself, EasyGets gives you transparent live rates, PIN-protected checkout and fast, reliable payouts.</p>
            <ul class="mt-6 space-y-3 text-sm text-gray-700">
                @foreach(['Live, transparent rates — no hidden cuts','PIN-secured purchases and withdrawals','Earnings go to your withdrawable balance, then your bank'] as $point)
                    <li class="flex items-start gap-3"><span class="mt-0.5 w-5 h-5 rounded-full bg-brand text-white flex items-center justify-center flex-shrink-0"><x-icon name="check" class="w-3 h-3" /></span>{{ $point }}</li>
                @endforeach
            </ul>
            <a href="/app/signup" class="inline-flex items-center gap-2 mt-8 px-7 py-3.5 rounded-full bg-brand text-white font-bold text-sm hover:bg-emerald-700 transition-colors">Start trading <x-icon name="arrow-right" class="w-4 h-4" /></a>
        </div>

        <div class="lg:col-span-3 grid sm:grid-cols-2 gap-5 stagger">
            <div data-reveal style="--i:0" class="rounded-3xl p-8 bg-white border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                <span class="w-12 h-12 rounded-2xl bg-brand text-white flex items-center justify-center"><x-icon name="banknote" class="w-6 h-6" /></span>
                <h3 class="font-display text-xl font-bold text-ink mt-5">Sell a gift card</h3>
                <p class="text-sm text-gray-500 mt-2 leading-relaxed">Pick your brand, upload the card or enter the code, and see your Naira payout before you submit.</p>
                <ol class="mt-5 space-y-2.5 text-sm text-gray-700">
                    <li class="flex gap-3"><span class="font-bold text-brand">1</span> Choose brand &amp; card type</li>
                    <li class="flex gap-3"><span class="font-bold text-brand">2</span> Submit images or code with your PIN</li>
                    <li class="flex gap-3"><span class="font-bold text-brand">3</span> Get paid once verified</li>
                </ol>
            </div>
            <div data-reveal style="--i:1" class="rounded-3xl p-8 bg-ink text-white shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                <span class="w-12 h-12 rounded-2xl bg-emerald-400 text-ink flex items-center justify-center"><x-icon name="bag" class="w-6 h-6" /></span>
                <h3 class="font-display text-xl font-bold mt-5">Buy a gift card</h3>
                <p class="text-sm text-gray-400 mt-2 leading-relaxed">Pay from your wallet and get your code delivered instantly, saved safely in your history.</p>
                <ol class="mt-5 space-y-2.5 text-sm text-gray-300">
                    <li class="flex gap-3"><span class="font-bold text-emerald-400">1</span> Search a brand &amp; pick an amount</li>
                    <li class="flex gap-3"><span class="font-bold text-emerald-400">2</span> Confirm with your PIN</li>
                    <li class="flex gap-3"><span class="font-bold text-emerald-400">3</span> Receive your code in seconds</li>
                </ol>
            </div>

            <div data-reveal style="--i:2" class="sm:col-span-2 rounded-3xl p-6 bg-white border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center gap-5">
                <span class="w-12 h-12 rounded-2xl bg-brand-light text-brand flex items-center justify-center flex-shrink-0"><x-icon name="tag" class="w-6 h-6" /></span>
                <div class="flex-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Sample rate card · illustrative</p>
                    <p class="font-display font-bold text-ink mt-1">Amazon · USA · Ecode</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-xs text-gray-400">You receive for $100</p>
                    <p class="font-display text-2xl font-extrabold text-brand">₦148,500</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═════════ ALL SERVICES (BENTO) ═════════ --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6" data-reveal>
            <div class="max-w-xl">
                <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-ink">All your digital services in one place</h2>
                <p class="text-gray-600 mt-4">Gift cards and instant numbers up front — plus every bill you pay regularly, ready in seconds.</p>
            </div>
            <a href="{{ route('marketing.services') }}" class="inline-flex items-center gap-2 self-start md:self-auto px-6 py-3 rounded-full bg-ink text-white font-bold text-sm hover:bg-brand transition-colors">See all services <x-icon name="arrow-right" class="w-4 h-4" /></a>
        </div>

        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-5 stagger">
            <div data-reveal style="--i:0" class="sm:col-span-2 rounded-3xl p-8 text-white relative overflow-hidden" style="background:linear-gradient(135deg,#03624C 0%,#021f18 100%)">
                <div class="absolute -right-12 -bottom-12 w-56 h-56 rounded-full bg-white/5"></div>
                <span class="w-12 h-12 rounded-2xl bg-white/15 flex items-center justify-center"><x-icon name="message" class="w-6 h-6" /></span>
                <h3 class="font-display text-xl font-bold mt-5">Rent Number</h3>
                <p class="text-sm text-emerald-50/80 mt-2 max-w-sm leading-relaxed">Rent a one-time or long-term verification number for WhatsApp, Telegram, Google and hundreds more — SMS delivered in real time.</p>
                <a href="/app/signup" class="inline-flex items-center gap-1.5 mt-5 text-sm font-bold">Rent a number <x-icon name="arrow-up-right" class="w-4 h-4" /></a>
            </div>
            @foreach([
                ['phone','Airtime Top-Up','Instantly recharge any network in seconds.'],
                ['wifi','Data Subscription','Fast, affordable plans that keep you online.'],
                ['zap','Electricity','Pay your bills and keep the power on.'],
                ['tv','TV / Cable','Renew your subscriptions in a few clicks.'],
                ['sim','eSIM','Get connected without a physical SIM.'],
                ['trending','Social Boost','Grow with real followers, views and engagement.'],
            ] as $i => [$icon,$title,$desc])
                <div data-reveal style="--i:{{ $i + 1 }}" class="rounded-3xl p-7 bg-gray-50 border border-gray-100 hover:bg-white hover:shadow-xl hover:-translate-y-1 transition-all">
                    <span class="w-11 h-11 rounded-xl bg-brand-light text-brand flex items-center justify-center"><x-icon :name="$icon" class="w-5 h-5" /></span>
                    <h3 class="font-display font-bold text-ink mt-5">{{ $title }}</h3>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═════════ HOW IT WORKS ═════════ --}}
<section class="py-24 bg-brand-light/40">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="text-center max-w-2xl mx-auto" data-reveal>
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-ink">Get started in minutes</h2>
            <p class="text-gray-600 mt-4">Four simple steps from sign-up to payout or delivery.</p>
        </div>
        <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-4 gap-6 stagger">
            @foreach([
                ['user-plus','Create an account','Sign up and unlock every service instantly.'],
                ['grid','Pick a service','Gift cards, numbers, airtime, bills — choose what you need.'],
                ['lock','Pay securely','Use your wallet, card or bank transfer with PIN protection.'],
                ['zap','Instant delivery','Codes, numbers and top-ups arrive in seconds.'],
            ] as $i => [$icon,$t,$d])
                <div data-reveal style="--i:{{ $i }}" class="relative rounded-3xl bg-white p-7 border border-gray-100 shadow-sm">
                    <span class="absolute top-6 right-6 font-display text-4xl font-extrabold text-brand/10">0{{ $i + 1 }}</span>
                    <span class="w-12 h-12 rounded-2xl bg-brand text-white flex items-center justify-center"><x-icon :name="$icon" class="w-6 h-6" /></span>
                    <h3 class="font-display font-bold text-ink mt-5">{{ $t }}</h3>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═════════ WHY CHOOSE ═════════ --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div data-reveal class="max-w-xl">
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-ink">Why choose EasyGets?</h2>
            <p class="text-gray-600 mt-4">From fair gift card rates to instant delivery, we make every transaction simple and stress-free.</p>
        </div>
        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-5 stagger">
            @foreach([
                ['zap','Instant delivery','Gift card codes, numbers and top-ups land in seconds.'],
                ['shield','Trusted & secure','Encrypted, PIN-protected and run through verified payment gateways.'],
                ['tag','Fair, live rates','Transparent gift card pricing that updates in real time.'],
                ['clock','Always on','Our automated systems run round the clock, day or night.'],
            ] as $i => [$icon,$t,$d])
                <div data-reveal style="--i:{{ $i }}" class="rounded-3xl p-7 border border-gray-100 hover:border-brand/30 hover:shadow-lg transition-all">
                    <span class="w-11 h-11 rounded-xl bg-brand-light text-brand flex items-center justify-center"><x-icon :name="$icon" class="w-5 h-5" /></span>
                    <h3 class="font-display font-bold text-ink mt-5">{{ $t }}</h3>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid md:grid-cols-3 gap-5 stagger">
            <div data-reveal class="rounded-3xl p-8 bg-brand text-white flex gap-5 items-start">
                <div class="flex-1">
                    <h3 class="font-display font-bold text-lg">Proven reliability</h3>
                    <p class="text-sm text-emerald-50/80 mt-2 leading-relaxed">We've helped users across Nigeria trade gift cards, rent numbers and stay connected — fast, secure and without hassle.</p>
                </div>
                <img src="{{ asset('images/reliability.jpg') }}" alt="" class="w-20 h-20 rounded-2xl object-cover flex-shrink-0 hidden sm:block">
            </div>
            <div data-reveal style="--i:1" class="rounded-3xl p-8 bg-brand text-white text-center">
                <p class="font-display text-4xl font-extrabold">4.9+</p>
                <div class="flex justify-center text-amber-300 gap-1 mt-2">@for($s=0;$s<5;$s++)<x-icon name="star" :fill="true" class="w-4 h-4" />@endfor</div>
                <p class="text-sm text-emerald-50/80 mt-3 leading-relaxed">Trusted by Nigerians for seamless, secure and instant access to digital services.</p>
            </div>
            <div data-reveal style="--i:2" class="rounded-3xl bg-brand text-white overflow-hidden">
                @foreach([['500+','Users'],['99.99%','Uptime'],['100%','Secure payments']] as $i => [$n,$l])
                    <div class="flex justify-between items-center px-8 py-5 {{ $i < 2 ? 'border-b border-white/15' : 'bg-emerald-700/40' }}">
                        <span class="font-display font-extrabold text-xl">{{ $n }}</span><span class="text-xs text-emerald-50/70">{{ $l }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═════════ SMART WALLET ═════════ --}}
<section class="relative py-24 bg-ink text-white overflow-hidden">
    <div class="pointer-events-none absolute -top-32 left-1/3 w-[520px] h-[520px] rounded-full bg-brand/30 blur-3xl"></div>
    <div class="relative max-w-7xl mx-auto px-5 sm:px-8 grid lg:grid-cols-2 gap-12 items-center">
        <div data-reveal="left">
            <span class="inline-flex items-center gap-2 text-emerald-400 font-bold text-xs uppercase tracking-[0.16em]"><x-icon name="wallet" class="w-4 h-4" /> Smart Wallet</span>
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold mt-4">Your money, beautifully organised.</h2>
            <p class="text-gray-400 mt-4 max-w-md leading-relaxed">Fund instantly, spend across every service, and withdraw your gift card earnings straight to your bank.</p>
            <a href="/app/signup" class="inline-flex items-center gap-2 mt-7 px-7 py-3.5 rounded-full font-bold bg-emerald-400 text-ink hover:bg-emerald-300 transition-colors">Create an account <x-icon name="arrow-right" class="w-4 h-4" /></a>
        </div>
        <div data-reveal="right" class="flex justify-center lg:justify-end">
            <img src="{{ asset('images/wallet-phone.jpg') }}" alt="EasyGets wallet" class="w-full max-w-sm rounded-3xl shadow-2xl ring-1 ring-white/10">
        </div>
    </div>

    <div class="relative max-w-7xl mx-auto px-5 sm:px-8 mt-16 grid sm:grid-cols-2 lg:grid-cols-4 gap-5 stagger">
        @foreach([
            ['bank','Virtual account','A dedicated account number for quick deposits.'],
            ['repeat','Auto-funding','Instant wallet updates whenever you transfer.'],
            ['receipt','Transaction history','Every top-up, bill and gift card trade in one record.'],
            ['banknote','Withdrawable earnings','Cash out your gift card sales to your bank.'],
        ] as $i => [$icon,$t,$d])
            <div data-reveal style="--i:{{ $i }}" class="rounded-3xl p-6 bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                <span class="w-10 h-10 rounded-xl bg-brand text-white flex items-center justify-center"><x-icon :name="$icon" class="w-5 h-5" /></span>
                <h3 class="font-display font-bold mt-4">{{ $t }}</h3>
                <p class="text-sm text-gray-400 mt-2 leading-relaxed">{{ $d }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ═════════ TESTIMONIALS ═════════ --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="text-center max-w-xl mx-auto" data-reveal>
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-ink">What our users say</h2>
            <p class="text-gray-600 mt-4">Trusted by everyday people across Nigeria for speed, security and convenience.</p>
        </div>
        <div class="mt-12 grid md:grid-cols-3 gap-6 stagger">
            @foreach([
                ['t-samuel.jpg','Samuel Toluwani','Selling my gift cards on EasyGets is smooth — the rate is clear before I submit and I get paid fast.'],
                ['t-mary.jpg','Mary Anne','I rent numbers here every week for verifications. Instant delivery, every single time.'],
                ['t-chike.jpg','Chike Nobis','From gift cards to electricity, EasyGets has completely simplified how I handle everything online.'],
            ] as $i => [$img,$name,$quote])
                <div data-reveal style="--i:{{ $i }}" class="rounded-3xl p-7 bg-gray-50 border border-gray-100">
                    <div class="flex text-amber-400 gap-0.5">@for($s=0;$s<5;$s++)<x-icon name="star" :fill="true" class="w-4 h-4" />@endfor</div>
                    <p class="text-gray-700 mt-4 leading-relaxed">“{{ $quote }}”</p>
                    <div class="flex items-center gap-3 mt-6">
                        <img src="{{ asset('images/'.$img) }}" alt="{{ $name }}" class="w-11 h-11 rounded-full object-cover">
                        <div><p class="font-bold text-sm text-ink">{{ $name }}</p><p class="text-xs text-gray-400">EasyGets user</p></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═════════ FAQ ═════════ --}}
<section class="py-24 bg-brand-light/40">
    <div class="max-w-4xl mx-auto px-5 sm:px-8">
        <div class="text-center" data-reveal>
            <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-ink">Frequently asked questions</h2>
            <p class="text-gray-600 mt-3">Got questions? We've got answers.</p>
        </div>
        <div class="mt-10 space-y-3">
            @foreach([
                ['How do I sell a gift card?','Open Gift Cards, choose Sell, pick your brand and card type, upload the card or enter the code, and confirm with your PIN. You\'ll see your Naira payout before you submit.'],
                ['How fast do I get paid?','Cards are reviewed quickly. Once approved, your payout is credited to your withdrawable balance, which you can send to your bank.'],
                ['Can I buy gift cards too?','Yes. Pay from your wallet and your code is delivered instantly and saved in your history.'],
                ['What numbers can I rent?','One-time and longer-term verification numbers for many platforms across multiple countries, with SMS delivered in real time.'],
                ['Is EasyGets secure?','Every transaction is encrypted and PIN-protected, and we use verified payment gateways. We also have zero tolerance for fraud.'],
                ['How do I contact support?','Use live chat, WhatsApp or the Contact Us page — our team responds promptly.'],
            ] as $i => [$q,$a])
                <details data-reveal class="group rounded-2xl bg-white border border-gray-100 px-6 py-5 open:shadow-md transition-shadow">
                    <summary class="flex justify-between items-center cursor-pointer font-semibold text-ink list-none">
                        {{ $q }}
                        <span class="ml-4 w-7 h-7 rounded-full bg-brand-light text-brand flex items-center justify-center flex-shrink-0 group-open:rotate-45 transition-transform text-lg leading-none">+</span>
                    </summary>
                    <p class="text-sm text-gray-600 mt-3 leading-relaxed">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ═════════ FINAL CTA ═════════ --}}
<section class="relative py-28 overflow-hidden">
    <img src="{{ asset('images/cta-bg.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-br from-ink/90 via-ink/80 to-brand/70"></div>
    <div class="relative max-w-3xl mx-auto px-5 text-center text-white" data-reveal="scale">
        <span class="inline-flex w-14 h-14 rounded-2xl bg-white/10 backdrop-blur items-center justify-center"><x-icon name="gift" class="w-7 h-7 text-emerald-300" /></span>
        <h2 class="font-display text-3xl sm:text-5xl font-extrabold mt-6">Go easy with EasyGets.</h2>
        <p class="mt-4 text-gray-200 max-w-xl mx-auto">Sign up today, trade gift cards at live rates and enjoy lightning-fast transactions with total peace of mind.</p>
        <a href="/app/signup" class="inline-flex items-center gap-2 mt-8 px-8 py-4 rounded-full font-bold bg-emerald-400 text-ink hover:bg-emerald-300 transition-colors">Create an account <x-icon name="arrow-right" class="w-4 h-4" /></a>
    </div>
</section>

@endsection