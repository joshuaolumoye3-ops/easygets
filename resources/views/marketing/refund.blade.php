@extends('layouts.marketing')
@section('title', 'Refund Policy — EasyGets')

@section('content')
<section class="bg-[#06110d] text-white py-20 text-center" data-reveal>
    <h1 class="text-4xl sm:text-5xl font-extrabold">Refund Policy</h1>
</section>

<section class="py-16 bg-white">
    <div class="max-w-3xl mx-auto px-5 space-y-10 text-gray-700 leading-relaxed" data-reveal>
        <p class="text-sm text-gray-400">Last Updated: November 2026</p>
        <p>At <strong>EasyGets</strong>, we aim to provide a smooth and reliable experience across gift card trading, number rentals, and bill payments. In rare cases where issues occur, this policy ensures fairness and transparency.</p>

        <div>
            <h2 class="text-xl font-bold text-gray-900">1. Non-Refundable Services</h2>
            <p class="mt-2">Due to the instant nature of our digital products (airtime, data, electricity tokens, e-pins, gift card codes, number rentals, etc.), all completed transactions are <strong>final and non-refundable</strong> once delivered successfully.</p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">2. Gift Card Submissions</h2>
            <p class="mt-2">A gift card submitted for sale that is rejected after review (for example, because it is invalid, already redeemed, or does not match the stated value) is not paid out. If photos or images were uploaded as part of a rejected submission, they are retained only for dispute and fraud-review purposes.</p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">3. Refund Eligibility</h2>
            <p class="mt-2">Refunds will only be issued if:</p>
            <ul class="mt-2 space-y-1.5 list-disc list-inside">
                <li>A payment was made but the service was <strong>not delivered</strong> due to a technical error.</li>
                <li>You were <strong>debited twice</strong> for the same transaction.</li>
                <li>The service delivered was <strong>incorrect</strong> due to an EasyGets system fault.</li>
            </ul>
        </div>

        <div class="rounded-2xl border-2 border-red-100 bg-red-50 p-6">
            <h2 class="text-xl font-bold text-red-700">4. No Refunds for Fraudulent or Prohibited Use</h2>
            <p class="mt-2 text-red-800">EasyGets does not sponsor, support, or refund transactions connected to fraudulent activity. Where a transaction is found to involve a stolen or fraudulently obtained gift card, unauthorized payment method, or use of a rented number to facilitate scams or illegal activity, no refund or payout will be issued, associated funds may be withheld or reversed, and the account will be reported to the relevant authorities.</p>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">5. Refund Process</h2>
            <p class="mt-2">If your case meets the conditions above:</p>
            <ul class="mt-2 space-y-1.5 list-disc list-inside">
                <li>Contact <a href="mailto:support@easygets.ng" class="text-brand font-semibold">support@easygets.ng</a> with your transaction details.</li>
                <li>Our team will verify and process the refund within <strong>24–72 hours</strong>.</li>
                <li>Refunds are credited to your EasyGets Wallet or original payment method.</li>
            </ul>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-900">6. Disputes</h2>
            <p class="mt-2">For unresolved issues, you may reach out to our support team via live chat or email. We strive to resolve all cases fairly and promptly.</p>
        </div>
    </div>
</section>
@endsection