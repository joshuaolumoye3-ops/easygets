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
                    'electricity' => '💡',
                    'betting'     => '⚽',
                    'esim'        => '📱',
                    'payment'     => '💰',
                ];
                $icon = $icons[$txn->type] ?? '🌐';

                $statusColors = [
                    'success' => 'bg-green-100 text-green-700',
                    'pending' => 'bg-yellow-100 text-yellow-700',
                    'failed'  => 'bg-red-100 text-red-700',
                ];
                $statusColor = $statusColors[strtolower($txn->status)] ?? 'bg-gray-100 text-gray-700';

                // Prepare image URLs (normalize stored formats) to avoid complex inline closures
                $imageUrls = null;
                if ($txn->esimUpload) {
                    $imagesRaw = $txn->esimUpload->images;
                    if (is_array($imagesRaw)) {
                        $imageUrls = array_map(function($i) { return asset($i); }, $imagesRaw);
                    } elseif (is_string($imagesRaw)) {
                        $decoded = json_decode($imagesRaw, true) ?? [];
                        $imageUrls = array_map(function($i) { return asset($i); }, $decoded);
                    } else {
                        $imageUrls = [];
                    }
                }
            @endphp

            <div 
                class="flex items-center justify-between p-5 border-b cursor-pointer hover:bg-gray-50 txn-row"
                data-transaction='@json(array_merge($txn->toArray(), [
                    "esim_upload" => $txn->esimUpload ? array_merge($txn->esimUpload->toArray(), [
                        'image_urls' => $imageUrls
                    ]) : null
                ]))'
            >
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
                        <span class="w-2 h-2 rounded-full {{ explode(' ', $statusColor)[0] }}"></span>
                        <span class="capitalize">{{ $txn->status }}</span>
                    </p>
                </div>
            </div>
        @endforeach
    @empty
        <p class="text-sm text-gray-500 text-center">No transactions yet.</p>
    @endforelse
</div>

<!-- Image Full Screen Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center z-[60]">
    <button id="closeImageModal" class="absolute top-5 right-5 text-white hover:text-gray-300 text-4xl font-bold">&times;</button>
    <img id="fullScreenImage" src="" alt="Full Screen" class="max-w-[90%] max-h-[90%] object-contain">
</div>

<!-- Transaction Details Modal -->
<div id="txnModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl w-11/12 max-w-md p-6 relative animate-fadeIn">
        <button id="closeModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>

        <div class="flex items-center space-x-3 mb-4">
            <div id="txnIcon" class="text-3xl"></div>
            <div>
                <h2 id="txnTitle" class="text-lg font-bold text-gray-800"></h2>
                <p id="txnDate" class="text-sm text-gray-500"></p>
            </div>
        </div>

        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="font-medium">Reference</span>
                <span id="txnReference" class="text-gray-600"></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Amount</span>
                <span id="txnAmount" class="font-semibold text-gray-900"></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Status</span>
                <span id="txnStatus" class="px-2 py-1 rounded-full text-xs"></span>
            </div>
            {{-- <div class="flex justify-between">
                <span class="font-medium">Channel</span>
                <span id="txnChannel" class="text-gray-600"></span>
            </div> --}}
            <div class="flex justify-between">
                <span class="font-medium">Provider</span>
                <span id="txnProvider" class="text-gray-600"></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Phone</span>
                <span id="txnPhone" class="text-gray-600"></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Recipient</span>
                <span id="txnRecipient" class="text-gray-600"></span>
            </div>
            <div id="esimDetails" class="space-y-2 mt-4 hidden">
                <div class="font-semibold text-green-700">eSIM Details</div>
                <div><span class="font-medium">Number:</span> <span id="esimNumber"></span></div>
                <div><span class="font-medium">Details:</span> <span id="esimUploadDetails"></span></div>
                <div id="esimImages" class="flex flex-wrap gap-2"></div>
            </div>
        </div>

        <div class="mt-6 text-right">
            <button id="closeFooter" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Close</button>
        </div>
    </div>
</div>

