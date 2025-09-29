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
            <input 
                id="txnSearch"
                type="text" 
                placeholder="Search transactions..." 
                style="padding:8px 16px; border-radius:8px; background:#2a2f3a; border:1px solid #3d414c; color:white; font-size:14px; width:100%; max-width:256px; outline:none;"
            >
        </div>

        <!-- Transactions Table -->
        <div style="background:#191c24; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.5); overflow-x:auto;">
            <table style="width:100%; font-size:14px; text-align:left; color:#9ca3af; border-collapse:collapse;">
                <thead style="text-transform:uppercase; font-size:12px; background:#111317; color:#6b7280;">
                    <tr>
                        <th style="padding:12px 16px;">Username</th>
                        <th style="padding:12px 16px;">Country</th>
                        <th style="padding:12px 16px;">Amount</th>
                        <th style="padding:12px 16px;">Status</th>
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
                                    background:{{ $txn->status === 'Success' ? '#16a34a' : ($txn->status === 'Pending' ? '#facc15' : '#dc2626') }}; 
                                    color:{{ $txn->status === 'Pending' ? '#000' : '#fff' }};">
                                    {{ $txn->status }}
                                </span>
                            </td>
                            <td style="padding:12px 16px;">{{ $txn->created_at->format('d M Y h:i A') }}</td>
                            <td style="padding:12px 16px;">
                                <button onclick="openTxnModal({{ $txn->id }})" 
                                    style="padding:4px 12px; background:#2563eb; border-radius:6px; color:white; font-size:12px; border:none; cursor:pointer;"
                                    onmouseover="this.style.background='#1d4ed8'" 
                                    onmouseout="this.style.background='#2563eb'">
                                    Manage
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:24px; color:#6b7280;">No ESIM transactions found.</td>
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

            <!-- Upload Section -->
            <h3 style="font-size:18px; font-weight:600; margin-bottom:12px; color:#4ade80;">Upload Details</h3>
            <form id="uploadForm" method="POST" action="{{ route('admin.esim.upload') }}" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:16px;">
                @csrf
                <input type="hidden" name="transaction_id" id="txnId">

                <div style="display:flex; flex-direction:column;">
                    <label style="font-size:14px; margin-bottom:4px;">Numbers</label>
                    <input type="text" name="numbers" 
                        style="width:100%; padding:8px 12px; border-radius:6px; background:#1f2937; border:1px solid #4b5563; color:white;">
                </div>

                <div style="display:flex; flex-direction:column;">
                    <label style="font-size:14px; margin-bottom:4px;">Details</label>
                    <textarea name="details" rows="3" 
                        style="width:100%; padding:8px 12px; border-radius:6px; background:#1f2937; border:1px solid #4b5563; color:white;"></textarea>
                </div>

                <div style="display:flex; flex-direction:column;">
                    <label style="font-size:14px; margin-bottom:4px;">Upload Images</label>
                    <input type="file" name="images[]" id="imageUpload" multiple accept="image/*"
                        style="width:100%; color:#d1d5db;">
                    <div id="preview" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:12px; margin-top:12px;"></div>
                </div>

                <button type="submit" 
                    style="width:100%; background:#16a34a; padding:10px; border-radius:8px; font-weight:600; border:none; color:white; cursor:pointer;"
                    onmouseover="this.style.background='#15803d'" 
                    onmouseout="this.style.background='#16a34a'">
                    Save Upload
                </button>
            </form>
        </div>
    </div>
</main>

<script>
function openTxnModal(txnId) {
    fetch(`/admin/esim/${txnId}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('txnId').value = txnId;
            document.getElementById('txnDetails').innerHTML = `
                <p><strong>User:</strong> (${data.username}) (${data.user.email})</p>
                <p><strong>Plan:</strong> ${data.recipient}</p>
                <p><strong>Amount:</strong> ₦${data.amount}</p>
                <p><strong>Status:</strong> ${data.status}</p>
                <p><strong>Date:</strong> ${data.created_at}</p>
            `;
            document.getElementById('txnModal').style.display = "flex";
        });
}

function closeTxnModal() {
    document.getElementById('txnModal').style.display = "none";
}

// Preview uploaded images
document.getElementById('imageUpload').addEventListener('change', function() {
    let preview = document.getElementById('preview');
    preview.innerHTML = '';
    Array.from(this.files).forEach(file => {
        let reader = new FileReader();
        reader.onload = e => {
            let img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = "100%";
            img.style.height = "112px";
            img.style.objectFit = "cover";
            img.style.borderRadius = "8px";
            img.style.border = "1px solid #4b5563";
            preview.appendChild(img);
        }
        reader.readAsDataURL(file);
    });
});
</script>
@endsection
