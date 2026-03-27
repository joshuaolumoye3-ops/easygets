@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<!-- Main Content -->
<main class="pt-[70px] w-full flex flex-col">
    <div class="p-[1.875rem_1.75rem] w-full flex-grow">
        
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-[#191c24] p-4 rounded-2xl shadow-md mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-white mb-4 md:mb-0 text-center md:text-left">
                Admin Dashboard
            </h1>
            <div class="flex items-center space-x-3 sm:space-x-4 w-full md:w-auto">
                <input 
                    id="userSearch"
                    type="text" 
                    placeholder="Search users..." 
                    class="flex-1 md:flex-none px-3 sm:px-4 py-2 w-full sm:w-auto rounded-lg bg-input_bg border border-border_color text-white focus:outline-none focus:ring-2 focus:ring-modern-primary text-sm sm:text-base"
                >
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-modern-primary flex items-center justify-center text-white font-bold text-sm sm:text-base">
                    A
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="flex flex-wrap gap-4 sm:gap-6 mb-8">
            <div class="flex-1 min-w-[200px] lg:flex-[1_1_22%] rounded-md bg-[#191c24] p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-muted text-sm mb-1">Total Users</p>
                        <div class="flex items-center">
                            <h3 class="text-2xl font-bold text-white mb-0">{{ $users->count() }}</h3>
                            <p class="text-modern-success text-sm ml-2 mb-0 font-medium">+3.5%</p>
                        </div>
                    </div>
                    <div class="text-modern-primary p-2 flex items-center justify-center w-[40px] h-[37px] bg-modern-primary/15 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1119.071 5.804 9 9 0 015.12 17.804zM15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="flex-1 min-w-[200px] lg:flex-[1_1_22%] rounded-md bg-[#191c24] p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-muted text-sm mb-1">Revenue</p>
                        <div class="flex items-center">
                            <h3 class="text-2xl font-bold text-white mb-0">₦{{ number_format($stats['total_revenue'] ?? 0, 2) }}</h3>
                            <p class="text-modern-success text-sm ml-2 mb-0 font-medium">+11%</p>
                        </div>
                    </div>
                    <div class="text-modern-success p-2 flex items-center justify-center w-[40px] h-[37px] bg-modern-success/15 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3 0-5 1-5 4s2 4 5 4 5-1 5-4-2-4-5-4z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12v4m0 0l2-2m-2 2l-2-2" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Profit Card -->
            <div class="flex-1 min-w-[200px] lg:flex-[1_1_22%] rounded-md bg-[#191c24] p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-muted text-sm mb-1">Profit (Net)</p>
                        <div class="flex items-center">
                            <h3 class="text-2xl font-bold text-white mb-0">₦{{ number_format($stats['total_profit'] ?? 0, 2) }}</h3>
                            <p class="text-modern-success text-sm ml-2 mb-0 font-medium">+7%</p>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Today: ₦{{ number_format($stats['profit_today'] ?? 0, 2) }} • Week: ₦{{ number_format($stats['profit_week'] ?? 0, 2) }} • Month: ₦{{ number_format($stats['profit_month'] ?? 0, 2) }}</p>
                    </div>
                    <div class="text-modern-primary p-2 flex items-center justify-center w-[40px] h-[37px] bg-modern-primary/15 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3 0-5 1-5 4s2 4 5 4 5-1 5-4-2-4-5-4z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="flex-1 min-w-[200px] lg:flex-[1_1_22%] rounded-md bg-[#191c24] p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-muted text-sm mb-1">Transactions</p>
                        <div class="flex items-center">
                            <h3 class="text-2xl font-bold text-white mb-0">{{ $stats['total_transactions'] ?? 0 }}</h3>
                            <p class="text-modern-success text-sm ml-2 mb-0 font-medium">+8.3%</p>
                        </div>
                    </div>
                    <div class="text-modern-info p-2 flex items-center justify-center w-[40px] h-[37px] bg-modern-info/15 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="flex-1 min-w-[200px] lg:flex-[1_1_22%] rounded-md bg-[#191c24] p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-muted text-sm mb-1">Pending Requests</p>
                        <div class="flex items-center">
                            <h3 class="text-2xl font-bold text-white mb-0">{{ $stats['pending_requests'] ?? 0 }}</h3>
                            <p class="text-modern-warning text-sm ml-2 mb-0 font-medium">-2.4%</p>
                        </div>
                    </div>
                    <div class="text-modern-warning p-2 flex items-center justify-center w-[40px] h-[37px] bg-modern-warning/15 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users and Recent Activity Row -->
        <div class="grid grid-cols-10 lg-max:grid-cols-1 gap-6 mb-8">
            <!-- Users Section -->
            <div class="rounded-md bg-[#191c24] col-span-6 lg-max:col-span-10">
                <div class="py-[1.88rem] px-[1.81rem] text-white">
                    <h2 class="text-lg font-bold mb-4">All Users</h2>
                    <div class="overflow-x-auto">
                        <table id="usersTable" class="w-full text-left border-collapse text-sm sm:text-base">
                            <thead>
                                <tr class="border-b border-muted/20 text-muted text-sm">
                                    <th class="text-start p-3">Username</th>
                                    <th class="text-start p-3">Email</th>
                                    <th class="text-start p-3">Phone</th>
                                    <th class="text-start p-3">Wallet Balance</th>
                                    <th class="text-start p-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users->take(10) as $user)
                                <tr class="border-b border-muted/20">
                                    <td class="p-3 text-white">{{ $user->username }}</td>
                                    <td class="p-3 text-muted">{{ $user->email }}</td>
                                    <td class="p-3 text-muted">{{ $user->phone_number }}</td>
                                    <td class="p-3 text-white font-semibold">₦{{ number_format(optional($user->wallet)->balance, 2) }}</td>
                                    <td class="p-3">
                                        <button 
                                            class="bg-modern-primary text-white px-3 py-1 rounded-lg text-sm hover:bg-modern-primary/80"
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
            </div>

            <!-- Quick Stats -->
            <div class="rounded-md bg-[#191c24] col-span-4 lg-max:col-span-10">
                <div class="py-[1.88rem] px-[1.81rem] text-white">
                    <h5 class="mb-6">Quick Actions</h5>
                    
                    <!-- Recent Activity -->
                    <div class="space-y-4 mb-6">
                        <div class="bg-input_bg flex flex-row py-3 px-4 rounded">
                            <div class="text-center">
                                <h6 class="mb-1 text-white font-medium">New User Registered</h6>
                                <p class="text-muted text-sm mb-0">{{ $stats['latest_user']->created_at->diffForHumans() ?? 'No recent users' }}</p>
                            </div>
                            <div class="flex-grow self-center text-right py-2">
                                <h6 class="font-bold text-modern-success mb-0">+1</h6>
                            </div>
                        </div>
                        
                        <div class="bg-input_bg flex flex-row py-3 px-4 rounded">
                            <div class="text-center">
                                <h6 class="mb-1 text-white font-medium">Latest Transaction</h6>
                                <p class="text-muted text-sm mb-0"> {{ $stats['latest_transaction']->created_at->diffForHumans() ?? 'No recent transactions' }}</p>
                            </div>
                            <div class="flex-grow self-center text-right py-2">
                                <h6 class="font-bold text-white mb-0">₦{{ number_format($stats['latest_transaction']->amount ?? 0, 2) }}</h6>
                            </div>
                        </div>
                    </div>

                    <!-- System Stats -->
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-muted">Success Rate</span>
                            <span class="text-modern-success font-semibold">
                                {{ $transactions->count() > 0 ? round(($stats['successful_transactions'] / $transactions->count()) * 100, 1) : 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-border_color rounded-full h-2">
                            <div class="bg-modern-success h-2 rounded-full" style="width: {{ $transactions->count() > 0 ? round(($stats['successful_transactions'] / $transactions->count()) * 100) : 0 }}%"></div>
                        </div>
                        
                        <div class="flex justify-between mt-3">
                            <span class="text-muted">Active Users</span>
                            <span class="text-modern-info font-semibold">
                                {{ $stats['active_users_30_days'] }}
                            </span>
                        </div>
                        <div class="w-full bg-border_color rounded-full h-2">
                            <div class="bg-modern-info h-2 rounded-full" style="width: {{ $users->count() > 0 ? round(($stats['active_users_30_days'] / $users->count()) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Section -->
        <div class="rounded-md bg-[#191c24] mb-8">
            <div class="py-[1.88rem] px-[1.81rem] text-white">
                <h4 class="font-bold mb-4">Recent Transactions</h4>
                <div class="overflow-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="border-b border-muted/20 text-muted text-sm">
                                <th class="text-start p-3">User</th>
                                <th class="text-start p-3">Service</th>
                                <th class="text-start p-3">Amount</th>
                                <th class="text-start p-3">Status</th>
                                <th class="text-start p-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions->take(10) as $tx)
                            <tr class="border-b border-muted/20">
                                <td class="p-3 text-white">{{ $tx->user->username ?? 'N/A' }}</td>
                                <td class="p-3 text-muted">{{ $tx->type }}</td>
                                <td class="p-3 text-white font-semibold">₦{{ number_format($tx->amount, 2) }}</td>
                                <td class="p-3">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                                        style="
                                            background-color: 
                                            {{ $tx->status == 'success' ? '#22c55e' : ($tx->status == 'failed' ? '#ef4444' : ($tx->status == 'pending' ? '#facc15' : '#9ca3af')) }};
                                            color: 
                                            {{ $tx->status == 'pending' ? '#1f2937' : '#ffffff' }};
                                            border-radius: 0.375rem;
                                            padding: 0.25rem 0.5rem;
                                            font-weight: 500;
                                            font-size: 0.75rem;
                                            display: inline-flex;
                                            align-items: center;
                                        ">
                                        {{ $tx->status }}
                                    </span>

                                </td>
                                <td class="p-3 text-muted">{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
<!-- User Details Modal -->
<div id="userModal" 
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center overflow-y-auto">
    <div id="modalContent" 
        class="bg-[#191c24] rounded-2xl shadow-2xl w-full max-w-4xl p-6 relative transform scale-95 opacity-0 transition-all duration-300"
        style="max-height: 90vh; overflow-y: auto;">

        <!-- Close Button -->
        <button type="button" onclick="closeUserModal()" 
                style="position:absolute; top:1rem; right:1rem; color:#bfbfbf; font-size:1.5rem; font-weight:bold; background:none; border:none; cursor:pointer;">
            ✖
        </button>

        <!-- User Details -->
        <h3 style="color:#fff; font-weight:bold; font-size:1.125rem; margin-bottom:1rem; border-bottom:1px solid #43464f; padding-bottom:0.5rem;">User Details</h3>
        <div id="userDetails" style="margin-bottom:1rem;"></div>

        <!-- Admin Actions -->
        <h3 style="color:#fff; font-weight:bold; font-size:1.125rem; margin-top:1.5rem; margin-bottom:1rem; border-bottom:1px solid #43464f; padding-bottom:0.5rem;">Admin Actions</h3>
        <div style="display:flex; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
            <button type="button" onclick="showPasswordForm()" style="background:#1890ff; color:#fff; padding:0.5rem 1rem; border-radius:0.5rem; border:none; cursor:pointer;">Change Password</button>
            <button type="button" onclick="showPinForm()" style="background:#faad14; color:#fff; padding:0.5rem 1rem; border-radius:0.5rem; border:none; cursor:pointer;">Change PIN</button>
            <button type="button" onclick="showFundsForm()" style="background:#52c41a; color:#fff; padding:0.5rem 1rem; border-radius:0.5rem; border:none; cursor:pointer;">Manage Funds</button>
        </div>

        <!-- Password Form -->
        <div id="passwordForm" class="hidden" style="margin-bottom:1rem;">
            <input type="password" id="newPassword" placeholder="New Password" style="width:100%; background:#1f2128; border:1px solid #43464f; color:#fff; border-radius:0.5rem; padding:0.5rem; margin-bottom:0.5rem;">
            <input type="password" id="confirmPassword" placeholder="Confirm Password" style="width:100%; background:#1f2128; border:1px solid #43464f; color:#fff; border-radius:0.5rem; padding:0.5rem; margin-bottom:0.5rem;">
            <button type="button" onclick="updatePassword()" style="background:#1890ff; color:#fff; padding:0.5rem 1rem; border-radius:0.5rem; border:none; cursor:pointer;">Save Password</button>
        </div>

        <!-- PIN Form -->
        <div id="pinForm" class="hidden" style="margin-bottom:1rem;">
            <input type="number" id="newPin" placeholder="New 4-digit PIN" maxlength="4" style="width:100%; background:#1f2128; border:1px solid #43464f; color:#fff; border-radius:0.5rem; padding:0.5rem; margin-bottom:0.5rem;">
            <button type="button" onclick="updatePin()" style="background:#faad14; color:#fff; padding:0.5rem 1rem; border-radius:0.5rem; border:none; cursor:pointer;">Save PIN</button>
        </div>

        <!-- Funds Management Form -->
        <div id="fundsForm" class="hidden" style="margin-bottom:1rem; padding:1rem; border:1px solid #43464f; border-radius:0.5rem; background:#1f2128;">
            <h4 style="color:#fff; margin-bottom:1rem; font-size:1rem;">Wallet Management</h4>
            
            <!-- Add Funds Section -->
            <div style="margin-bottom:1rem; padding:0.75rem; border:1px solid #52c41a; border-radius:0.5rem; background:rgba(82, 196, 26, 0.1);">
                <h5 style="color:#52c41a; margin-bottom:0.5rem; font-size:0.9rem;">Add Funds</h5>
                <input type="number" id="addAmount" placeholder="Amount to add" min="0" step="0.01" style="width:100%; background:#191c24; border:1px solid #43464f; color:#fff; border-radius:0.5rem; padding:0.5rem; margin-bottom:0.5rem;">
                <textarea id="addDescription" placeholder="Reason for adding funds" style="width:100%; background:#191c24; border:1px solid #43464f; color:#fff; border-radius:0.5rem; padding:0.5rem; margin-bottom:0.5rem; resize:vertical;" rows="2"></textarea>
                <button type="button" onclick="addFunds()" style="background:#52c41a; color:#fff; padding:0.5rem 1rem; border-radius:0.5rem; border:none; cursor:pointer;">Add Funds</button>
            </div>
            
            <!-- Deduct Funds Section -->
            <div style="margin-bottom:1rem; padding:0.75rem; border:1px solid #ff4d4f; border-radius:0.5rem; background:rgba(255, 77, 79, 0.1);">
                <h5 style="color:#ff4d4f; margin-bottom:0.5rem; font-size:0.9rem;">Deduct Funds</h5>
                <input type="number" id="deductAmount" placeholder="Amount to deduct" min="0" step="0.01" style="width:100%; background:#191c24; border:1px solid #43464f; color:#fff; border-radius:0.5rem; padding:0.5rem; margin-bottom:0.5rem;">
                <textarea id="deductDescription" placeholder="Reason for deducting funds" style="width:100%; background:#191c24; border:1px solid #43464f; color:#fff; border-radius:0.5rem; padding:0.5rem; margin-bottom:0.5rem; resize:vertical;" rows="2"></textarea>
                <button type="button" onclick="deductFunds()" style="background:#ff4d4f; color:#fff; padding:0.5rem 1rem; border-radius:0.5rem; border:none; cursor:pointer;">Deduct Funds</button>
            </div>
        </div>

        <!-- Feedback -->
        <div id="actionMessage" style="margin-top:0.5rem; font-weight:500;"></div>

        <!-- User Transactions -->
        <h3 style="color:#fff; font-weight:bold; font-size:1.125rem; margin-top:1.5rem; margin-bottom:1rem; border-bottom:1px solid #43464f; padding-bottom:0.5rem;">User Transactions</h3>
        <div id="userTransactions" style="overflow-x:auto; max-height:300px;"></div>
    </div>
</div>

<script>
let currentUserId = null;

// Get CSRF token function
function getCSRFToken() {
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    if (!tokenElement) {
        console.error('CSRF token not found. Make sure you have <meta name="csrf-token" content="{{ csrf_token() }}"> in your layout head.');
        return null;
    }
    return tokenElement.getAttribute('content');
}

// Generic request function with better error handling
async function makeRequest(url, data) {
    const token = getCSRFToken();
    if (!token) {
        throw new Error('CSRF token not found');
    }

    console.log(`Making request to: ${url}`, data); // Debug log

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    });

    console.log('Response status:', response.status); // Debug log

    if (!response.ok) {
        const errorText = await response.text();
        console.error('Response error:', errorText);
        throw new Error(`HTTP ${response.status}: ${errorText}`);
    }

    return response.json();
}

function showPasswordForm() {
    hideAllForms();
    document.getElementById('passwordForm').classList.remove('hidden');
    document.getElementById('actionMessage').innerHTML = '';
}

function showPinForm() {
    hideAllForms();
    document.getElementById('pinForm').classList.remove('hidden');
    document.getElementById('actionMessage').innerHTML = '';
}

function showFundsForm() {
    hideAllForms();
    document.getElementById('fundsForm').classList.remove('hidden');
    document.getElementById('actionMessage').innerHTML = '';
}

function hideAllForms() {
    document.getElementById('passwordForm').classList.add('hidden');
    document.getElementById('pinForm').classList.add('hidden');
    document.getElementById('fundsForm').classList.add('hidden');
}

function showMessage(message, isSuccess = false) {
    const color = isSuccess ? '#52c41a' : '#ff4d4f';
    document.getElementById('actionMessage').innerHTML = `<span style="color:${color};">${message}</span>`;
}

async function addFunds() {
    const amount = parseFloat(document.getElementById('addAmount').value);
    const description = document.getElementById('addDescription').value.trim();

    if (!amount || amount <= 0) {
        showMessage('Please enter a valid amount');
        return;
    }

    if (!description) {
        showMessage('Please provide a description');
        return;
    }

    if (!currentUserId) {
        showMessage('No user selected');
        return;
    }

    try {
        showMessage('Processing...', true);

        const baseUrl = '{{ url('/') }}';
        const data = await makeRequest(`${baseUrl}/app/admin/users/${currentUserId}/add-funds`, {
            amount: amount,
            description: description
        });

        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('addAmount').value = '';
            document.getElementById('addDescription').value = '';
            // Refresh user details after 1 second
            setTimeout(() => {
                openUserModal(currentUserId);
            }, 1000);
        } else {
            showMessage(data.message || 'Failed to add funds');
        }
    } catch (error) {
        console.error('Add funds error:', error);
        showMessage('Failed to add funds: ' + error.message);
    }
}

async function deductFunds() {
    const amount = parseFloat(document.getElementById('deductAmount').value);
    const description = document.getElementById('deductDescription').value.trim();

    if (!amount || amount <= 0) {
        showMessage('Please enter a valid amount');
        return;
    }

    if (!description) {
        showMessage('Please provide a description');
        return;
    }

    if (!currentUserId) {
        showMessage('No user selected');
        return;
    }

    try {
        showMessage('Processing...', true);

        const baseUrl = '{{ url('/') }}';
        const data = await makeRequest(`${baseUrl}/app/admin/users/${currentUserId}/deduct-funds`, {
            amount: amount,
            description: description
        });

        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('deductAmount').value = '';
            document.getElementById('deductDescription').value = '';
            // Refresh user details after 1 second
            setTimeout(() => {
                openUserModal(currentUserId);
            }, 1000);
        } else {
            showMessage(data.message || 'Failed to deduct funds');
        }
    } catch (error) {
        console.error('Deduct funds error:', error);
        showMessage('Failed to deduct funds: ' + error.message);
    }
}

async function updatePassword() {
    const password = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;

    if (!password || !confirm) {
        showMessage('Please fill in both password fields');
        return;
    }

    if (password !== confirm) {
        showMessage('Passwords do not match');
        return;
    }

    if (!currentUserId) {
        showMessage('No user selected');
        return;
    }

    try {
        showMessage('Processing...', true);

        const baseUrl = '{{ url('/') }}';
        const data = await makeRequest(`${baseUrl}/app/admin/users/${currentUserId}/update-password`, {
            password: password,
            password_confirmation: confirm
        });

        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('passwordForm').classList.add('hidden');
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        } else {
            showMessage(data.message || 'Failed to update password');
        }
    } catch (error) {
        console.error('Update password error:', error);
        showMessage('Failed to update password: ' + error.message);
    }
}

async function updatePin() {
    const pin = document.getElementById('newPin').value;
    
    if (!pin || pin.length !== 4) {
        showMessage('PIN must be exactly 4 digits');
        return;
    }

    if (!currentUserId) {
        showMessage('No user selected');
        return;
    }

    try {
        showMessage('Processing...', true);

        const baseUrl = '{{ url('/') }}';
        const data = await makeRequest(`${baseUrl}/app/admin/users/${currentUserId}/update-pin`, {
            pin: pin
        });

        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('pinForm').classList.add('hidden');
            document.getElementById('newPin').value = '';
        } else {
            showMessage(data.message || 'Failed to update PIN');
        }
    } catch (error) {
        console.error('Update PIN error:', error);
        showMessage('Failed to update PIN: ' + error.message);
    }
}

async function openUserModal(userId) {
    currentUserId = userId;

    try {
        const baseUrl = '{{ url('/') }}';
        const response = await fetch(`${baseUrl}/app/admin/users/${userId}`);
        console.log('User details response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        const user = data.user;
        const transactions = data.transactions;

        document.getElementById('userDetails').innerHTML = `
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div>
                    <p><strong style="color:#40a9ff;">Username:</strong> <span style="color:#fff;">${user.username || 'N/A'}</span></p>
                    <p><strong style="color:#40a9ff;">Name:</strong> <span style="color:#fff;">${(user.first_name || '') + ' ' + (user.last_name || '')}</span></p>
                    <p><strong style="color:#40a9ff;">Email:</strong> <span style="color:#fff;">${user.email || 'N/A'}</span></p>
                </div>
                <div>
                    <p><strong style="color:#40a9ff;">Phone:</strong> <span style="color:#fff;">${user.phone_number || 'N/A'}</span></p>
                    <p><strong style="color:#40a9ff;">Wallet Balance:</strong> <span style="color:#52c41a; font-weight:bold;">₦${user.wallet ? Number(user.wallet.balance).toFixed(2) : '0.00'}</span></p>
                    <p><strong style="color:#40a9ff;">Joined:</strong> <span style="color:#fff;">${new Date(user.created_at).toLocaleDateString()}</span></p>
                </div>
            </div>
        `;

        let txRows = '';
        if (transactions && transactions.length > 0) {
            txRows = transactions.map(tx => {
                let bg = '#d9d9d9', color = '#000';
                const status = tx.status ? tx.status.toLowerCase() : '';
                if (status === 'success') { bg = '#52c41a'; color = '#fff'; }
                else if (status === 'pending') { bg = '#faad14'; color = '#000'; }
                else if (status === 'failed') { bg = '#ff4d4f'; color = '#fff'; }

                return `<tr style="border-bottom:1px solid #43464f;">
                    <td style="padding:8px; color:#fff;">${tx.type || 'N/A'}</td>
                    <td style="padding:8px; color:#fff; font-weight:bold;">₦${Number(tx.amount || 0).toFixed(2)}</td>
                    <td style="padding:8px;"><span style="background:${bg}; color:${color}; padding:2px 6px; border-radius:6px; font-size:12px; font-weight:500;">${tx.status || 'Unknown'}</span></td>
                    <td style="padding:8px; color:#bfbfbf;">${tx.created_at ? new Date(tx.created_at).toLocaleString() : 'N/A'}</td>
                    <td style="padding:8px; color:#bfbfbf; font-size:12px;">${tx.response || 'N/A'}</td>
                </tr>`;
            }).join('');
        } else {
            txRows = `<tr><td colspan="5" style="padding:8px; text-align:center; color:#bfbfbf;">No transactions yet.</td></tr>`;
        }

        document.getElementById('userTransactions').innerHTML = `
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #43464f; background-color:#1f2128;">
                        <th style="padding:8px; color:#bfbfbf;">Type</th>
                        <th style="padding:8px; color:#bfbfbf;">Amount</th>
                        <th style="padding:8px; color:#bfbfbf;">Status</th>
                        <th style="padding:8px; color:#bfbfbf;">Date</th>
                        <th style="padding:8px; color:#bfbfbf;">Description</th>
                    </tr>
                </thead>
                <tbody>${txRows}</tbody>
            </table>
        `;

        const modal = document.getElementById('userModal');
        const modalContent = document.getElementById('modalContent');
        modal.classList.remove('hidden');
        setTimeout(() => { 
            modalContent.classList.remove('scale-95', 'opacity-0'); 
            modalContent.classList.add('scale-100', 'opacity-100'); 
        }, 50);
        
    } catch (error) {
        console.error('Open modal error:', error);
        alert("Failed to load user details: " + error.message);
    }
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    const modalContent = document.getElementById('modalContent');
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        hideAllForms();
        document.getElementById('actionMessage').innerHTML = '';
        // Clear all form inputs
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
        document.getElementById('newPin').value = '';
        document.getElementById('addAmount').value = '';
        document.getElementById('addDescription').value = '';
        document.getElementById('deductAmount').value = '';
        document.getElementById('deductDescription').value = '';
        currentUserId = null;
    }, 200);
}

document.getElementById('userModal').addEventListener('click', e => {
    if (e.target === document.getElementById('userModal')) {
        closeUserModal();
    }
});
</script>
@endsection