<style>
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    const modal = document.getElementById('txnModal');
    const closeModal = document.getElementById('closeModal');
    const closeFooter = document.getElementById('closeFooter');
    const rows = document.querySelectorAll('.txn-row');

    const txnIcon = document.getElementById('txnIcon');
    const txnTitle = document.getElementById('txnTitle');
    const txnDate = document.getElementById('txnDate');
    const txnReference = document.getElementById('txnReference');
    const txnAmount = document.getElementById('txnAmount');
    const txnStatus = document.getElementById('txnStatus');
    //const txnChannel = document.getElementById('txnChannel');
    const txnProvider = document.getElementById('txnProvider');
    const txnPhone = document.getElementById('txnPhone');
    const txnRecipient = document.getElementById('txnRecipient');

    rows.forEach(row => {
        row.addEventListener('click', () => {
            const txn = JSON.parse(row.dataset.transaction);

            const icons = {
                airtime: '🌐',
                data: '🌐',
                electricity: '💡',
                betting: '⚽',
                esim: '📱',
                payment: '💰'
            };

            const statuses = {
                success: 'bg-green-100 text-green-700',
                pending: 'bg-yellow-100 text-yellow-700',
                failed: 'bg-red-100 text-red-700'
            };

            txnIcon.textContent = icons[txn.type] || '🌐';
            txnTitle.textContent = txn.title ?? txn.type;
            txnDate.textContent = new Date(txn.created_at).toLocaleString();
            txnReference.textContent = txn.reference;
            txnAmount.textContent = `₦${parseFloat(txn.amount).toFixed(2)}`;

            txnStatus.textContent = txn.status;
            txnStatus.className = `px-2 py-1 rounded-full text-xs ${statuses[txn.status.toLowerCase()] || 'bg-gray-100 text-gray-700'}`;

            //txnChannel.textContent = txn.channel ?? '-';
            txnProvider.textContent = txn.provider ?? '-';
            txnPhone.textContent = txn.phone ?? '-';
            txnRecipient.textContent = txn.recipient ?? '-';

            // eSIM details logic
            const esimDetailsDiv = document.getElementById('esimDetails');
            if (txn.type.toLowerCase() === 'esim' && txn.esim_upload) {
                esimDetailsDiv.classList.remove('hidden');
                document.getElementById('esimNumber').textContent = txn.esim_upload.number ?? '-';
                document.getElementById('esimUploadDetails').textContent = txn.esim_upload.details ?? '-';

                // Images with full-screen functionality
                const imagesDiv = document.getElementById('esimImages');
                imagesDiv.innerHTML = '';

                // Prefer full URLs if available (image_urls), otherwise handle images field (string or array)
                let imgs = txn.esim_upload.image_urls ?? txn.esim_upload.images ?? [];

                if (typeof imgs === 'string') {
                    try {
                        imgs = JSON.parse(imgs);
                    } catch (e) {
                        imgs = [imgs];
                    }
                }

                if (Array.isArray(imgs)) {
                    imgs.forEach(img => {
                        const imageElem = document.createElement('img');
                        const src = (typeof img === 'string' && (img.startsWith('http://') || img.startsWith('https://')))
                            ? img
                            : ('/' + img.replace(/^\//, ''));

                        imageElem.src = src;
                        imageElem.style.width = '60px';
                        imageElem.style.borderRadius = '6px';
                        imageElem.style.cursor = 'pointer';
                        imageElem.addEventListener('click', () => {
                            document.getElementById('fullScreenImage').src = imageElem.src;
                            document.getElementById('imageModal').classList.remove('hidden');
                            document.getElementById('imageModal').classList.add('flex');
                        });
                        imagesDiv.appendChild(imageElem);
                    });
                }
            } else {
                esimDetailsDiv.classList.add('hidden');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    [closeModal, closeFooter].forEach(el => {
        el.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });

    // Image modal handlers
    const imageModal = document.getElementById('imageModal');
    const closeImageModal = document.getElementById('closeImageModal');

    closeImageModal.addEventListener('click', () => {
        imageModal.classList.add('hidden');
        imageModal.classList.remove('flex');
    });

    imageModal.addEventListener('click', (e) => {
        if (e.target === imageModal) {
            imageModal.classList.add('hidden');
            imageModal.classList.remove('flex');
        }
    });
</script>
@endsection