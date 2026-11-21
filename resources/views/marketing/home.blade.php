@extends('layouts.marketing')
@section('title', 'EasyGets — Buy & Sell Gift Cards Instantly, Rent Numbers, Pay Bills')
@section('description', 'Nigeria\'s all-in-one platform for gift cards, verification numbers, airtime, data, electricity and more. Instant delivery, bank-level security.')

@section('content')

{{-- ── HERO ── --}}
<section class="grad-hero overflow-hidden">
    <div class="max-w-7xl mx-auto px-5 sm:px-8 pt-14 pb-20 grid lg:grid-cols-2 gap-12 items-center">
        <div data-reveal="left">
            <div class="inline-flex items-center gap-2 bg-brand-light text-brand font-bold text-xs px-4 py-2 rounded-full mb-5">
                🎁 Now Live — Buy &amp; Sell Gift Cards Instantly
            </div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.08] tracking-tight">
                Gift Cards, Numbers &amp; Bills — <span class="text-brand">All In One Tap.</span>
            </h1>
            <p class="mt-6 text-gray-600 text-lg max-w-lg leading-relaxed">
                Buy gift cards instantly or sell yours for cash, rent a verification number in seconds, and handle airtime, data, electricity and more — fast, secure, effortless.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="/app/signup" class="px-7 py-4 rounded-full font-bold text-white bg-gray-900 hover:bg-brand transition-colors shadow-lg shadow-brand/10">Get Started Now</a>
                <a href="/app/signin" class="px-7 py-4 rounded-full font-bold text-brand bg-white border-2 border-brand/20 hover:border-brand transition-colors">Login Now</a>
            </div>
            <div class="flex items-center gap-3 mt-8">
                <div class="flex -space-x-3">
                    @for($i=0;$i<4;$i++)
                        <div class="w-9 h-9 rounded-full border-2 border-white bg-gradient-to-br from-emerald-300 to-brand"></div>
                    @endfor
                </div>
                <span class="text-sm font-bold text-gray-700">500+ <span class="font-normal text-gray-500">Users already trading gift cards</span></span>
            </div>
        </div>

        <div class="relative" data-reveal="right">
            <div class="float relative bg-white rounded-3xl shadow-2xl p-6 max-w-sm mx-auto border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Sell Gift Card</p>
                <div class="mt-3 flex items-center gap-3 bg-gray-50 rounded-xl p-3">
                    <div class="w-10 h-10 rounded-lg bg-brand text-white flex items-center justify-center font-extrabold">A</div>
                    <div class="flex-1"><p class="text-sm font-bold">Amazon · $100 Ecode</p><p class="text-xs text-gray-400">USA</p></div>
                </div>
                <div class="mt-4 bg-brand-light rounded-xl p-4 text-center">
                    <p class="text-xs text-gray-500">You receive</p>
                    <p class="text-2xl font-extrabold text-brand">₦148,500</p>
                </div>
                <div class="mt-4 h-10 rounded-xl bg-gray-900 text-white text-sm font-bold flex items-center justify-center shimmer">Submit for Approval</div>
            </div>
            <div class="float-slow absolute -right-4 -top-6 bg-white rounded-2xl shadow-xl p-4 hidden sm:block">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Rent Number</p>
                <p class="text-sm font-extrabold mt-1">+1 •••• 7820</p>
                <p class="text-[10px] text-emerald-600 font-bold mt-1">● SMS Received</p>
            </div>
            <div class="float-slow absolute -left-6 bottom-6 bg-white rounded-2xl shadow-xl px-4 py-3 hidden sm:block" style="animation-delay:1s">
                <p class="text-[10px] text-gray-400">Wallet Balance</p>
                <p class="text-sm font-extrabold text-brand">₦50,000.00</p>
            </div>
        </div>
    </div>

    <div class="border-t border-gray-100 bg-white/70">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 py-8 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            @foreach([['500+','Users'],['99.99%','Uptime'],['100%','Secure Payments'],['24/7','Customer Support']] as $i => [$num,$label])
                <div data-reveal style="transition-delay: {{ $i * 100 }}ms">
                    <p class="text-3xl font-extrabold text-gray-900">{{ $num }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── FLAGSHIP: GIFT CARDS + RENT NUMBER ── --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="text-center max-w-2xl mx-auto" data-reveal>
            <span class="text-brand font-bold text-sm uppercase tracking-wide">Our flagship services</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold mt-3">Built around Gift Cards &amp; Instant Numbers</h2>
            <p class="text-gray-500 mt-4">The two things our users trade the most, now first-class on EasyGets.</p>
        </div>

        <div class="mt-14 grid lg:grid-cols-2 gap-8">
            <div class="group rounded-3xl p-8 sm:p-10 text-white relative overflow-hidden" style="background:linear-gradient(135deg,#03624C 0%,#021f18 100%)" data-reveal="scale">
                <div class="absolute -right-10 -bottom-10 w-48 h-48 rounded-full bg-white/5"></div>
                <span class="text-4xl">🎁</span>
                <h3 class="text-2xl font-extrabold mt-5">Gift Cards</h3>
                <p class="text-emerald-50/80 mt-3 leading-relaxed">Buy Amazon, iTunes, Steam &amp; more at great rates with instant code delivery — or sell your unused gift cards and get paid straight to your withdrawable balance.</p>
                <ul class="mt-6 space-y-2 text-sm text-emerald-50/90">
                    <li class="flex items-center gap-2">✓ Live, transparent rates</li>
                    <li class="flex items-center gap-2">✓ Instant codes on buy</li>
                    <li class="flex items-center gap-2">✓ Fast payout on sell</li>
                </ul>
                <a href="/app/signup" class="inline-flex mt-7 px-6 py-3 rounded-full bg-white text-brand font-bold text-sm group-hover:scale-105 transition-transform">Trade Gift Cards →</a>
            </div>

            <div class="group rounded-3xl p-8 sm:p-10 bg-gray-50 border border-gray-100 relative overflow-hidden" data-reveal="scale" style="transition-delay:120ms">
                <span class="text-4xl">📲</span>
                <h3 class="text-2xl font-extrabold mt-5 text-gray-900">Rent Number</h3>
                <p class="text-gray-500 mt-3 leading-relaxed">Instantly rent a verification number for WhatsApp, Telegram, Google and hundreds of other platforms — no SIM card required, delivered in seconds.</p>
                <ul class="mt-6 space-y-2 text-sm text-gray-600">
                    <li class="flex items-center gap-2">✓ One-time &amp; long-term numbers</li>
                    <li class="flex items-center gap-2">✓ Multiple countries supported</li>
                    <li class="flex items-center gap-2">✓ Real-time SMS delivery</li>
                </ul>
                <a href="/app/signup" class="inline-flex mt-7 px-6 py-3 rounded-full bg-gray-900 text-white font-bold text-sm group-hover:scale-105 transition-transform">Rent a Number →</a>
            </div>
        </div>
    </div>
</section>

{{-- ── ALL SERVICES ── --}}
<section class="py-24 bg-brand-light/40">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div data-reveal="left">
            <span class="text-brand font-bold text-sm uppercase tracking-wide">Everything in one place</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold mt-3 max-w-xl">All Your Digital Services in One Place</h2>
            <p class="text-gray-500 mt-4 max-w-xl">Top up, trade, pay bills and stay connected in seconds — everything you need, right at your fingertips.</p>
            <a href="{{ route('marketing.services') }}" class="inline-flex mt-6 px-6 py-3 rounded-full bg-gray-900 text-white font-bold text-sm hover:bg-brand transition-colors">See All Services</a>
        </div>

        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger">
            @foreach([
                ['🎁','Gift Cards','Buy and sell top gift card brands instantly, at great rates.', true],
                ['📲','Rent Number','Rent a verification number for any platform in seconds.', true],
                ['📶','Data Subscription','Fast, affordable data plans to keep you online.'],
                ['📞','Airtime Top-Up','Instantly recharge any network in seconds.'],
                ['⚡','Electricity','Pay your electricity bills and enjoy uninterrupted power.'],
                ['📺','TV/Cable','Renew your cable or satellite subscriptions in a few clicks.'],
                ['🛜','eSIM','Get connected without a physical SIM, activate instantly.'],
                ['🚀','Social Boost','Grow your social presence with real followers &amp; engagement.'],
                ['🎓','JAMB &amp; WAEC e-PINs','Official exam PINs, delivered instantly and verified.'],
            ] as $i => $row)
                @php [$icon, $title, $desc] = $row; $flagship = $row[3] ?? false; @endphp
                <div data-reveal style="--i:{{ $i }}" class="rounded-2xl p-6 bg-white border {{ $flagship ? 'border-brand/30 ring-1 ring-brand/10' : 'border-gray-100' }} hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-brand-light flex items-center justify-center text-2xl">{{ $icon }}</div>
                    <h3 class="font-bold mt-4">{{ $title }} @if($flagship)<span class="ml-1 text-[10px] font-bold text-brand bg-brand-light px-2 py-0.5 rounded-full align-middle">Popular</span>@endif</h3>
                    <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── WHY CHOOSE US ── --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="text-center max-w-xl mx-auto" data-reveal>
            <h2 class="text-3xl sm:text-4xl font-extrabold">Why Choose EasyGets?</h2>
            <p class="text-gray-500 mt-4">From instant delivery to unbeatable rates, we make every transaction simple and stress-free.</p>
        </div>
        <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-4 gap-6 stagger">
            @foreach([['01','Instant Delivery','Gift card codes and number rentals arrive in seconds, every time.'],['02','Trusted & Secure','Bank-level encryption and verified payment gateways protect every transaction.'],['03','Fair, Live Rates','Transparent gift card rates that update in real time — no surprises.'],['04','Always On','Our automated systems run round the clock, day or night.']] as $i => [$n,$t,$d])
                <div data-reveal style="--i:{{ $i }}" class="rounded-2xl p-6 border border-gray-100 hover:border-brand/30 transition-colors">
                    <span class="inline-flex w-9 h-9 rounded-lg bg-brand text-white items-center justify-center text-xs font-extrabold">{{ $n }}</span>
                    <h3 class="font-bold mt-4">{{ $t }}</h3>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── GET STARTED STEPS ── --}}
