@extends('layouts.app')

@section('title', 'Transactions History')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] bg-white mt-0 pt-10">
    <div class="flex items-center space-x-2 ps-5 mb-6">
        <a href="dashboard">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        </a>
        
        <h1 class="text-lg font-semibold text-center flex-1">Transactions History</h1>
    </div>

    @forelse ($transactions as $date => $txns)
        <div class="bg-gray-100 text-gray-600 text-xs py-1 px-3 rounded-full mb-3">{{ $date }}</div>

        @foreach ($txns as $txn)
            @php
                $icons = [
                    'airtime'     => '🌐',
                    'data'        => '🌐',
                    'electricity' => '🌐',
                    'betting'     => '⚽',
                    'esim'        => '📱',
                    'payment'     => '💰',
                ];
                $icon = $icons[$txn->type] ?? '🌐';

                $statusColors = [
                    'success' => 'bg-green-600',
                    'pending' => 'bg-yellow-400',
                    'failed'  => 'bg-red-500',
                ];
                $statusColor = $statusColors[$txn->status] ?? 'bg-gray-400';
            @endphp

            <div class="flex items-center justify-between p-5 border-b">
                <div class="flex items-center space-x-4">
                    <div class="text-green-800 text-xl">
                        {{ $icon }}
                    </div>
                    <div>
                        <p class="text-sm font-medium">{{ $txn->title ?? ucfirst($txn->type) }}</p>
                        <p class="text-xs text-gray-500">{{ $txn->created_at->format('h:i A') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold {{ $txn->type === 'payment' ? 'text-green-600' : 'text-black' }}">
                        {{ $txn->type === 'payment' ? '+' : '-' }}₦{{ number_format($txn->amount, 2) }}
                    </p>
                    <p class="flex items-center justify-end text-xs text-gray-500 space-x-1 mt-1">
                        <span class="w-2 h-2 rounded-full {{ $statusColor }}"></span>
                        <span class="capitalize">{{ $txn->status }}</span>
                    </p>
                </div>
            </div>
        @endforeach
    @empty
        <p class="text-sm text-gray-500 text-center">No transactions yet.</p>
    @endforelse
</div>
@endsection
