@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="flex flex-col lg:flex-row min-h-screen bg-brand-gray-light">
    @include('partials.aside')

    <!-- Main Content -->
    <div class="flex-1 p-4 sm:p-6 md:p-8 space-y-8 lg:ml-0">
        
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-2xl shadow-md">
            <h1 class="text-lg sm:text-xl font-bold text-brand-green mb-4 md:mb-0 text-center md:text-left">
                Admin Dashboard
            </h1>
            <div class="flex items-center space-x-3 sm:space-x-4 w-full md:w-auto">
                <input 
                    id="userSearch"
                    type="text" 
                    placeholder="Search users..." 
                    class="flex-1 md:flex-none px-3 sm:px-4 py-2 w-full sm:w-auto rounded-lg border border-brand-gray-medium focus:outline-none focus:ring-2 focus:ring-brand-green-medium text-sm sm:text-base"
                >
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-brand-green-medium flex items-center justify-center text-white font-bold text-sm sm:text-base">
                    A
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <div class="bg-white p-4 rounded-2xl shadow-lg text-center">
                <p class="text-gray-500 text-sm">Total Users</p>
                <p class="text-2xl font-bold text-brand-green">{{ $users->count() }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-lg text-center">
                <p class="text-gray-500 text-sm">Revenue</p>
                <p class="text-2xl font-bold text-brand-green">₦{{ number_format($transactions->sum('amount')) }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-lg text-center">
                <p class="text-gray-500 text-sm">Transactions</p>
                <p class="text-2xl font-bold text-brand-green">{{ $transactions->count() }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-lg text-center">
                <p class="text-gray-500 text-sm">Pending Requests</p>
                <p class="text-2xl font-bold text-brand-green">{{ $transactions->where('status','Pending')->count() }}</p>
            </div>
        </div>

        <!-- Users Section -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-lg font-bold mb-4">All Users</h2>
            <div class="overflow-x-auto">
                <table id="usersTable" class="w-full text-left border-collapse text-sm sm:text-base">
                    <thead>
                        <tr class="border-b border-brand-gray-medium">
                            <th class="p-3">Username</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Phone</th>
                            <th class="p-3">Wallet Balance</th>
                            <th class="p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-3">{{ $user->username }}</td>
                            <td class="p-3">{{ $user->email }}</td>
                            <td class="p-3">{{ $user->phone_number }}</td>
                            <td class="p-3">₦{{ number_format(optional($user->wallet)->balance, 2) }}</td>
                            <td class="p-3">
                                <button 
                                    class="bg-brand-green text-white px-3 py-1 rounded-lg text-sm hover:bg-brand-green-medium"
                                    onclick="openUserModal({{ $user->id }})"
                                >
                                    View
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>

        <!-- Transactions Section -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-lg font-bold mb-4">Recent Transactions</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm sm:text-base">
                    <thead>
                        <tr class="border-b border-brand-gray-medium">
                            <th class="p-3">User</th>
                            <th class="p-3">Service</th>
                            <th class="p-3">Amount</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-3">{{ $tx->user->username ?? 'N/A' }}</td>
                            <td class="p-3">{{ $tx->type }}</td>
                            <td class="p-3">₦{{ number_format($tx->amount, 2) }}</td>
                            <td class="p-3 
                                @if($tx->status == 'Success') text-green-600 
                                @elseif($tx->status == 'Failed') text-red-600 
                                @else text-yellow-600 @endif">
                                {{ $tx->status }}
                            </td>
                            <td class="p-3">{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{-- {{ $transactions->links() }} --}}
            </div>
        </div>

        <!-- User Details Modal -->
        <div id="userModal" 
            class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 transition-opacity duration-300">
            <div id="modalContent" 
                class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-6 relative transform scale-95 opacity-0 transition-all duration-300">
                <button onclick="closeUserModal()" 
                        class="absolute top-4 right-4 text-gray-500 hover:text-black text-xl font-bold">✖</button>
                
                <h3 class="text-lg sm:text-xl font-bold mb-4 border-b pb-2">User Details</h3>
                <div id="userDetails" class="space-y-3 text-sm sm:text-base"></div>

                <h3 class="text-lg sm:text-xl font-bold mt-6 mb-4 border-b pb-2">Admin Actions</h3>
                <div class="flex space-x-4">
                    <button onclick="showPasswordForm()" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm">
                        Change Password
                    </button>
                    <button onclick="showPinForm()" class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 text-sm">
                        Change PIN
                    </button>
                </div>

                <!-- Password Form -->
                <div id="passwordForm" class="hidden mt-4 space-y-3">
                    <input type="password" id="newPassword" placeholder="New Password"
                        class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-300">
                    <input type="password" id="confirmPassword" placeholder="Confirm Password"
                        class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-300">
                    <button onclick="updatePassword()" 
                            class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm">
                        Save Password
                    </button>
                </div>

                <!-- PIN Form -->
                <div id="pinForm" class="hidden mt-4 space-y-3">
                    <input type="number" id="newPin" placeholder="New 4-digit PIN"
                        class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-purple-300" maxlength="4">
                    <button onclick="updatePin()" 
                            class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 text-sm">
                        Save PIN
                    </button>
                </div>

                <!-- Feedback -->
                <div id="actionMessage" class="mt-3 text-sm font-medium"></div>

                
                <h3 class="text-lg sm:text-xl font-bold mt-6 mb-4 border-b pb-2">User Transactions</h3>
                <div id="userTransactions" class="overflow-x-auto text-sm sm:text-base"></div>
            </div>
        </div>

    </div>
</div>

<script>
    // User search filter
    document.getElementById('userSearch').addEventListener('input', function () {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll("#usersTable tbody tr");
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });

    // Modal functions
    let currentUserId = null;

function showPasswordForm() {
    document.getElementById('passwordForm').classList.remove('hidden');
    document.getElementById('pinForm').classList.add('hidden');
    document.getElementById('actionMessage').innerHTML = '';
}

function showPinForm() {
    document.getElementById('pinForm').classList.remove('hidden');
    document.getElementById('passwordForm').classList.add('hidden');
    document.getElementById('actionMessage').innerHTML = '';
}

function updatePassword() {
    let password = document.getElementById('newPassword').value;
    let confirmPassword = document.getElementById('confirmPassword').value;

    const baseUrl = '{{ url('/') }}';
    fetch(`${baseUrl}/app/admin/users/${currentUserId}/update-password`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            password: password,
            password_confirmation: confirmPassword
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('actionMessage').innerHTML = 
                `<span class="text-green-600">${data.message}</span>`;
            document.getElementById('passwordForm').classList.add('hidden');
        } else {
            document.getElementById('actionMessage').innerHTML = 
                `<span class="text-red-600">${data.message ?? 'Failed to update password'}</span>`;
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('actionMessage').innerHTML = 
            `<span class="text-red-600">Failed to update password</span>`;
    });
}


function updatePin() {
    let pin = document.getElementById('newPin').value;

    const baseUrl = '{{ url('/') }}';
    fetch(`${baseUrl}/app/admin/users/${currentUserId}/update-pin`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ pin })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('actionMessage').innerHTML = 
            `<span class="text-green-600">${data.message}</span>`;
        document.getElementById('pinForm').classList.add('hidden');
    })
    .catch(() => {
        document.getElementById('actionMessage').innerHTML = 
            `<span class="text-red-600">Failed to update PIN</span>`;
    });
}

   function openUserModal(userId) {
    console.log("Opening modal for user:", userId); // debug

    currentUserId = userId; // 👈 store the user ID globally so updatePassword/updatePin can use it

    const baseUrl = '{{ url('/') }}';
    fetch(`${baseUrl}/app/admin/users/${userId}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || `HTTP ${response.status}: ${response.statusText}`);
                }).catch(() => {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log("Fetched user data:", data); // debug

            if (!data.success) {
                throw new Error(data.message || 'Failed to load user details');
            }

            let user = data.user;
            let transactions = data.transactions;

            let userDetails = `
                <p><strong>Username:</strong> ${user.username}</p>
                <p><strong>Name:</strong> ${user.first_name ?? ''} ${user.last_name ?? ''}</p>
                <p><strong>Email:</strong> ${user.email}</p>
                <p><strong>Phone:</strong> ${user.phone_number}</p>
                <p><strong>Wallet Balance:</strong> ₦${user.wallet ? Number(user.wallet.balance).toFixed(2) : '0.00'}</p>
            `;

            let txRows = transactions.length ? transactions.map(tx => `
                <tr class="border-b">
                    <td class="p-2">${tx.type}</td>
                    <td class="p-2">₦${Number(tx.amount).toFixed(2)}</td>
                    <td class="p-2">${tx.status}</td>
                    <td class="p-2">${new Date(tx.created_at).toLocaleString()}</td>
                </tr>
            `).join('') : `<tr><td colspan="4" class="p-3 text-center text-gray-500">No transactions yet.</td></tr>`;

            document.getElementById('userDetails').innerHTML = userDetails;
            document.getElementById('userTransactions').innerHTML = `
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b bg-gray-100">
                            <th class="p-2">Type</th>
                            <th class="p-2">Amount</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Date</th>
                        </tr>
                    </thead>
                    <tbody>${txRows}</tbody>
                </table>
            `;

            let modal = document.getElementById('userModal');
            let modalContent = document.getElementById('modalContent');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 50);
        })
        .catch(error => {
            console.error("Error loading user:", error);
            alert("Failed to load user details: " + error.message);
        });
}

    function closeUserModal() {
        let modal = document.getElementById('userModal');
        let modalContent = document.getElementById('modalContent');
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }
</script>
@endsection
