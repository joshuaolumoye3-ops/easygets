@extends('layouts.admin')

@section('title', 'ESIM Transactions')

@section('content')
<main style="padding-top:70px; width:100%; display:flex; flex-direction:column;">
    <div style="padding:24px; width:100%; flex-grow:1;">

        <!-- Page Header -->
        <div style="display:flex; flex-direction:column; justify-content:space-between; align-items:center; background:#191c24; padding:16px; border-radius:16px; box-shadow:0 2px 6px rgba(0,0,0,0.5); margin-bottom:24px;">
            <h1 style="font-size:18px; font-weight:bold; color:white; margin-bottom:16px;">
                ESIM Transactions
            </h1>
            <a href="/app/admin/esim" 
                class="inline-block px-4 py-2 rounded-lg bg-green hover:bg-green-700 text-white font-medium text-sm transition duration-200 text-center w-full max-w-xs">
                Manage eSIM Plans
            </a>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div style="background:#22c55e; color:white; padding:12px 16px; border-radius:8px; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background:#ef4444; color:white; padding:12px 16px; border-radius:8px; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Transactions Table -->
        <div style="background:#191c24; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.5); overflow-x:auto;">
            <table style="width:100%; font-size:14px; text-align:left; color:#9ca3af; border-collapse:collapse;">
                <thead style="text-transform:uppercase; font-size:12px; background:#111317; color:#6b7280;">
                    <tr>
                        <th style="padding:12px 16px;">Username</th>
                        <th style="padding:12px 16px;">Country</th>
                        <th style="padding:12px 16px;">Amount</th>
                        <th style="padding:12px 16px;">Status</th>
                        <th style="padding:12px 16px;">Upload Status</th>
                        <th style="padding:12px 16px;">Date</th>
                        <th style="padding:12px 16px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                        <tr onclick="openTxnModal({{ $txn->id }})" 
                            style="border-bottom:1px solid #374151; cursor:pointer;" 
                            onmouseover="this.style.background='#1f2937'" 
                            onmouseout="this.style.background='transparent'">
                            <td style="padding:12px 16px;">{{ $txn->user->username ?? 'N/A' }}</td>
                            <td style="padding:12px 16px;">{{ $txn->recipient ?? '-' }}</td>
                            <td style="padding:12px 16px;">₦{{ number_format($txn->amount, 2) }}</td>
                            <td style="padding:12px 16px;">
                                <span style="padding:4px 8px; border-radius:6px; font-size:12px; font-weight:500; 
                                    background:{{ $txn->status === 'Success' ? '#16a34a' : ($txn->status === 'Pending' ? '#03624C' : '#FFC107') }}; 
                                    color:{{ $txn->status === 'Pending' ? '#000' : '#fff' }};">
                                    {{ $txn->status }}
                                </span>
                            </td>
                            <td style="padding:12px 16px;">
                                @if($txn->esimUpload)
                                    <span class="badge-approved" style="padding:4px 8px; border-radius:6px; font-size:12px; font-weight:500;">
                                        ✓ Uploaded
                                    </span>
                                @else
                                    <span class="badge-not-uploaded" style="padding:4px 8px; border-radius:6px; font-size:12px; font-weight:500;">
                                        Not Uploaded
                                    </span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;">{{ $txn->created_at->format('d M Y h:i A') }}</td>
                            <td style="padding:12px 16px;">
                                <button onclick="event.stopPropagation(); openTxnModal({{ $txn->id }})" 
                                    style="padding:4px 12px; background:#03624C; border-radius:6px; color:white; font-size:12px; border:none; cursor:pointer;"
                                    onmouseover="this.style.background='#1d4ed8'" 
                                    onmouseout="this.style.background='#2563eb'">
                                    Manage
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:24px; color:#6b7280;">No ESIM transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div id="txnModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:50; background:rgba(0,0,0,0.7);">
        <div style="background:#121417; color:white; border-radius:8px; width:100%; max-width:640px; max-height:90vh; overflow-y:auto; padding:24px; position:relative;">

            <!-- Close -->
            <button onclick="closeTxnModal()" style="position:absolute; top:12px; right:16px; color:#9ca3af; font-size:24px; border:none; background:none; cursor:pointer;" 
                onmouseover="this.style.color='#22c55e'" 
                onmouseout="this.style.color='#9ca3af'">&times;</button>

            <h2 style="font-size:20px; font-weight:bold; color:#22c55e; margin-bottom:16px; border-bottom:1px solid #15803d; padding-bottom:8px;">
                ESIM Transaction Details
            </h2>

            <!-- Transaction Info -->
            <div id="txnDetails" style="margin-bottom:24px;"></div>

            <!-- Upload Status Banner -->
            <div id="uploadStatusBanner" style="display:none; margin-bottom:16px; padding:12px; border-radius:8px; text-align:center; font-weight:600;">
            </div>

            <!-- Existing Upload Details (Read-only preview) -->
            <div id="existingUploadSection" style="display:none; margin-bottom:24px;"></div>

            <!-- Upload Section -->
            <h3 id="uploadSectionTitle" style="font-size:18px; font-weight:600; margin-bottom:12px; color:#4ade80;">Upload eSIM Details</h3>
            <form id="uploadForm" method="POST" action="{{ route('admin.esim.upload') }}" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:16px;">
                @csrf
                <input type="hidden" name="transaction_id" id="txnId">
                <input type="hidden" name="esim_upload_id" id="esimUploadId">

                <div style="display:flex; flex-direction:column;">
                    <label style="font-size:14px; margin-bottom:4px; color:#9ca3af;">Numbers / ICCID</label>
                    <input type="text" name="numbers" id="uploadNumbers"
                        placeholder="Enter phone numbers or ICCID"
                        style="width:100%; padding:8px 12px; border-radius:6px; background:#1f2937; border:1px solid #4b5563; color:white;">
                </div>

                <div style="display:flex; flex-direction:column;">
                    <label style="font-size:14px; margin-bottom:4px; color:#9ca3af;">Details / Instructions</label>
                    <textarea name="details" id="uploadDetails" rows="3" 
                        placeholder="Enter eSIM activation details, instructions, or notes"
                        style="width:100%; padding:8px 12px; border-radius:6px; background:#1f2937; border:1px solid #4b5563; color:white;"></textarea>
                </div>

                <div style="display:flex; flex-direction:column;">
                    <label style="font-size:14px; margin-bottom:4px; color:#9ca3af;">
                        Upload Images (QR Codes, Instructions, etc.)
                    </label>
                    <input type="file" name="images[]" id="imageUpload" multiple accept="image/*"
                        style="width:100%; color:#d1d5db;">
                    
                    <!-- Existing Images Display -->
                    <div id="existingImagesGrid" style="display:none; margin-top:12px;">
                        <p style="font-size:12px; color:#9ca3af; margin-bottom:8px;">Existing Images:</p>
                        <div id="existingImages" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:12px;"></div>
                    </div>
                    
                    <!-- New Images Preview -->
                    <div id="preview" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:12px; margin-top:12px;"></div>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="button" onclick="clearForm()" 
                        style="flex:1; background:#374151; padding:10px; border-radius:8px; font-weight:600; border:none; color:white; cursor:pointer;"
                        onmouseover="this.style.background='#4b5563'" 
                        onmouseout="this.style.background='#374151'">
                        Clear Form
                    </button>
                    <button type="submit" id="submitBtn"
                        style="flex:2; background:#16a34a; padding:10px; border-radius:8px; font-weight:600; border:none; color:white; cursor:pointer;"
                        onmouseover="this.style.background='#15803d'" 
                        onmouseout="this.style.background='#16a34a'">
                        Save Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Full Screen Image Modal -->
    <div id="imageModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:60; background:rgba(0,0,0,0.95);">
        <button onclick="closeImageModal()" style="position:absolute; top:20px; right:20px; color:white; font-size:36px; border:none; background:none; cursor:pointer; z-index:61;"
            onmouseover="this.style.color='#22c55e'" 
            onmouseout="this.style.color='white'">&times;</button>
        <img id="fullScreenImage" src="" alt="Full Screen" style="max-width:90%; max-height:90%; object-fit:contain;">
    </div>
