@extends('layouts.admin')

@section('title', 'ESIM Transactions')

@section('content')
<main style="padding-top:70px; width:100%; display:flex; flex-direction:column; background:#0f1115; min-height:100vh;">

    <div style="padding:30px 28px; width:100%; flex-grow:1;">

        <!-- Header -->
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:15px; background:#1a1d23; padding:20px; border-radius:14px; box-shadow:0 8px 20px rgba(0,0,0,0.25); margin-bottom:25px;">
            <h1 style="font-size:22px; font-weight:700; color:#fff;">ESIM Transactions</h1>
            <a href="/app/admin/esim"
                style="background:#22c55e; padding:10px 18px; border-radius:8px; color:#fff; font-weight:600; text-decoration:none; font-size:14px;"
                onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                Manage eSIM Plans
            </a>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div style="background:#16a34a; padding:12px 16px; border-radius:10px; color:white; margin-bottom:15px;">
                ✓ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="background:#ef4444; padding:12px 16px; border-radius:10px; color:white; margin-bottom:15px;">
                ⚠ {{ session('error') }}
            </div>
        @endif

        <!-- Table -->
        <div style="background:#1a1d23; border-radius:14px; overflow:hidden; box-shadow:0 8px 20px rgba(0,0,0,0.25);">
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; color:#e5e7eb; font-size:14px;">
                    <thead style="background:#222630;">
                        <tr>
                            <th style="padding:14px;">User</th>
                            <th style="padding:14px;">Country / Plan</th>
                            <th style="padding:14px;">Amount</th>
                            <th style="padding:14px;">Status</th>
                            <th style="padding:14px;">Upload</th>
                            <th style="padding:14px;">Date</th>
                            <th style="padding:14px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                        <tr style="border-top:1px solid rgba(255,255,255,0.05); cursor:pointer;"
                            onmouseover="this.style.background='#222630'"
                            onmouseout="this.style.background='transparent'">

                            <td style="padding:14px;">{{ $txn->user->username ?? 'N/A' }}</td>
                            <td style="padding:14px;">{{ $txn->recipient ?? '-' }}</td>
                            <td style="padding:14px; color:#22c55e; font-weight:600;">
                                ₦{{ number_format($txn->amount, 2) }}
                            </td>
                            <td style="padding:14px;">
                                <span style="padding:4px 10px; border-radius:6px; font-size:12px; font-weight:600;
                                    background:{{ $txn->status === 'Success' ? '#16a34a' : ($txn->status === 'Pending' ? '#f59e0b' : '#ef4444') }};
                                    color:white;">
                                    {{ $txn->status }}
                                </span>
                            </td>
                            <td style="padding:14px;">
                                <span style="padding:4px 10px; border-radius:6px; font-size:12px; font-weight:600;
                                    background:{{ $txn->esimUpload ? '#22c55e' : '#6b7280' }}; color:white;">
                                    {{ $txn->esimUpload ? 'Uploaded' : 'Not Uploaded' }}
                                </span>
                            </td>
                            <td style="padding:14px; color:#9ca3af;">
                                {{ $txn->created_at->format('d M Y h:i A') }}
                            </td>
                            <td style="padding:14px;">
                                <button onclick="openTxnModal({{ $txn->id }})"
                                    style="background:#3b82f6; padding:6px 12px; border-radius:6px; color:#fff; font-size:12px; border:none; cursor:pointer;"
                                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                                    Manage
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:30px; color:#6b7280;">
                                No ESIM transactions found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========== MODAL ========== -->
    <div id="txnModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.8); backdrop-filter:blur(4px); z-index:50; padding:20px;">
        <div style="background:linear-gradient(135deg,#1a1d23 0%,#222630 100%); width:100%; max-width:750px; max-height:90vh; overflow-y:auto; border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,0.6); position:relative; border:1px solid rgba(255,255,255,0.1);">

            <!-- Header -->
            <div style="background:linear-gradient(135deg,#22c55e 0%,#16a34a 100%); padding:24px; border-radius:16px 16px 0 0; display:flex; justify-content:space-between; align-items:center;">
                <h2 style="font-size:24px; font-weight:700; color:white; margin:0;">Transaction Details</h2>
                <button onclick="closeTxnModal()"
                    style="position:absolute; top:16px; right:16px; font-size:28px; color:white; border:none; background:rgba(255,255,255,0.2); width:40px; height:40px; border-radius:8px; cursor:pointer;"
                    onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.2)'">×</button>
            </div>

            <!-- Content -->
            <div style="padding:28px;">

                <!-- Transaction info grid -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:16px; margin-bottom:28px;">
                    <div style="background:rgba(255,255,255,0.05); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <p style="font-size:12px; color:#9ca3af; margin:0 0 8px; text-transform:uppercase; font-weight:600;">User</p>
                        <p style="font-size:16px; font-weight:700; color:#22c55e; margin:0;" id="txnUser">-</p>
                    </div>
                    <div style="background:rgba(255,255,255,0.05); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <p style="font-size:12px; color:#9ca3af; margin:0 0 8px; text-transform:uppercase; font-weight:600;">Amount</p>
                        <p style="font-size:16px; font-weight:700; color:#60a5fa; margin:0;" id="txnAmount">-</p>
                    </div>
                    <div style="background:rgba(255,255,255,0.05); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <p style="font-size:12px; color:#9ca3af; margin:0 0 8px; text-transform:uppercase; font-weight:600;">Status</p>
                        <p style="font-size:16px; font-weight:700; color:#fbbf24; margin:0;" id="txnStatus">-</p>
                    </div>
                </div>

                <!-- Reference -->
                <div style="background:rgba(255,255,255,0.05); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); margin-bottom:28px;">
                    <p style="font-size:12px; color:#9ca3af; margin:0 0 12px; text-transform:uppercase; font-weight:600;">Reference ID</p>
                    <p style="font-size:14px; font-weight:600; color:#e5e7eb; margin:0; word-break:break-all;" id="txnReference">-</p>
                </div>

                <!-- Existing images (if any) -->
                <div id="existingImagesSection" style="display:none; margin-bottom:24px;">
                    <p style="font-size:13px; color:#9ca3af; margin:0 0 10px; text-transform:uppercase; font-weight:600;">Existing Uploaded Images</p>
                    <div id="existingImagesGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(110px,1fr)); gap:10px;"></div>
                </div>

                <!-- Upload form -->
                <h3 style="font-size:18px; font-weight:700; color:#fff; margin:0 0 16px;">Upload eSIM Details</h3>
                <form id="uploadForm" method="POST" action="{{ route('admin.esim.upload') }}" enctype="multipart/form-data"
                    style="display:flex; flex-direction:column; gap:16px;">
                    @csrf
                    <input type="hidden" name="transaction_id" id="txnId">

                    <!-- Numbers / ICCID -->
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="font-size:13px; color:#e5e7eb; font-weight:600; text-transform:uppercase;">Numbers / ICCID</label>
                        <input type="text" name="numbers" id="uploadNumbers"
                            placeholder="Enter phone numbers or ICCID codes"
                            style="padding:12px 16px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#fff; font-size:14px;"
                            onfocus="this.style.borderColor='rgba(34,197,94,0.5)'; this.style.background='rgba(34,197,94,0.05)'"
                            onblur="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.background='rgba(255,255,255,0.05)'">
                    </div>

                    <!-- Details / Instructions -->
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="font-size:13px; color:#e5e7eb; font-weight:600; text-transform:uppercase;">Instructions / Details</label>
                        <textarea name="details" id="uploadDetails" rows="4"
                            placeholder="Add any special instructions or details"
                            style="padding:12px 16px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#fff; font-size:14px; resize:vertical; font-family:inherit;"
                            onfocus="this.style.borderColor='rgba(34,197,94,0.5)'; this.style.background='rgba(34,197,94,0.05)'"
                            onblur="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.background='rgba(255,255,255,0.05)'"></textarea>
                    </div>

                    <!-- File upload -->
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="font-size:13px; color:#e5e7eb; font-weight:600; text-transform:uppercase;">Add Images</label>
                        <div style="padding:16px; border:2px dashed rgba(34,197,94,0.3); border-radius:10px; background:rgba(34,197,94,0.05); text-align:center;">
                            <input type="file" name="images[]" id="fileInput" multiple accept="image/*"
                                style="display:none;" onchange="previewNewImages(event)">
                            <button type="button" onclick="document.getElementById('fileInput').click()"
                                style="background:none; border:none; color:#22c55e; cursor:pointer; font-weight:600; font-size:14px;">
                                📎 Click to attach images
                            </button>
                            <p style="font-size:12px; color:#9ca3af; margin:8px 0 0;">PNG, JPG up to 5 MB each</p>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="font-size:13px; color:#e5e7eb; font-weight:600; text-transform:uppercase;">Image or resource link</label>
                        <input type="url" name="link" id="uploadLink" placeholder="https://example.com/esim-image-or-setup-link"
                            style="padding:12px 16px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#fff; font-size:14px;"
                            onfocus="this.style.borderColor='rgba(34,197,94,0.5)'; this.style.background='rgba(34,197,94,0.05)'"
                            onblur="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.background='rgba(255,255,255,0.05)'">
                    </div>

                    <!-- New image preview -->
                    <div id="newImagePreview" style="display:none; flex-direction:column; gap:12px;">
                        <p style="font-size:12px; color:#9ca3af; margin:0; text-transform:uppercase; font-weight:600;">New images to upload</p>
                        <div id="newPreviewGrid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px;"></div>
                    </div>

                    <!-- Buttons -->
                    <div style="display:flex; gap:12px; margin-top:8px;">
                        <button type="button" onclick="clearForm()"
                            style="flex:1; background:rgba(255,255,255,0.1); padding:12px 20px; border-radius:10px; color:#e5e7eb; border:1px solid rgba(255,255,255,0.2); font-weight:600; font-size:14px; cursor:pointer;"
                            onmouseover="this.style.background='rgba(255,255,255,0.15)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                            Clear Form
                        </button>
                        <button type="submit" id="submitBtn"
                            style="flex:1.5; background:linear-gradient(135deg,#22c55e 0%,#16a34a 100%); padding:12px 20px; border-radius:10px; color:white; border:none; font-weight:600; font-size:14px; cursor:pointer; box-shadow:0 4px 12px rgba(34,197,94,0.3);"
                            onmouseover="this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                            ✓ Save Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</main>

