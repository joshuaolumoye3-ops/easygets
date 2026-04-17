@extends('layouts.admin')

@section('title', 'ESIM Transactions')

@section('content')
<main style="padding-top:70px; width:100%; display:flex; flex-direction:column; background:#0f1115; min-height:100vh;">

    <div style="padding:30px 28px; width:100%; flex-grow:1;">

        <!-- Header -->
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:15px; background:#1a1d23; padding:20px; border-radius:14px; box-shadow:0 8px 20px rgba(0,0,0,0.25); margin-bottom:25px;">
            
            <h1 style="font-size:22px; font-weight:700; color:#fff;">
                ESIM Transactions
            </h1>

            <a href="/app/admin/esim"
                style="background:#22c55e; padding:10px 18px; border-radius:8px; color:#fff; font-weight:600; text-decoration:none; font-size:14px; transition:0.2s;"
                onmouseover="this.style.opacity='0.85'"
                onmouseout="this.style.opacity='1'">
                Manage eSIM Plans
            </a>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div style="background:#16a34a; padding:12px 16px; border-radius:10px; color:white; margin-bottom:15px; display:flex; align-items:center; gap:10px;">
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
                            <th style="padding:14px;">Country</th>
                            <th style="padding:14px;">Amount</th>
                            <th style="padding:14px;">Status</th>
                            <th style="padding:14px;">Upload</th>
                            <th style="padding:14px;">Date</th>
                            <th style="padding:14px;">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($transactions as $txn)
                        <tr onclick="openTxnModal({{ $txn->id }})"
                            style="border-top:1px solid rgba(255,255,255,0.05); cursor:pointer;"
                            onmouseover="this.style.background='#222630'"
                            onmouseout="this.style.background='transparent'">

                            <td style="padding:14px;">{{ $txn->user->username ?? 'N/A' }}</td>
                            <td style="padding:14px;">{{ $txn->recipient ?? '-' }}</td>
                            <td style="padding:14px; color:#22c55e; font-weight:600;">
                                ₦{{ number_format($txn->amount, 2) }}
                            </td>

                            <td style="padding:14px;">
                                <span style="
                                    padding:4px 10px;
                                    border-radius:6px;
                                    font-size:12px;
                                    font-weight:600;
                                    background:{{ $txn->status === 'Success' ? '#16a34a' : ($txn->status === 'Pending' ? '#f59e0b' : '#ef4444') }};
                                    color:white;">
                                    {{ $txn->status }}
                                </span>
                            </td>

                            <td style="padding:14px;">
                                <span style="
                                    padding:4px 10px;
                                    border-radius:6px;
                                    font-size:12px;
                                    font-weight:600;
                                    background:{{ $txn->esimUpload ? '#22c55e' : '#6b7280' }};
                                    color:white;">
                                    {{ $txn->esimUpload ? 'Uploaded' : 'Not Uploaded' }}
                                </span>
                            </td>

                            <td style="padding:14px; color:#9ca3af;">
                                {{ $txn->created_at->format('d M Y h:i A') }}
                            </td>

                            <td style="padding:14px;">
                                <button onclick="event.stopPropagation(); openTxnModal({{ $txn->id }})"
                                    style="background:#3b82f6; padding:6px 12px; border-radius:6px; color:#fff; font-size:12px; border:none; cursor:pointer;"
                                    onmouseover="this.style.opacity='0.85'"
                                    onmouseout="this.style.opacity='1'">
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

    <!-- MODAL -->
    <div id="txnModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.8); backdrop-filter:blur(4px); z-index:50; padding:20px;">

        <div style="background:linear-gradient(135deg, #1a1d23 0%, #222630 100%); width:100%; max-width:750px; max-height:90vh; overflow-y:auto; border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,0.6); position:relative; border:1px solid rgba(255,255,255,0.1);">

            <!-- Header -->
            <div style="background:linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding:24px; border-radius:16px 16px 0 0; display:flex; justify-content:space-between; align-items:center;">
                <h2 style="font-size:24px; font-weight:700; color:white; margin:0;">
                    Transaction Details
                </h2>
                <button onclick="closeTxnModal()"
                    style="position:absolute; top:16px; right:16px; font-size:28px; color:white; border:none; background:rgba(255,255,255,0.2); width:40px; height:40px; border-radius:8px; cursor:pointer; transition:all 0.3s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    ×
                </button>
            </div>

            <!-- Content -->
            <div style="padding:28px;">

                <!-- Transaction Details Grid -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
                    <div style="background:rgba(255,255,255,0.05); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <p style="font-size:12px; color:#9ca3af; margin:0 0 8px 0; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">User</p>
                        <p style="font-size:16px; font-weight:700; color:#22c55e; margin:0;" id="txnUser">-</p>
                    </div>
                    <div style="background:rgba(255,255,255,0.05); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <p style="font-size:12px; color:#9ca3af; margin:0 0 8px 0; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Amount</p>
                        <p style="font-size:16px; font-weight:700; color:#60a5fa; margin:0;" id="txnAmount">-</p>
                    </div>
                    <div style="background:rgba(255,255,255,0.05); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08);">
                        <p style="font-size:12px; color:#9ca3af; margin:0 0 8px 0; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Status</p>
                        <p style="font-size:16px; font-weight:700; color:#fbbf24; margin:0;" id="txnStatus">-</p>
                    </div>
                </div>

                <!-- Reference & Details -->
                <div style="background:rgba(255,255,255,0.05); padding:16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); margin-bottom:28px;">
                    <p style="font-size:12px; color:#9ca3af; margin:0 0 12px 0; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Reference ID</p>
                    <p style="font-size:14px; font-weight:600; color:#e5e7eb; margin:0; word-break:break-all;" id="txnReference">-</p>
                </div>

                <!-- Upload Section Header -->
                <h3 style="font-size:18px; font-weight:700; color:#fff; margin:0 0 16px 0; display:flex; align-items:center; gap:8px;">
                    <span style="width:4px; height:4px; background:#22c55e; border-radius:50%; display:inline-block;"></span>
                    Upload eSIM Details
                </h3>

                <!-- Upload Form -->
                <form id="uploadForm" method="POST" action="{{ route('admin.esim.upload') }}" enctype="multipart/form-data"
                    style="display:flex; flex-direction:column; gap:16px;">
                    @csrf

                    <input type="hidden" name="transaction_id" id="txnId">

                    <!-- Numbers / ICCID -->
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="font-size:13px; color:#e5e7eb; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Numbers / ICCID</label>
                        <input type="text" name="numbers" id="uploadNumbers"
                            placeholder="Enter phone numbers or ICCID codes"
                            style="padding:12px 16px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#fff; font-size:14px; transition:all 0.3s;"
                            onfocus="this.style.borderColor='rgba(34,197,94,0.5)'; this.style.background='rgba(34,197,94,0.05)'"
                            onblur="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.background='rgba(255,255,255,0.05)'">
                    </div>

                    <!-- Details -->
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="font-size:13px; color:#e5e7eb; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Instructions / Details</label>
                        <textarea name="details" id="uploadDetails" rows="4"
                            placeholder="Add any special instructions or details about this upload"
                            style="padding:12px 16px; border-radius:10px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#fff; font-size:14px; resize:vertical; transition:all 0.3s; font-family:inherit;"
                            onfocus="this.style.borderColor='rgba(34,197,94,0.5)'; this.style.background='rgba(34,197,94,0.05)'"
                            onblur="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.background='rgba(255,255,255,0.05)'"></textarea>
                    </div>

                    <!-- File Upload -->
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label style="font-size:13px; color:#e5e7eb; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Attach Images/Files</label>
                        <div style="padding:16px; border:2px dashed rgba(34,197,94,0.3); border-radius:10px; background:rgba(34,197,94,0.05); text-align:center;">
                            <input type="file" name="images[]" id="fileInput" multiple
                                style="display:none;"
                                onchange="previewImages(event)">
                            <button type="button" onclick="document.getElementById('fileInput').click()"
                                style="background:none; border:none; color:#22c55e; cursor:pointer; font-weight:600; font-size:14px;">
                                📎 Click to upload or drag files
                            </button>
                            <p style="font-size:12px; color:#9ca3af; margin:8px 0 0 0;">PNG, JPG, PDF up to 10MB each</p>
                        </div>
                    </div>

                    <!-- Image Preview -->
                    <div id="imagePreview" style="display:none; flex-direction:column; gap:12px;">
                        <p style="font-size:12px; color:#9ca3af; margin:0; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Preview</p>
                        <div id="previewGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:12px;"></div>
                    </div>

                    <!-- Buttons -->
                    <div style="display:flex; gap:12px; margin-top:20px;">
                        <button type="button" onclick="clearForm()"
                            style="flex:1; background:rgba(255,255,255,0.1); padding:12px 20px; border-radius:10px; color:#e5e7eb; border:1px solid rgba(255,255,255,0.2); font-weight:600; font-size:14px; cursor:pointer; transition:all 0.3s;"
                            onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.borderColor='rgba(255,255,255,0.3)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.borderColor='rgba(255,255,255,0.2)'">
                            Clear Form
                        </button>

                        <button type="submit"
                            style="flex:1.5; background:linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding:12px 20px; border-radius:10px; color:white; border:none; font-weight:600; font-size:14px; cursor:pointer; box-shadow:0 4px 12px rgba(34,197,94,0.3); transition:all 0.3s;"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(34,197,94,0.4)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(34,197,94,0.3)'">
                            ✓ Save Upload
                        </button>
                    </div>
                </form>

            </div>

        </div>
    </div>

