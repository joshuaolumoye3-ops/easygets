@extends('layouts.admin')

@section('title', 'Admin Dashboard - Easygets')

@section('content')
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background-color: #f8fafc; }
    
    .dashboard-container { padding-top: 70px; min-height: 100vh; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
    .main-content { padding: 40px 30px; max-width: 1400px; margin: 0 auto; }
    
    /* Top Header Bar */
    .header-bar {
        background: white;
        border-radius: 16px;
        padding: 24px 28px;
        margin-bottom: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.04);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .header-left h1 { font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
    .header-left p { font-size: 14px; color: #64748b; font-weight: 500; }
    
    .header-right { display: flex; align-items: center; gap: 16px; }
    .search-box { position: relative; width: 300px; }
    .search-box input {
        width: 100%;
        padding: 10px 16px 10px 38px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        background-color: #f8fafc;
        transition: all 0.3s ease;
        color: #1e293b;
    }
    .search-box input:focus { outline: none; border-color: #3b82f6; background-color: white; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; }
    
    .avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.04);
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.12), 0 8px 24px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    }
    
    .stat-content { display: flex; justify-content: space-between; align-items: flex-start; }
    .stat-info { flex: 1; }
    .stat-label { font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .stat-value { font-size: 32px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
    .stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background-color: #dcfce7;
        color: #166534;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
    .stat-icon.blue { background-color: #dbeafe; color: #1e40af; }
    .stat-icon.green { background-color: #dcfce7; color: #166534; }
    .stat-icon.purple { background-color: #e9d5ff; color: #6b21a8; }
    .stat-icon.orange { background-color: #fed7aa; color: #92400e; }
    
    /* Tables */
    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.04);
        overflow: hidden;
        margin-bottom: 32px;
    }
    
    .table-header { padding: 24px 28px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .table-header h3 { font-size: 18px; font-weight: 700; color: #1e293b; }
    .table-badge { padding: 4px 12px; background-color: #dbeafe; color: #1e40af; border-radius: 20px; font-size: 12px; font-weight: 600; }
    
    .table-wrapper { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background-color: #f8fafc; }
    th {
        padding: 16px 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }
    td {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #1e293b;
    }
    tbody tr { transition: background-color 0.2s ease; }
    tbody tr:hover { background-color: #f8fafc; }
    
    .user-cell { display: flex; align-items: center; gap: 12px; }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; }
    .user-name { font-weight: 600; color: #1e293b; }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        width: fit-content;
    }
    .status-success { background-color: #dcfce7; color: #166534; }
    .status-pending { background-color: #fef3c7; color: #92400e; }
    .status-failed { background-color: #fee2e2; color: #991b1b; }
    
    .action-btn {
        padding: 8px 14px;
        background-color: #dbeafe;
        color: #1e40af;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .action-btn:hover { background-color: #bfdbfe; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2); }
    
    /* Modal */
    .modal { display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
    .modal.active { display: flex; }
    .modal-content {
        background: white;
        border-radius: 20px;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: slideUp 0.3s ease;
    }
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .modal-header {
        padding: 24px 28px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h2 { font-size: 20px; font-weight: 700; color: #1e293b; }
    .close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: color 0.2s; }
    .close-btn:hover { color: #64748b; }
    
    .modal-body { padding: 28px; }
    .section { margin-bottom: 28px; }
    .section-title { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px; }
    
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .info-item { padding: 16px; background-color: #f8fafc; border-radius: 12px; }
    .info-label { font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-size: 16px; color: #1e293b; font-weight: 600; }
    
    .action-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .btn {
        padding: 12px 16px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-primary { background-color: #3b82f6; color: white; }
    .btn-primary:hover { background-color: #2563eb; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
    
    .btn-purple { background-color: #8b5cf6; color: white; }
    .btn-purple:hover { background-color: #7c3aed; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3); }
    
    .btn-green { background-color: #10b981; color: white; }
    .btn-green:hover { background-color: #059669; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
    
    .btn-red { background-color: #ef4444; color: white; }
    .btn-red:hover { background-color: #dc2626; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
    
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
    .form-input, .form-textarea {
        width: 100%;
        padding: 12px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        color: #1e293b;
        background-color: white;
        transition: all 0.3s ease;
    }
    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .form-textarea { resize: vertical; min-height: 80px; }
    
    .hidden-form { display: none; padding: 16px; background-color: #f8fafc; border-radius: 12px; border-left: 4px solid #3b82f6; }
    .hidden-form.active { display: block; }
    
    .message {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 16px;
    }
    .message-success { background-color: #dcfce7; color: #166534; border-left: 4px solid #10b981; }
    .message-error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
    
    .transactions-section {
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
        margin-top: 20px;
    }
    
    @media (max-width: 768px) {
        .header-bar { flex-direction: column; align-items: flex-start; }
        .search-box { width: 100%; }
        .stats-grid { grid-template-columns: 1fr; }
        .action-buttons { grid-template-columns: 1fr; }
    }
</style>

<!-- Main Dashboard -->
<div class="dashboard-container">
    <div class="main-content">
        
        <!-- Header -->
        <div class="header-bar">
            <div class="header-left">
                <h1>Admin Dashboard</h1>
                <p>Welcome back! Here's what's happening with your platform.</p>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="userSearch" placeholder="Search users...">
                </div>
                <div class="avatar">A</div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value">{{ $users->count() }}</div>
                        <div class="stat-badge">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            +3.5%
                        </div>
                    </div>
                    <div class="stat-icon blue">👥</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">₦{{ number_format($stats['total_revenue'] ?? 0, 0) }}</div>
                        <div class="stat-badge">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            +11%
                        </div>
                    </div>
                    <div class="stat-icon green">💰</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <div class="stat-label">Net Profit</div>
                        <div class="stat-value">₦{{ number_format($stats['total_profit'] ?? 0, 0) }}</div>
                        <div class="stat-badge">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            +7%
                        </div>
                    </div>
                    <div class="stat-icon purple">📊</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <div class="stat-label">Transactions</div>
                        <div class="stat-value">{{ $stats['total_transactions'] ?? 0 }}</div>
                        <div class="stat-badge">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            +8.3%
                        </div>
                    </div>
                    <div class="stat-icon orange">🛒</div>
                </div>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="table-card">
            <div class="table-header">
                <h3>Recent Users</h3>
                <span class="table-badge">{{ $users->count() }} total</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Wallet Balance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users->take(10) as $user)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">{{ strtoupper(substr($user->username, 0, 1)) }}</div>
                                    <div class="user-name">{{ $user->username }}</div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone_number }}</td>
                            <td><span style="color: #10b981; font-weight: 600;">₦{{ number_format(optional($user->wallet)->balance ?? 0, 2) }}</span></td>
                            <td><button class="action-btn" onclick="openUserModal({{ $user->id }})">👁️ View</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Transactions Table -->
        <div class="table-card">
            <div class="table-header">
                <h3>Recent Transactions</h3>
                <span class="table-badge">{{ $transactions->count() }} total</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions->take(10) as $tx)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">{{ strtoupper(substr($tx->user->username ?? 'N', 0, 1)) }}</div>
                                    <div class="user-name">{{ $tx->user->username ?? 'N/A' }}</div>
                                </div>
                            </td>
                            <td><span style="background-color: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">{{ $tx->type }}</span></td>
                            <td style="font-weight: 600;">₦{{ number_format($tx->amount, 2) }}</td>
                            <td>
                                @if($tx->status == 'success' || $tx->status == 'completed')
                                    <span class="status-badge status-success">✓ Success</span>
                                @elseif($tx->status == 'pending')
                                    <span class="status-badge status-pending">⏳ Pending</span>
                                @elseif($tx->status == 'failed')
                                    <span class="status-badge status-failed">✗ Failed</span>
                                @else
                                    <span class="status-badge" style="background-color: #f1f5f9; color: #475569;">{{ $tx->status }}</span>
                                @endif
                            </td>
                            <td style="color: #64748b;">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>User Management</h2>
            <button class="close-btn" onclick="closeUserModal()">×</button>
        </div>
        
        <div class="modal-body">
            <!-- User Info -->
            <div class="section">
                <h3 class="section-title">User Information</h3>
                <div id="userDetails"></div>
            </div>
            
            <!-- Action Buttons -->
            <div class="section">
                <h3 class="section-title">Admin Actions</h3>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="showPasswordForm()">🔑 Change Password</button>
                    <button class="btn btn-purple" onclick="showPinForm()">🔐 Change PIN</button>
                    <button class="btn btn-green" onclick="showFundsForm()">💳 Manage Funds</button>
                </div>
                
                <div id="actionMessage"></div>
                
                <!-- Password Form -->
                <div id="passwordForm" class="hidden-form">
                    <h4 style="margin-bottom: 16px; font-weight: 600;">Change Password</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" id="newPassword" class="form-input" placeholder="Enter new password">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" id="confirmPassword" class="form-input" placeholder="Confirm password">
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="updatePassword()" style="width: 100%;">✓ Update Password</button>
                </div>
                
                <!-- PIN Form -->
                <div id="pinForm" class="hidden-form">
                    <h4 style="margin-bottom: 16px; font-weight: 600;">Change PIN</h4>
                    <div class="form-group">
                        <label class="form-label">New 4-digit PIN</label>
                        <input type="password" id="newPin" class="form-input" placeholder="Enter 4-digit PIN" maxlength="4">
                    </div>
                    <button class="btn btn-purple" onclick="updatePin()" style="width: 100%;">✓ Update PIN</button>
                </div>
                
                <!-- Funds Form -->
                <div id="fundsForm" class="hidden-form">
                    <h4 style="margin-bottom: 16px; font-weight: 600;">Wallet Management</h4>
                    
                    <!-- Add Funds -->
                    <div style="margin-bottom: 20px; padding: 16px; background-color: #dcfce7; border-radius: 12px; border-left: 4px solid #10b981;">
                        <h5 style="margin-bottom: 12px; font-weight: 600; color: #166534;">Add Funds</h5>
                        <div style="display: grid; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">Amount</label>
                                <input type="number" id="addAmount" class="form-input" placeholder="Amount to add" min="0" step="0.01">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea id="addDescription" class="form-textarea" placeholder="Reason for adding funds"></textarea>
                            </div>
                            <button class="btn btn-green" onclick="addFunds()" style="width: 100%;">+ Add Funds</button>
                        </div>
                    </div>
                    
                    <!-- Deduct Funds -->
                    <div style="padding: 16px; background-color: #fee2e2; border-radius: 12px; border-left: 4px solid #ef4444;">
                        <h5 style="margin-bottom: 12px; font-weight: 600; color: #991b1b;">Deduct Funds</h5>
                        <div style="display: grid; gap: 12px;">
                            <div class="form-group">
                                <label class="form-label">Amount</label>
                                <input type="number" id="deductAmount" class="form-input" placeholder="Amount to deduct" min="0" step="0.01">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea id="deductDescription" class="form-textarea" placeholder="Reason for deducting funds"></textarea>
                            </div>
                            <button class="btn btn-red" onclick="deductFunds()" style="width: 100%;">− Deduct Funds</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Transactions -->
            <div class="transactions-section">
                <h3 class="section-title">Recent Transactions</h3>
                <div id="userTransactions"></div>
            </div>
        </div>
    </div>
</div>

<script>
let currentUserId = null;

function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function makeRequest(url, data) {
    const token = getCSRFToken();
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
}

function showPasswordForm() {
    hideAllForms();
    document.getElementById('passwordForm').classList.add('active');
}

function showPinForm() {
    hideAllForms();
    document.getElementById('pinForm').classList.add('active');
}

function showFundsForm() {
    hideAllForms();
    document.getElementById('fundsForm').classList.add('active');
}

function hideAllForms() {
    document.querySelectorAll('.hidden-form').forEach(f => f.classList.remove('active'));
}

function showMessage(message, isSuccess = false) {
    const div = document.getElementById('actionMessage');
    div.className = `message ${isSuccess ? 'message-success' : 'message-error'}`;
    div.textContent = message;
}

async function addFunds() {
    const amount = parseFloat(document.getElementById('addAmount').value);
    const description = document.getElementById('addDescription').value.trim();
    if (!amount || amount <= 0 || !description || !currentUserId) { showMessage('Please fill all fields'); return; }
    try {
        showMessage('Processing...', true);
        const data = await makeRequest(`{{ url('/') }}/app/admin/users/${currentUserId}/add-funds`, { amount, description });
        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('addAmount').value = '';
            document.getElementById('addDescription').value = '';
            setTimeout(() => openUserModal(currentUserId), 1000);
        } else { showMessage(data.message || 'Failed'); }
    } catch (error) {
        showMessage('Error: ' + error.message);
    }
}

async function deductFunds() {
    const amount = parseFloat(document.getElementById('deductAmount').value);
    const description = document.getElementById('deductDescription').value.trim();
    if (!amount || amount <= 0 || !description || !currentUserId) { showMessage('Please fill all fields'); return; }
    try {
        showMessage('Processing...', true);
        const data = await makeRequest(`{{ url('/') }}/app/admin/users/${currentUserId}/deduct-funds`, { amount, description });
        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('deductAmount').value = '';
            document.getElementById('deductDescription').value = '';
            setTimeout(() => openUserModal(currentUserId), 1000);
        } else { showMessage(data.message || 'Failed'); }
    } catch (error) {
        showMessage('Error: ' + error.message);
    }
}

async function updatePassword() {
    const password = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    if (!password || !confirm || password !== confirm) { showMessage('Passwords must match'); return; }
    if (!currentUserId) { showMessage('No user selected'); return; }
    try {
        showMessage('Processing...', true);
        const data = await makeRequest(`{{ url('/') }}/app/admin/users/${currentUserId}/update-password`, { password, password_confirmation: confirm });
        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('passwordForm').classList.remove('active');
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        } else { showMessage(data.message || 'Failed'); }
    } catch (error) {
        showMessage('Error: ' + error.message);
    }
}

async function updatePin() {
    const pin = document.getElementById('newPin').value;
    if (!pin || pin.length !== 4) { showMessage('PIN must be 4 digits'); return; }
    if (!currentUserId) { showMessage('No user selected'); return; }
    try {
        showMessage('Processing...', true);
        const data = await makeRequest(`{{ url('/') }}/app/admin/users/${currentUserId}/update-pin`, { pin });
        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('pinForm').classList.remove('active');
            document.getElementById('newPin').value = '';
        } else { showMessage(data.message || 'Failed'); }
    } catch (error) {
        showMessage('Error: ' + error.message);
    }
}

async function openUserModal(userId) {
    currentUserId = userId;
    try {
        const response = await fetch(`{{ url('/') }}/app/admin/users/${userId}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        const user = data.user;
        const transactions = data.transactions || [];
        
        document.getElementById('userDetails').innerHTML = `
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value">${user.first_name || ''} ${user.last_name || ''}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value">@${user.username || 'N/A'}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value">${user.email || 'N/A'}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value">${user.phone_number || 'N/A'}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Wallet Balance</div>
                    <div class="info-value" style="color: #10b981;">₦${Number(user.wallet?.balance || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Joined</div>
                    <div class="info-value">${new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</div>
                </div>
            </div>
        `;
        
        let txHtml = transactions.length > 0 ? `
            <div style="overflow-x: auto;">
                <table style="width: 100%; font-size: 13px;">
                    <thead>
                        <tr style="background-color: #f8fafc;">
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #64748b;">Type</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #64748b;">Amount</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #64748b;">Status</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #64748b;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${transactions.map(tx => {
                            let statusClass = 'status-failed';
                            if (tx.status === 'success' || tx.status === 'completed') statusClass = 'status-success';
                            else if (tx.status === 'pending') statusClass = 'status-pending';
                            return `<tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px;">${tx.type || 'N/A'}</td>
                                <td style="padding: 12px; font-weight: 600;">₦${Number(tx.amount || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                <td style="padding: 12px;"><span class="status-badge ${statusClass}">${tx.status || 'Unknown'}</span></td>
                                <td style="padding: 12px; color: #64748b;">${new Date(tx.created_at).toLocaleDateString()}</td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        ` : '<p style="color: #94a3b8; text-align: center; padding: 20px;">No transactions yet.</p>';
        
        document.getElementById('userTransactions').innerHTML = txHtml;
        document.getElementById('userModal').classList.add('active');
        hideAllForms();
        document.getElementById('actionMessage').innerHTML = '';
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
}

document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) closeUserModal();
});
</script>

@endsection