<!-- Full-screen image viewer -->
<div id="imgViewer" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.95); z-index:100; align-items:center; justify-content:center;">
    <button onclick="closeViewer()" style="position:absolute; top:20px; right:24px; background:none; border:none; color:#fff; font-size:36px; cursor:pointer; line-height:1;">×</button>
    <img id="viewerImg" src="" alt="" style="max-width:90vw; max-height:90vh; object-fit:contain; border-radius:8px;">
</div>

<script>
// ─── helpers ────────────────────────────────────────────────
function openViewer(src) {
    document.getElementById('viewerImg').src = src;
    const v = document.getElementById('imgViewer');
    v.style.display = 'flex';
}
function closeViewer() {
    document.getElementById('imgViewer').style.display = 'none';
    document.getElementById('viewerImg').src = '';
}
document.getElementById('imgViewer').addEventListener('click', function(e) {
    if (e.target === this) closeViewer();
});

function makeThumb(src) {
    const wrap = document.createElement('div');
    wrap.style.cssText = 'position:relative; border-radius:10px; overflow:hidden; border:1px solid rgba(34,197,94,0.3); aspect-ratio:1; background:rgba(255,255,255,0.05); cursor:pointer;';
    const img = document.createElement('img');
    img.src = src;
    img.style.cssText = 'width:100%; height:100%; object-fit:cover;';
    img.onerror = () => { wrap.style.display = 'none'; };
    img.addEventListener('click', () => openViewer(src));
    wrap.appendChild(img);
    return wrap;
}