<section class="py-24 bg-[#06110d] text-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div data-reveal>
            <span class="text-emerald-400 font-bold text-sm uppercase tracking-wide">Get Started In Minutes</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold mt-3 max-w-xl">Join smart users already trading gift cards &amp; renting numbers on EasyGets</h2>
        </div>
        <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-5 stagger">
            @foreach([['👤','Create an Account','Join EasyGets and unlock instant access to all digital services.'],['🎯','Pick a Service','From gift cards to numbers, choose what you need in seconds.'],['🔒','Pay Securely','Use your wallet, card or bank transfer — seamless every time.'],['⚡','Enjoy Instant Delivery','No waiting, no stress. Everything delivered in seconds.']] as $i => [$icon,$t,$d])
                <div data-reveal style="--i:{{ $i }}" class="rounded-2xl p-6 bg-white/5 border border-white/10 hover:bg-brand/20 hover:border-brand/40 transition-colors">
                    <div class="text-3xl">{{ $icon }}</div>
                    <h3 class="font-bold mt-4">{{ $t }}</h3>
                    <p class="text-sm text-gray-400 mt-2 leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid md:grid-cols-3 gap-5">
            <div class="rounded-2xl p-6 bg-white/5 border border-white/10" data-reveal>
                <h3 class="font-bold">Proven Reliability</h3>
                <p class="text-sm text-gray-400 mt-2 leading-relaxed">We've helped users across Nigeria trade gift cards, rent numbers, and stay connected — fast, secure, without hassle.</p>
            </div>
            <div class="rounded-2xl p-6 bg-brand text-center" data-reveal style="transition-delay:100ms">
                <p class="text-3xl font-extrabold">4.9+ <span class="text-yellow-300">★★★★★</span></p>
                <p class="text-sm text-emerald-50/80 mt-2">Trusted by Nigerians for seamless, secure, instant access to digital services.</p>
            </div>
            <div class="rounded-2xl p-6 bg-white/5 border border-white/10 grid grid-cols-3 gap-2 text-center" data-reveal style="transition-delay:200ms">
                @foreach([['500+','Users'],['99.99%','Uptime'],['100%','Secure']] as [$n,$l])
                    <div><p class="font-extrabold">{{ $n }}</p><p class="text-[11px] text-gray-400">{{ $l }}</p></div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ── TESTIMONIALS ── --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="text-center max-w-xl mx-auto" data-reveal>
            <h2 class="text-3xl sm:text-4xl font-extrabold">What Our Users Say</h2>
            <p class="text-gray-500 mt-4">Trusted by people across Nigeria who count on EasyGets for speed, security, and convenience.</p>
        </div>
        <div class="mt-12 grid md:grid-cols-3 gap-6 stagger">
            @foreach([['Samuel Toluwani','Selling my gift cards on EasyGets is so smooth — I get paid fast and the rates are fair.'],['Mary Anne','I rent numbers here weekly for verifications. Instant delivery, every single time.'],['Chike Nobis','From gift cards to airtime, EasyGets has completely simplified how I handle everything online.']] as $i => [$name,$quote])
                <div data-reveal style="--i:{{ $i }}" class="rounded-2xl p-6 border border-gray-100">
                    <div class="text-yellow-400 text-sm">★★★★★</div>
                    <p class="text-sm text-gray-600 mt-3 leading-relaxed">"{{ $quote }}"</p>
                    <div class="flex items-center gap-3 mt-5">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand to-emerald-400"></div>
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
        <div class="mt-10 grid sm:grid-cols-2 gap-3" x-data="{open:null}">
            @foreach([
                ['How do I create an account?','Tap "Get Started", enter your details and verify your email or phone — you\'ll be trading in minutes.'],
                ['Can I sell any gift card?','We support a wide range of top brands. Open the Sell Gift Card page in-app to see live rates for your card.'],
                ['How fast are transactions?','Gift card purchases and number rentals are instant. Gift card sales are reviewed and paid out quickly once approved.'],
                ['Is EasyGets secure?','Yes — every transaction is encrypted, PIN-protected, and processed through verified payment gateways.'],
                ['What if my transaction fails?','No funds leave your wallet on a failed transaction. See our Refund Policy for full details.'],
                ['How do I contact support?','Reach us via live chat, WhatsApp, or the Contact Us page — our team responds promptly.'],
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
<section class="relative py-28 bg-gray-900 overflow-hidden">
    <div class="absolute inset-0 opacity-20" style="background:radial-gradient(600px 300px at 20% 20%, #03624C, transparent)"></div>
    <div class="relative max-w-3xl mx-auto px-5 text-center text-white" data-reveal="scale">
        <h2 class="text-3xl sm:text-4xl font-extrabold">Go Easy with EasyGets!</h2>
        <p class="mt-4 text-gray-300">Sign up today, trade gift cards, rent numbers, and enjoy lightning-fast transactions with total peace of mind.</p>
        <a href="/app/signup" class="inline-flex mt-8 px-8 py-4 rounded-full font-bold bg-brand hover:bg-emerald-400 transition-colors">Create an Account</a>
    </div>
</section>

@endsection