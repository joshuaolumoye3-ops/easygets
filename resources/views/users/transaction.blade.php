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

                // Build clean full image URLs
                $imageUrls = [];
                if ($txn->esimUpload) {
                    $rawImages = $txn->esimUpload->images;

                    if (is_string($rawImages)) {
                        $rawImages = json_decode($rawImages, true) ?? [];
                    }
                    $rawImages = is_array($rawImages) ? $rawImages : [];

                    foreach ($rawImages as $img) {
                        if (empty($img)) continue;

                        // Fix any escaped slashes from old JSON storage
                        $img = str_replace(['\\/', '\\'], '/', $img);
                        $img = ltrim($img, '/');

                        if (str_starts_with($img, 'storage/')) {
                            // "storage/esim/filename.png" → strip "storage/" → Storage::disk('public')->url(...)
                            $relativePath = substr($img, strlen('storage/'));
                            $imageUrls[] = \Illuminate\Support\Facades\Storage::disk('public')->url($relativePath);
                        } elseif (str_starts_with($img, 'uploads/')) {
                            // Legacy path in public folder
                            $imageUrls[] = asset($img);
                        } else {
                            // Fallback: assume it's a public disk relative path
                            $imageUrls[] = \Illuminate\Support\Facades\Storage::disk('public')->url($img);
                        }
                    }
                }

                $esimPayload = null;
                if ($txn->esimUpload) {
                    $esimPayload = [
                        'number'  => $txn->esimUpload->number,
                        'details' => $txn->esimUpload->details,
                        'images'  => $imageUrls,   // always full URLs, ready to use in <img src>
                    ];
                }

                $txnData = [
                    'id'          => $txn->id,
                    'type'        => $txn->type,
                    'title'       => $txn->title ?? ucfirst($txn->type),
                    'reference'   => $txn->reference,
                    'amount'      => $txn->amount,
                    'status'      => $txn->status,
                    'channel'     => $txn->channel,
                    'provider'    => $txn->provider,
                    'phone'       => $txn->phone,
                    'recipient'   => $txn->recipient,
                    'created_at'  => $txn->created_at->toISOString(),
                    'esim_upload' => $esimPayload,
                ];
            @endphp

            <div
                class="flex items-center justify-between p-5 border-b cursor-pointer hover:bg-gray-50 txn-row"
                data-transaction="{{ json_encode($txnData) }}"
            >
                <div class="flex items-center space-x-4">
                    <div class="text-green-800 text-xl">{{ $icon }}</div>
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

<!-- Full-screen image viewer -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center z-[60]">
    <button id="closeImageModal" class="absolute top-5 right-5 text-white hover:text-gray-300 text-4xl font-bold">&times;</button>
    <img id="fullScreenImage" src="" alt="" class="max-w-[90%] max-h-[90%] object-contain rounded-lg">
</div>

<!-- Transaction detail modal -->
<div id="txnModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl w-11/12 max-w-md p-6 relative animate-fadeIn">
        <button id="closeModal" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>

        <div class="flex items-center space-x-3 mb-4">
            <div id="txnIcon" class="text-3xl"></div>
            <div>
                <h2 id="txnTitle" class="text-lg font-bold text-gray-800"></h2>
                <p id="txnDate"  class="text-sm text-gray-500"></p>
            </div>
        </div>

        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="font-medium">Reference</span>
                <span id="txnReference" class="text-gray-600 text-right break-all max-w-[60%]"></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Amount</span>
                <span id="txnAmount" class="font-semibold text-gray-900"></span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Status</span>
                <span id="txnStatus" class="px-2 py-1 rounded-full text-xs"></span>
            </div>
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

            <!-- eSIM details (shown only for esim type with upload) -->
            <div id="esimDetails" class="space-y-3 mt-4 hidden">
                <hr class="border-gray-100">
                <p class="font-semibold text-green-700 text-sm">eSIM Details</p>
                <div class="flex justify-between">
                    <span class="font-medium">ICCID / Number</span>
                    <span id="esimNumber" class="text-gray-600 text-right break-all max-w-[60%]"></span>
                </div>
                <div>
                    <span class="font-medium block mb-1">Instructions</span>
                    <p id="esimUploadDetails" class="text-gray-600 text-sm leading-relaxed whitespace-pre-line"></p>
                </div>
                <div id="esimImagesWrap" class="hidden">
                    <span class="font-medium block mb-2">Images</span>
                    <div id="esimImages" class="flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>

        <div class="mt-6 text-right">
            <button id="closeFooter" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">Close</button>
        </div>
    </div>