// ─── open modal ─────────────────────────────────────────────
function openTxnModal(id) {
    fetch(`{{ route('admin.esim.show', '') }}/${id}`)
        .then(r => r.json())
        .then(data => {
            const txn  = data.transaction;
            const esim = data.esim;

            document.getElementById('txnId').value        = txn.id;
            document.getElementById('txnUser').textContent = txn.username || 'N/A';
            document.getElementById('txnAmount').textContent =
                '₦' + Number(txn.amount || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 });
            document.getElementById('txnStatus').textContent    = txn.status || 'Pending';
            document.getElementById('txnReference').textContent = txn.reference || 'N/A';

            // Populate form fields with existing data
            document.getElementById('uploadNumbers').value = esim ? (esim.number  || '') : '';
            document.getElementById('uploadDetails').value = esim ? (esim.details || '') : '';
            document.getElementById('uploadLink').value = esim ? (esim.link || '') : '';

            // Show existing images
            const existingSection = document.getElementById('existingImagesSection');
            const existingGrid    = document.getElementById('existingImagesGrid');
            existingGrid.innerHTML = '';

            if (esim && esim.images && esim.images.length > 0) {
                existingSection.style.display = 'block';
                esim.images.forEach(url => existingGrid.appendChild(makeThumb(url)));
            } else {
                existingSection.style.display = 'none';
            }

            // Reset new-upload preview
            document.getElementById('fileInput').value      = '';
            document.getElementById('newImagePreview').style.display = 'none';
            document.getElementById('newPreviewGrid').innerHTML = '';

            document.getElementById('txnModal').style.display = 'flex';
        })
        .catch(err => {
            console.error('Modal load error:', err);
            alert('Failed to load transaction details. Please try again.');
        });
}

