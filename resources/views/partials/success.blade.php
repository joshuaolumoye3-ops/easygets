@props([
    'title' => 'Success!',
    'message' => 'Operation completed successfully.',
    'details' => []
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 p-6 bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl shadow-sm animate-fade-in']) }}>
    <!-- Success Icon and Title -->
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-bold text-green-800">{{ $title }}</h3>
            <p class="mt-1 text-sm text-green-700 leading-relaxed">{{ $message }}</p>
        </div>
    </div>

    <!-- Transaction Details -->
    @if(!empty($details))
        <div class="bg-white rounded-lg p-4 border border-green-100 shadow-sm">
            <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Transaction Details
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

    <!-- Success Footer -->
    <div class="flex items-center justify-center pt-2">
        <div class="flex items-center text-xs text-brand-green">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Transaction completed securely
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