</main>

<script>
function openTxnModal(id){
    fetch(`{{ route('admin.esim.transaction.show', '') }}/${id}`)
    .then(res=>res.json())
    .then(data=>{
        console.log('Transaction Data Received:', data);
        
        const txn = data.transaction;
        const esim = data.esim;
        
        document.getElementById('txnId').value = txn.id;
        
        // Populate transaction details
        document.getElementById('txnUser').textContent = txn.username || 'N/A';
        document.getElementById('txnAmount').textContent = '₦' + (txn.amount ? Number(txn.amount).toLocaleString('en-NG', {minimumFractionDigits: 2}) : '0.00');
        document.getElementById('txnStatus').textContent = txn.status || 'Pending';
        document.getElementById('txnReference').textContent = txn.reference || 'N/A';
        
        // Populate form with existing upload data if available
        if (esim) {
            console.log('eSIM Upload Data:', esim);
            document.getElementById('uploadNumbers').value = esim.number || '';
            document.getElementById('uploadDetails').value = esim.details || '';
            
            // Show existing images
            if (esim.images && esim.images.length > 0) {
                console.log('Images Found:', esim.images);
                document.getElementById('imagePreview').style.display = 'flex';
                const previewGrid = document.getElementById('previewGrid');
                previewGrid.innerHTML = '';
                
                esim.images.forEach((imgUrl, index) => {
                    console.log(`Loading image ${index}:`, imgUrl);
                    const previewItem = document.createElement('div');
                    previewItem.style.cssText = `
                        position: relative;
                        border-radius: 10px;
                        overflow: hidden;
                        border: 1px solid rgba(34,197,94,0.3);
                        aspect-ratio: 1;
                        background: rgba(255,255,255,0.05);
                    `;
                    
                    const img = document.createElement('img');
                    img.src = imgUrl;
                    img.onerror = function() {
                        console.error('Failed to load image:', imgUrl);
                        this.style.display = 'none';
                    };
                    img.onload = function() {
                        console.log('Image loaded successfully:', imgUrl);
                    };
                    img.style.cssText = `
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    `;
                    
                    previewItem.appendChild(img);
                    previewGrid.appendChild(previewItem);
                });
            } else {
                console.log('No images found in eSIM data');
                document.getElementById('imagePreview').style.display = 'none';
                document.getElementById('previewGrid').innerHTML = '';
            }
        } else {
            console.log('No eSIM upload data found');
            document.getElementById('uploadNumbers').value = '';
            document.getElementById('uploadDetails').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('previewGrid').innerHTML = '';
        }
        
        // Clear file input
        document.getElementById('fileInput').value = '';
        
        // Show modal
        document.getElementById('txnModal').style.display = 'flex';
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to load transaction details');
    });
}