function closeTxnModal() {
    document.getElementById('txnModal').style.display = 'none';
    clearForm();
}

function clearForm() {
    document.getElementById('uploadNumbers').value = '';
    document.getElementById('uploadDetails').value = '';
    document.getElementById('uploadLink').value = '';
    document.getElementById('fileInput').value     = '';
    document.getElementById('newImagePreview').style.display = 'none';
    document.getElementById('newPreviewGrid').innerHTML = '';
}

// ─── new-image preview ───────────────────────────────────────
function previewNewImages(event) {
    const files       = Array.from(event.target.files);
    const previewWrap = document.getElementById('newImagePreview');
    const grid        = document.getElementById('newPreviewGrid');
    grid.innerHTML    = '';

    if (!files.length) { previewWrap.style.display = 'none'; return; }

    previewWrap.style.display = 'flex';

    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative; border-radius:10px; overflow:hidden; border:1px solid rgba(34,197,94,0.3); aspect-ratio:1; background:rgba(255,255,255,0.05);';
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:100%; height:100%; object-fit:cover;';
            wrap.appendChild(img);
            grid.appendChild(wrap);
        };
        reader.readAsDataURL(file);
    });
}

// ─── form submit via AJAX ────────────────────────────────────
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const btn = document.getElementById('submitBtn');
    btn.disabled    = true;
    btn.textContent = 'Saving…';

    fetch(this.action, {
        method: 'POST',
        body:   new FormData(this),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('eSIM details saved successfully!');
            closeTxnModal();
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Network error: ' + err.message))
    .finally(() => {
        btn.disabled    = false;
        btn.textContent = '✓ Save Upload';
    });
});
</script>
@endsection