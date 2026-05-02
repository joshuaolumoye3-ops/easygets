@props([
    'title' => 'Transaction Failed',
    'message' => 'The operation could not be completed.',
    'errorType' => 'error',
    'details' => [],
    'suggestion' => null
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 p-6 bg-gradient-to-br from-red-50 to-rose-50 border border-red-200 rounded-xl shadow-sm animate-fade-in']) }}>
    <!-- Error Icon and Title -->
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                @if($errorType === 'warning')
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4v2m0 4v2M9 3h6a2 2 0 012 2v18a2 2 0 01-2 2H9a2 2 0 01-2-2V5a2 2 0 012-2z" />
                    </svg>
                @else
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4v2m0 4v2M9 8a3 3 0 106 0V6a3 3 0 00-6 0v2zm0 0H6a3 3 0 00-3 3v6a3 3 0 003 3h12a3 3 0 003-3V8a3 3 0 00-3-3h-3V6a3 3 0 00-6 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l12 6-12 6V9z" />
                    </svg>
                @endif
            </div>
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-bold {{ $errorType === 'warning' ? 'text-amber-800' : 'text-red-800' }}">{{ $title }}</h3>
            <p class="mt-1 text-sm {{ $errorType === 'warning' ? 'text-amber-700' : 'text-red-700' }} leading-relaxed">{{ $message }}</p>
        </div>
    </div>

    <!-- Error Details -->
    @if(!empty($details))
        <div class="bg-white rounded-lg p-4 border {{ $errorType === 'warning' ? 'border-amber-100' : 'border-red-100' }} shadow-sm">
            <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-2 {{ $errorType === 'warning' ? 'text-amber-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Details
            </h4>
            <div class="space-y-2">
                @foreach($details as $key => $value)
                    <div class="flex justify-between items-center py-1">
                        <span class="text-sm text-gray-600">{{ $key }}:</span>
                        <span class="text-sm font-semibold text-gray-800 text-right ml-2">{{ $value }}</span>
                    </div>
                    @if(!$loop->last)
                        <hr class="border-gray-100">
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- Suggestion/Help Text -->
    @if($suggestion)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-blue-700">{{ $suggestion }}</p>
            </div>
        </div>
    @endif

    <!-- Safety Message -->
    <div class="flex items-center justify-center pt-2">
        <div class="flex items-center text-xs {{ $errorType === 'warning' ? 'text-amber-600' : 'text-red-600' }}">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Your wallet balance was not affected by this transaction
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from { 
        opacity: 0; 
        transform: translateY(-10px) scale(0.95); 
    }
    to   { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
}

@keyframes fade-out {
    from { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
    to   { 
        opacity: 0; 
        transform: translateY(-10px) scale(0.95); 
    }
}

.animate-fade-in {
    animation: fade-in 0.4s ease-out forwards;
}

.animate-fade-out {
    animation: fade-out 0.3s ease-in forwards;
}
</style>
