@extends('layouts.app')
@section('title', 'Reset Password')
@section('content')
<div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-white" style="font-family: 'Inter', sans-serif;">
    <div class="w-full max-w-sm mx-auto">
        <div class="flex justify-center mb-6">
            <img src="/easygets-logo.jpg" alt="Logo" class="w-36 h-36 rounded-full" />
        </div>
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Reset Password</h1>
            <p class="mt-2 text-sm text-gray-600">Set a new password for <strong>{{ $user->email }}</strong></p>
        </div>
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-600">{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
            @csrf
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                <input type="password" id="password" name="password" required minlength="8" class="w-full px-4 py-3 text-base bg-white text-gray-800 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#03624C] focus:outline-none transition-all" placeholder="New password">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" class="w-full px-4 py-3 text-base bg-white text-gray-800 rounded-xl border border-gray-300 focus:ring-2 focus:ring-[#03624C] focus:outline-none transition-all" placeholder="Retype password">
            </div>
            <button type="submit" class="w-full py-3 text-base font-semibold text-white bg-[#03624C] rounded-xl hover:bg-opacity-90 transition-all duration-300">Reset Password</button>
            <div class="text-center pt-4">
                <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-800 transition-colors">Back to Sign In</a>
            </div>
        </form>
    </div>
</div>
@endsection
