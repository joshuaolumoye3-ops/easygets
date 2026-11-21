@extends('layouts.marketing')
@section('title', 'Privacy Policy — EasyGets')

@section('content')
<section class="bg-[#06110d] text-white py-20 text-center" data-reveal>
    <h1 class="text-4xl sm:text-5xl font-extrabold">Privacy Policy</h1>
</section>

<section class="py-16 bg-white">
    <div class="max-w-3xl mx-auto px-5 space-y-10 text-gray-700 leading-relaxed" data-reveal>
        <p class="text-sm text-gray-400">Last Updated: November 2026</p>
        <p>At <strong>EasyGets</strong>, your privacy is our priority. We are committed to protecting your personal information and being transparent about how we handle it.</p>

        <div>
            <h2 class="text-xl font-bold text-gray-900">1. Information We Collect</h2>
            <ul class="mt-2 space-y-1.5 list-disc list-inside">
                <li>Personal details such as name, email, and phone number.</li>
                <li>Transactional information, such as gift card trades, number rentals, payments, and purchase history.</li>
                <li>Verification details required to review a gift card submission (such as card images or codes).</li>
                <li>Technical data like IP address and browser type, used for analytics and fraud prevention.</li>
            </ul>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">2. How We Use Your Information</h2>
            <ul class="mt-2 space-y-1.5 list-disc list-inside">
                <li>Process your transactions and deliver services.</li>
                <li>Verify gift card submissions and detect fraudulent activity.</li>
                <li>Communicate with you regarding updates, offers, or support.</li>
                <li>Improve our services and protect our users and platform.</li>
            </ul>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">3. Data Security</h2>
            <p class="mt-2">We use encryption, firewalls, PIN-protected transactions, and secure payment gateways to protect your information. However, no online system is 100% secure; you share data at your own discretion.</p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">4. Fraud Prevention &amp; Reporting</h2>
            <p class="mt-2">We may retain transaction records, submitted gift card details, and account activity logs for as long as necessary to detect, investigate, and prevent fraud. Where required by law or a valid request from a regulatory or law enforcement authority, we may share relevant information to support an investigation into suspected fraud or illegal activity.</p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">5. Cookies</h2>
            <p class="mt-2">Our site may use cookies to enhance your browsing experience. You can disable cookies in your browser settings at any time.</p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">6. Third-Party Services</h2>
            <p class="mt-2">We may share limited information with trusted partners (e.g., payment processors and gift card verification providers) strictly for service delivery purposes.</p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">7. Your Rights</h2>
            <p class="mt-2">You have the right to access, correct, or request deletion of your personal data. Contact <a href="mailto:support@easygets.ng" class="text-brand font-semibold">support@easygets.ng</a> for any privacy concerns.</p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">8. Updates to Policy</h2>
            <p class="mt-2">This policy may be updated occasionally. The latest version will always be available on our website.</p>
        </div>
    </div>
</section>
@endsection