</main>

<style>
    /* eSIM upload badge styles */
    .badge-approved { background:#22c55e; color:white; }
    .badge-not-uploaded { background:#6b7280; color:white; }
</style>

<script>
let currentTransactionData = null;

function openTxnModal(txnId) {
    fetch(`/app/admin/esim/transaction/${txnId}`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            console.log('Transaction data:', data); // Debug log
            
            if (!data.success) {
                alert('Failed to load eSIM details: ' + (data.message || 'Unknown error'));
                return;
            }

            const txn = data.transaction;
            const esim = data.esim;
            const hasUpload = data.hasExistingUpload;
            currentTransactionData = data;

            document.getElementById('txnId').value = txn.id;
            document.getElementById('esimUploadId').value = esim?.id || '';

            // Transaction details
            document.getElementById('txnDetails').innerHTML = `
                <div style="display:grid; gap:8px; background:#1f2937; padding:12px; border-radius:8px; margin-bottom:16px;">
                    <p><strong style="color:#9ca3af;">User:</strong> ${txn.username} (${txn.user_email})</p>
                    <p><strong style="color:#9ca3af;">Reference:</strong> ${txn.reference}</p>
                    <p><strong style="color:#9ca3af;">Amount:</strong> <span style="color:#22c55e; font-weight:600;">₦${parseFloat(txn.amount).toLocaleString()}</span></p>
                    <p><strong style="color:#9ca3af;">Status:</strong> 
                        <span style="padding:2px 8px; border-radius:4px; background:${txn.status === 'Success' ? '#16a34a' : '#FFC107'}; color:${txn.status === 'Success' ? 'white' : 'black'}; font-size:12px;">
                            ${txn.status}
                        </span>
                    </p>
                    <p><strong style="color:#9ca3af;">Channel:</strong> ${txn.channel}</p>
                    <p><strong style="color:#9ca3af;">Provider:</strong> ${txn.provider}</p>
                    <p><strong style="color:#9ca3af;">Phone:</strong> ${txn.phone}</p>
                    <p><strong style="color:#9ca3af;">Recipient:</strong> ${txn.recipient}</p>
                    <p><strong style="color:#9ca3af;">Date:</strong> ${txn.created_at}</p>
                </div>
            `;

            // Upload status banner
            const banner = document.getElementById('uploadStatusBanner');
            const submitBtn = document.getElementById('submitBtn');
            const sectionTitle = document.getElementById('uploadSectionTitle');
            
            if (hasUpload) {
                banner.style.display = 'block';
                banner.style.background = '#22c55e';
                banner.style.color = 'white';

                // If transaction is marked Success, show Approved wording to emphasize green approval
                if (txn.status === 'Success') {
                    banner.innerHTML = '✓ eSIM Details Approved';
                } else {
                    banner.innerHTML = '✓ eSIM Details Already Uploaded - Edit Below to Update';
                }

                submitBtn.textContent = 'Update Upload';
                sectionTitle.textContent = 'Edit eSIM Details';

                // Populate form with existing data
                document.getElementById('uploadNumbers').value = esim.number || '';
                document.getElementById('uploadDetails').value = esim.details || '';

                // Display existing upload info
                const existingSection = document.getElementById('existingUploadSection');
                existingSection.style.display = 'block';
                existingSection.innerHTML = `
                    <div style="background:#1f2937; padding:12px; border-radius:8px; border:2px solid #22c55e;">
                        <h4 style="font-size:14px; font-weight:600; color:#22c55e; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Current Upload Details
                        </h4>
                        <div style="font-size:13px; color:#d1d5db; margin-top:8px;">
                            <p style="margin-bottom:4px;"><strong style="color:#9ca3af;">Uploaded:</strong> ${esim.created}</p>
                            ${esim.updated !== esim.created ? `<p style="margin-bottom:4px;"><strong style="color:#9ca3af;">Last Updated:</strong> ${esim.updated}</p>` : ''}
                        </div>
                    </div>
                `;

                // Display existing images
                if (esim.images && esim.images.length > 0) {
                    const imagesGrid = document.getElementById('existingImagesGrid');
                    const imagesContainer = document.getElementById('existingImages');
                    imagesGrid.style.display = 'block';
                    imagesContainer.innerHTML = esim.images.map((img, idx) => `
                        <div style="position:relative;">
                            <img src="${img}" alt="eSIM Image ${idx + 1}"
                                style="width:100%; height:112px; object-fit:cover; border-radius:8px; border:2px solid #22c55e; cursor:pointer;"
                                onclick="document.getElementById('fullScreenImage').src='${img}'; document.getElementById('imageModal').style.display='flex';">
                            <div style="position:absolute; top:4px; left:4px; background:#22c55e; color:white; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold;">
                                Saved ${idx + 1}
                            </div>
                        </div>
                    `).join('');
                }
            } else {
                banner.style.display = 'block';
                banner.style.background = '#f59e0b';
                banner.style.color = '#000';
                banner.innerHTML = '⚠ No eSIM Details Uploaded Yet - Please Upload Below';
                submitBtn.textContent = 'Save Upload';
                sectionTitle.textContent = 'Upload eSIM Details';
                document.getElementById('existingUploadSection').style.display = 'none';
                document.getElementById('existingImagesGrid').style.display = 'none';
                clearForm();
            }

            document.getElementById('txnModal').style.display = "flex";
        })
        .catch(error => {
            console.error('Error fetching transaction:', error);
            alert('Failed to load transaction details: ' + error.message);
        });
}

function clearForm() {
    document.getElementById('uploadNumbers').value = '';
    document.getElementById('uploadDetails').value = '';
    document.getElementById('imageUpload').value = '';
    document.getElementById('preview').innerHTML = '';
}

function closeTxnModal() {
    document.getElementById('txnModal').style.display = "none";
    clearForm();
    document.getElementById('existingImagesGrid').style.display = 'none';
    currentTransactionData = null;
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = "none";
}

// Image preview on upload
document.getElementById('imageUpload').addEventListener('change', function() {
    let preview = document.getElementById('preview');
    preview.innerHTML = '';

    if (this.files.length > 0) {
        Array.from(this.files).forEach((file, index) => {
            let reader = new FileReader();
            reader.onload = e => {
                const imgContainer = document.createElement('div');
                imgContainer.style.position = 'relative';

                let img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = "100%";
                img.style.height = "112px";
                img.style.objectFit = "cover";
                img.style.borderRadius = "8px";
                img.style.border = "2px solid #3b82f6";

                const label = document.createElement('div');
                label.textContent = `New ${index + 1}`;
                label.style.position = 'absolute';
                label.style.top = '4px';
                label.style.left = '4px';
                label.style.background = '#3b82f6';
                label.style.color = 'white';
                label.style.padding = '2px 6px';
                label.style.borderRadius = '4px';
                label.style.fontSize = '10px';
                label.style.fontWeight = 'bold';

                imgContainer.appendChild(img);
                imgContainer.appendChild(label);
                preview.appendChild(imgContainer);
            };
            reader.readAsDataURL(file);
        });
    }
});

// Form submission validation
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const numbers = document.getElementById('uploadNumbers').value.trim();
    const details = document.getElementById('uploadDetails').value.trim();
    const newImages = document.getElementById('imageUpload').files.length;
    const hasExisting = currentTransactionData?.hasExistingUpload;

    if (!hasExisting && !numbers && !details && newImages === 0) {
        e.preventDefault();
        alert('Please fill in at least one field or upload an image');
        return false;
    }

    const action = hasExisting ? 'update' : 'save';
    if (!confirm(`Are you sure you want to ${action} these eSIM details?`)) {
        e.preventDefault();
        return false;
    }

    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    submitBtn.style.background = '#6b7280';
});

// Close modals by clicking outside or pressing Escape
document.getElementById('txnModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeTxnModal();
});

document.getElementById('imageModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeImageModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (document.getElementById('imageModal').style.display === 'flex') closeImageModal();
        else if (document.getElementById('txnModal').style.display === 'flex') closeTxnModal();
    }
});
</script>

<style>
/* Smooth animations */
#txnModal > div, #imageModal > img {
    animation: fadeInScale 0.3s ease-out;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Input focus styles */
input:focus, textarea:focus {
    outline: none;
    border-color: #22c55e !important;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

/* Scrollbar styling for modal */
#txnModal > div::-webkit-scrollbar {
    width: 8px;
}

#txnModal > div::-webkit-scrollbar-track {
    background: #1f2937;
    border-radius: 4px;
}

#txnModal > div::-webkit-scrollbar-thumb {
    background: #4b5563;
    border-radius: 4px;
}

#txnModal > div::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}
</style>
@endsection