function closeTxnModal(){
    document.getElementById('txnModal').style.display = 'none';
    clearForm();
}

function clearForm(){
    document.getElementById('uploadNumbers').value = '';
    document.getElementById('uploadDetails').value = '';
    document.getElementById('fileInput').value = '';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('previewGrid').innerHTML = '';
}

// Handle form submission
document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const txnId = document.getElementById('txnId').value;
    const uploadUrl = form.action; // Use form's action attribute which has the route URL
    
    console.log('Submitting eSIM upload form for transaction:', txnId);
    console.log('Upload URL:', uploadUrl);
    console.log('Form data:', {
        transaction_id: formData.get('transaction_id'),
        numbers: formData.get('numbers'),
        details: formData.get('details'),
        has_files: formData.getAll('images[]').length > 0,
        file_count: formData.getAll('images[]').length
    });
    
    fetch(uploadUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        console.log('Response status:', res.status);
        return res.json().then(data => ({status: res.status, data}));
    })
    .then(({status, data}) => {
        console.log('Upload response:', status, data);
        
        if (data.success) {
            alert('eSIM details uploaded successfully!');
            closeTxnModal();
            // Reload the page to refresh the transaction list
            window.location.reload();
        } else {
            alert('Failed to upload: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Upload error:', err);
        alert('Error uploading eSIM details: ' + err.message);
    });
});

