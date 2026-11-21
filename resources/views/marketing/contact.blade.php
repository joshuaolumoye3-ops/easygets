@extends('layouts.marketing')
@section('title', 'Contact Us — EasyGets')

@section('content')
<section class="bg-[#06110d] text-white py-24 text-center">
    <div class="max-w-3xl mx-auto px-5" data-reveal>
        <h1 class="text-4xl sm:text-5xl font-extrabold">Contact Us</h1>
        <p class="text-gray-400 mt-4 text-lg">We would like to hear from you</p>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-2xl mx-auto px-5" data-reveal>
        <h2 class="text-2xl font-extrabold text-center">Send Us A Message</h2>
        <p class="text-center text-sm text-gray-500 mt-2">WhatsApp: 0802 274 6033 &nbsp;|&nbsp; 0802 274 6033</p>

        @if(session('success'))
            <div class="mt-6 bg-brand-light text-brand text-sm font-semibold rounded-xl p-4 text-center">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('marketing.contact.submit') }}" class="mt-8 space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Enter Your First Name" required
                       class="w-full px-5 py-3.5 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand">
                <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Enter Your Last Name" required
                       class="w-full px-5 py-3.5 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand">
            </div>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email Address" required
                   class="w-full px-5 py-3.5 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand">
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone Number" required
                   class="w-full px-5 py-3.5 rounded-full border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand">
            <textarea name="message" rows="4" placeholder="Kindly let us know your Enquiry" required
                      class="w-full px-5 py-4 rounded-2xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand">{{ old('message') }}</textarea>
            @foreach(['first_name','last_name','email','phone','message'] as $f)
                @error($f)<p class="text-xs text-red-500 -mt-2">{{ $message }}</p>@enderror
            @endforeach
            <button type="submit" class="w-full py-4 rounded-full bg-gray-900 text-white font-bold hover:bg-brand transition-colors">SUBMIT ENQUIRY</button>
        </form>
    </div>
</section>
@endsection