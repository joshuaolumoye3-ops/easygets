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
    <div id="txnModal" style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.75); z-index:50;">

        <div style="background:#121417; width:100%; max-width:700px; max-height:90vh; overflow-y:auto; border-radius:14px; padding:24px; box-shadow:0 10px 30px rgba(0,0,0,0.5); position:relative;">

            <!-- Close -->
            <button onclick="closeTxnModal()"
                style="position:absolute; top:12px; right:16px; font-size:22px; color:#9ca3af; border:none; background:none; cursor:pointer;"
                onmouseover="this.style.color='#22c55e'"
                onmouseout="this.style.color='#9ca3af'">
                ×
            </button>

            <h2 style="font-size:20px; font-weight:700; color:#22c55e; margin-bottom:15px;">
                Transaction Details
            </h2>

            <div id="txnDetails"></div>

            <!-- Upload -->
            <form id="uploadForm" method="POST" action="{{ route('admin.esim.upload') }}" enctype="multipart/form-data"
                style="margin-top:20px; display:flex; flex-direction:column; gap:14px;">
                @csrf

                <input type="hidden" name="transaction_id" id="txnId">

                <input type="text" name="numbers" id="uploadNumbers"
                    placeholder="Numbers / ICCID"
                    style="padding:10px; border-radius:8px; border:none; background:#1f2937; color:#fff;">

                <textarea name="details" id="uploadDetails" rows="3"
                    placeholder="Instructions / Details"
                    style="padding:10px; border-radius:8px; border:none; background:#1f2937; color:#fff;"></textarea>

                <input type="file" name="images[]" multiple
                    style="color:#9ca3af;">

                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="clearForm()"
                        style="flex:1; background:#374151; padding:10px; border-radius:8px; color:white; border:none;">
                        Clear
                    </button>

                    <button type="submit"
                        style="flex:2; background:#16a34a; padding:10px; border-radius:8px; color:white; border:none;">
                        Save Upload
                    </button>
                </div>
            </form>

        </div>
    </div>

</main>

<script>
function openTxnModal(id){
    fetch(`/app/admin/esim/transaction/${id}`)
    .then(res=>res.json())
    .then(data=>{
        const txn=data.transaction;
        document.getElementById('txnId').value=txn.id;

        document.getElementById('txnDetails').innerHTML=`
            <div style="background:#1f2937; padding:12px; border-radius:10px; margin-bottom:10px;">
                <p><strong>User:</strong> ${txn.username}</p>
                <p><strong>Amount:</strong> ₦${txn.amount}</p>
                <p><strong>Status:</strong> ${txn.status}</p>
                <p><strong>Reference:</strong> ${txn.reference}</p>
            </div>
        `;

        document.getElementById('txnModal').style.display='flex';
    });
}

function closeTxnModal(){
    document.getElementById('txnModal').style.display='none';
}

function clearForm(){
    document.getElementById('uploadNumbers').value='';
    document.getElementById('uploadDetails').value='';
}
</script>
@endsection