function previewImages(event){
    const files = event.target.files;
    const previewContainer = document.getElementById('imagePreview');
    const previewGrid = document.getElementById('previewGrid');
    
    // Clear previous previews
    previewGrid.innerHTML = '';
    
    if (files.length === 0) {
        previewContainer.style.display = 'none';
        return;
    }
    
    previewContainer.style.display = 'flex';
    
    Array.from(files).forEach(file => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.style.cssText = `
                position: relative;
                border-radius: 10px;
                overflow: hidden;
                border: 1px solid rgba(34,197,94,0.3);
                aspect-ratio: 1;
                background: rgba(255,255,255,0.05);
            `;
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = `
                width: 100%;
                height: 100%;
                object-fit: cover;
            `;
            
            const removeBtn = document.createElement('button');
            removeBtn.innerHTML = '×';
            removeBtn.type = 'button';
            removeBtn.style.cssText = `
                position: absolute;
                top: 4px;
                right: 4px;
                width: 28px;
                height: 28px;
                border-radius: 6px;
                border: none;
                background: rgba(239,68,68,0.9);
                color: white;
                cursor: pointer;
                font-size: 18px;
                font-weight: bold;
                transition: all 0.2s;
            `;
            
            removeBtn.onmouseover = function() {
                this.style.background = 'rgba(239,68,68,1)';
            };
            removeBtn.onmouseout = function() {
                this.style.background = 'rgba(239,68,68,0.9)';
            };
            
            removeBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Remove from FileList is not possible, so we'll just hide this preview
                previewItem.style.display = 'none';
            };
            
            previewItem.appendChild(img);
            previewItem.appendChild(removeBtn);
            previewGrid.appendChild(previewItem);
        };
        
        reader.readAsDataURL(file);
    });
}
</script>
@endsection