</div>

<style>
.animate-fadeIn { animation: fadeIn .3s ease-in-out; }
@keyframes fadeIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
</style>

<script>
const modal       = document.getElementById('txnModal');
const imageModal  = document.getElementById('imageModal');

// ── open transaction detail ──────────────────────────────────
document.querySelectorAll('.txn-row').forEach(row => {
    row.addEventListener('click', () => {
        const txn = JSON.parse(row.dataset.transaction);

        const icons    = { airtime:'🌐', data:'🌐', electricity:'💡', betting:'⚽', esim:'📱', payment:'💰' };
        const statuses = { success:'bg-green-100 text-green-700', pending:'bg-yellow-100 text-yellow-700', failed:'bg-red-100 text-red-700' };

        document.getElementById('txnIcon').textContent      = icons[txn.type] || '🌐';
        document.getElementById('txnTitle').textContent     = txn.title || txn.type;
        document.getElementById('txnDate').textContent      = new Date(txn.created_at).toLocaleString();
        document.getElementById('txnReference').textContent = txn.reference || '-';
        document.getElementById('txnAmount').textContent    = '₦' + parseFloat(txn.amount || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 });
        document.getElementById('txnProvider').textContent  = txn.provider  || '-';
        document.getElementById('txnPhone').textContent     = txn.phone     || '-';
        document.getElementById('txnRecipient').textContent = txn.recipient || '-';

        const statusEl = document.getElementById('txnStatus');
        statusEl.textContent = txn.status;
        statusEl.className   = 'px-2 py-1 rounded-full text-xs ' + (statuses[(txn.status || '').toLowerCase()] || 'bg-gray-100 text-gray-700');

        // eSIM details block
        const esimBlock    = document.getElementById('esimDetails');
        const imagesWrap   = document.getElementById('esimImagesWrap');
        const imagesDiv    = document.getElementById('esimImages');

        if (txn.type && txn.type.toLowerCase() === 'esim' && txn.esim_upload) {
            const eu = txn.esim_upload;
            esimBlock.classList.remove('hidden');
            document.getElementById('esimNumber').textContent        = eu.number  || '-';
            document.getElementById('esimUploadDetails').textContent = eu.details || '-';

            imagesDiv.innerHTML = '';
            const imgs = Array.isArray(eu.images) ? eu.images : [];

            if (imgs.length > 0) {
                imagesWrap.classList.remove('hidden');
                imgs.forEach(url => {
                    if (!url) return;
                    const img = document.createElement('img');
                    img.src   = url;
                    img.style.cssText = 'width:64px; height:64px; object-fit:cover; border-radius:8px; cursor:pointer; border:1px solid #e5e7eb;';
                    img.addEventListener('click', () => {
                        document.getElementById('fullScreenImage').src = url;
                        imageModal.classList.remove('hidden');
                        imageModal.classList.add('flex');
                    });
                    img.onerror = () => img.style.display = 'none';
                    imagesDiv.appendChild(img);
                });
            } else {
                imagesWrap.classList.add('hidden');
            }
        } else {
            esimBlock.classList.add('hidden');
            imagesWrap.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
});

// ── close handlers ───────────────────────────────────────────
['closeModal', 'closeFooter'].forEach(id => {
    document.getElementById(id).addEventListener('click', closeModal);
});
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('closeImageModal').addEventListener('click', closeImgModal);
imageModal.addEventListener('click', e => { if (e.target === imageModal) closeImgModal(); });
function closeImgModal() {
    imageModal.classList.add('hidden');
    imageModal.classList.remove('flex');
    document.getElementById('fullScreenImage').src = '';
}
</script>
@endsection