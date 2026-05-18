@extends('layouts.app')

@section('title', 'Page Expired')

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-white" style="font-family: 'Inter', sans-serif;">
    <div class="w-full max-w-lg mx-auto p-6 bg-white shadow rounded-xl border border-gray-200 text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Page Expired</h1>
        <p class="text-sm text-gray-600 mb-6">It looks like your session has expired or the form has been open too long.</p>
        <p class="text-sm text-gray-600 mb-6">Please refresh the page and try again. If the issue persists, clear your browser cache or try again in a new tab.</p>
        <div class="space-x-4">
            <a href="{{ url()->current() }}" class="inline-block px-6 py-3 bg-[#03624C] text-white rounded-xl hover:bg-[#024f3c] transition-colors">Refresh Page</a>
            <a href="{{ route('login') }}" class="inline-block px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors">Back to Login</a>
        </div>
    </div>
</div>
@endsection
