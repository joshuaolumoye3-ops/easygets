@extends('layouts.admin')

@section('title', 'Admin Dashboard - Easygets')

@section('content')
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background-color: #0f172a; }

    .dashboard-container { padding-top: 70px; min-height: 100vh; background: linear-gradient(135deg, #0f172a 0%, #1a202c 100%); }
    .main-content { padding: 40px 30px; max-width: 1400px; margin: 0 auto; }

    /* Header Bar */
    .header-bar {
        background: #1e293b;
        border-radius: 16px;
        padding: 24px 28px;
        margin-bottom: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 2px 6px rgba(0,0,0,0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        border: 1px solid #334155;
    }
    .header-left h1 { font-size: 28px; font-weight: 700; color: #f1f5f9; margin-bottom: 4px; }
    .header-left p { font-size: 14px; color: #cbd5e1; font-weight: 500; }
    .header-right { display: flex; align-items: center; gap: 16px; }
    .search-box { position: relative; width: 300px; }
    .search-box input {
        width: 100%;
        padding: 10px 16px 10px 38px;
        border: 2px solid #475569;
        border-radius: 10px;
        font-size: 14px;
        background-color: #334155;
        transition: all 0.3s ease;
        color: #f1f5f9;
    }
    .search-box input::placeholder { color: #94a3b8; }
    .search-box input:focus { outline: none; border-color: #3b82f6; background-color: #475569; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
    .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; }
    .avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px; }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    .stat-card {
        background: #1e293b;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 2px 6px rgba(0,0,0,0.2);
        border: 1px solid #334155;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(59,130,246,0.2), 0 8px 24px rgba(0,0,0,0.3);
        transform: translateY(-2px);
        border-color: #475569;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    }
    .stat-content { display: flex; justify-content: space-between; align-items: flex-start; }
    .stat-info { flex: 1; }
    .stat-label { font-size: 13px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .stat-value { font-size: 32px; font-weight: 700; color: #f1f5f9; margin-bottom: 8px; }
    .stat-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px;
        background-color: rgba(16,185,129,0.2);
        color: #86efac;
        border-radius: 20px;
        font-size: 12px; font-weight: 600;
    }
    .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
    .stat-icon.blue  { background-color: rgba(30,58,138,0.2);  color: #60a5fa; }
    .stat-icon.green { background-color: rgba(21,128,61,0.2);  color: #86efac; }
    .stat-icon.purple{ background-color: rgba(107,33,168,0.2); color: #d8b4fe; }
    .stat-icon.orange{ background-color: rgba(146,64,14,0.2);  color: #fdba74; }

    /* Table Card */
    .table-card {
        background: #1e293b;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 2px 6px rgba(0,0,0,0.2);
        overflow: hidden;
        margin-bottom: 32px;
        border: 1px solid #334155;
    }
    .table-header { padding: 24px 28px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
    .table-header h3 { font-size: 18px; font-weight: 700; color: #f1f5f9; }
    .table-badge { padding: 4px 12px; background-color: #1e3a8a; color: #93c5fd; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .table-wrapper { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background-color: #0f172a; }
    th {
        padding: 16px 20px;
        text-align: left;
        font-size: 12px; font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase; letter-spacing: 0.5px;
        border-bottom: 2px solid #334155;
    }
    td { padding: 16px 20px; border-bottom: 1px solid #1e293b; font-size: 14px; color: #f1f5f9; }
    tbody tr { transition: background-color 0.2s ease; }
    tbody tr:hover { background-color: rgba(51,65,85,0.5); }

    .user-cell { display: flex; align-items: center; gap: 12px; }
    .user-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 14px;
        flex-shrink: 0;
    }

    .status-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 11px; border-radius: 8px;
        font-size: 12px; font-weight: 600;
    }
    .status-success { background-color: rgba(21,128,61,0.25); color: #86efac; }
    .status-pending { background-color: rgba(146,64,14,0.25); color: #fcd34d; }
    .status-failed  { background-color: rgba(127,29,29,0.25); color: #fca5a5; }

    .action-btn {
        padding: 8px 14px;
        background-color: #1e3a8a; color: #93c5fd;
        border: 1px solid #3b82f6; border-radius: 8px;
        font-size: 12px; font-weight: 600; cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .action-btn:hover { background-color: #1e40af; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(59,130,246,0.3); }

    /* ─── PAGINATION ─────────────────────────────────── */
    .pagination-wrapper {
        padding: 20px 28px;
        border-top: 1px solid #334155;
        background-color: #172033;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Tailwind paginator nav wrapper */
    .pagination-wrapper nav {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        width: 100%;
    }

    /* Result count text */
    .pagination-wrapper nav p,
    .pagination-wrapper p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }

    /* The flex row that holds the page buttons */
    .pagination-wrapper nav span[role="navigation"],
    .pagination-wrapper nav div[role="navigation"],
    .pagination-wrapper [aria-label="Pagination Navigation"] {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }

    /* Every clickable page anchor */
    .pagination-wrapper a,
    .pagination-wrapper button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        font-size: 13px;
        font-weight: 600;
        color: #94a3b8;
        background-color: #1e293b;
        border: 1.5px solid #334155;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
        white-space: nowrap;
    }

    .pagination-wrapper a:hover,
    .pagination-wrapper button:hover {
        background-color: #3b82f6;
        border-color: #2563eb;
        color: #ffffff;
        transform: translateY(-1px);
    }

    /* Active / current page */
    .pagination-wrapper span[aria-current="page"],
    .pagination-wrapper .active > span,
    .pagination-wrapper span.font-medium {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        font-size: 13px;
        font-weight: 700;
        color: #ffffff;
        background-color: #3b82f6;
        border: 1.5px solid #2563eb;
        border-radius: 8px;
        cursor: default;
    }

    /* Disabled prev / next when on first or last page */
    .pagination-wrapper span[aria-disabled="true"],
    .pagination-wrapper .disabled > span,
    .pagination-wrapper span.cursor-default {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        background-color: #0f172a;
        border: 1.5px solid #1e293b;
        border-radius: 8px;
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* Ellipsis dots */
    .pagination-wrapper span.relative,
    .pagination-wrapper span[aria-hidden="true"] {
        color: #475569;
        font-size: 13px;
        padding: 0 4px;
    }

    /* Tailwind simple pagination (older links() output) */
    .pagination-wrapper ul.pagination {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        list-style: none;
        padding: 0; margin: 0;
        justify-content: center;
        align-items: center;
    }
    .pagination-wrapper ul.pagination li a,
    .pagination-wrapper ul.pagination li span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 12px;
        font-size: 13px;
        font-weight: 600;
        color: #94a3b8;
        background-color: #1e293b;
        border: 1.5px solid #334155;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }
    .pagination-wrapper ul.pagination li a:hover {
        background-color: #3b82f6;
        border-color: #2563eb;
        color: #ffffff;
    }
    .pagination-wrapper ul.pagination li.active span,
    .pagination-wrapper ul.pagination li.active a {
        background-color: #3b82f6;
        border-color: #2563eb;
        color: #ffffff;
        font-weight: 700;
        cursor: default;
    }
    .pagination-wrapper ul.pagination li.disabled span,
    .pagination-wrapper ul.pagination li.disabled a {
        opacity: 0.4;
        cursor: not-allowed;
        background-color: #0f172a;
        border-color: #1e293b;
        color: #334155;
    }
    /* ─── END PAGINATION ─────────────────────────────── */

    /* Modal */
    #userModal {
        display: none !important;
        position: fixed !important;
        top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
        background-color: rgba(0,0,0,0.75) !important;
        z-index: 1000 !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 20px !important;
    }
    #userModal.active { display: flex !important; }
    .modal-content {
        background: #1e293b !important;
        border-radius: 20px !important;
        width: 100% !important;
        max-width: 900px !important;
        max-height: 90vh !important;
        overflow-y: auto !important;
        box-shadow: 0 20px 60px rgba(0,0,0,0.6) !important;
        animation: slideUp 0.3s ease !important;
        border: 1px solid #334155 !important;
    }
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .modal-header {
        padding: 24px 28px;
        border-bottom: 1px solid #334155;
        display: flex; justify-content: space-between; align-items: center;
    }
    .modal-header h2 { font-size: 20px; font-weight: 700; color: #f1f5f9; }
    .close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: color 0.2s; }
    .close-btn:hover { color: #f1f5f9; }
    .modal-body { padding: 28px; }
    .section { margin-bottom: 28px; }
    .section-title { font-size: 16px; font-weight: 700; color: #f1f5f9; margin-bottom: 16px; }

    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .info-item { padding: 16px; background-color: #334155; border-radius: 12px; border: 1px solid #475569; }
    .info-label { font-size: 12px; color: #94a3b8; font-weight: 600; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-value { font-size: 16px; color: #f1f5f9; font-weight: 600; }

    .action-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .btn {
        padding: 12px 16px; border: none; border-radius: 10px;
        font-size: 14px; font-weight: 600; cursor: pointer;
        transition: all 0.3s ease;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-primary { background-color: #3b82f6; color: white; }
    .btn-primary:hover { background-color: #2563eb; box-shadow: 0 4px 12px rgba(59,130,246,0.4); }
    .btn-purple { background-color: #8b5cf6; color: white; }
    .btn-purple:hover { background-color: #7c3aed; box-shadow: 0 4px 12px rgba(139,92,246,0.4); }
    .btn-green  { background-color: #10b981; color: white; }
    .btn-green:hover  { background-color: #059669; box-shadow: 0 4px 12px rgba(16,185,129,0.4); }
    .btn-red    { background-color: #ef4444; color: white; }
    .btn-red:hover    { background-color: #dc2626; box-shadow: 0 4px 12px rgba(239,68,68,0.4); }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 14px; font-weight: 600; color: #f1f5f9; margin-bottom: 8px; }
    .form-input, .form-textarea {
        width: 100%; padding: 12px 14px;
        border: 2px solid #475569; border-radius: 10px;
        font-size: 14px; font-family: inherit;
        color: #f1f5f9; background-color: #334155;
        transition: all 0.3s ease;
    }
    .form-input::placeholder, .form-textarea::placeholder { color: #94a3b8; }
    .form-input:focus, .form-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
    .form-textarea { resize: vertical; min-height: 80px; }

    .hidden-form { display: none; padding: 16px; background-color: #334155; border-radius: 12px; border-left: 4px solid #3b82f6; }
    .hidden-form.active { display: block; }

    .message { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; margin-bottom: 16px; }
    .message-success { background-color: rgba(21,128,61,0.25); color: #86efac; border-left: 4px solid #10b981; }
    .message-error   { background-color: rgba(127,29,29,0.25); color: #fca5a5; border-left: 4px solid #ef4444; }

    .transactions-section { padding-top: 20px; border-top: 1px solid #334155; margin-top: 20px; }

    @media (max-width: 768px) {
        .header-bar { flex-direction: column; align-items: flex-start; }
        .search-box { width: 100%; }
        .stats-grid { grid-template-columns: 1fr; }
        .action-buttons { grid-template-columns: 1fr; }
        .pagination-wrapper a,
        .pagination-wrapper button,
        .pagination-wrapper ul.pagination li a,
        .pagination-wrapper ul.pagination li span { min-width: 32px; height: 32px; font-size: 12px; padding: 0 8px; }
    }
</style>

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
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
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
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                            +11%
                        </div>
                    </div>
                    <div class="stat-icon green">💰</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <div class="stat-label">Transactions</div>
                        <div class="stat-value">{{ $stats['total_transactions'] ?? 0 }}</div>
                        <div class="stat-badge">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
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
                <span class="table-badge">{{ $users->total() }} total</span>
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
                        @foreach($users as $user)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">{{ strtoupper(substr($user->username, 0, 1)) }}</div>
                                    <div>{{ $user->username }}</div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone_number }}</td>
                            <td><span style="color:#86efac;font-weight:600;">₦{{ number_format(optional($user->wallet)->balance ?? 0, 2) }}</span></td>
                            <td><button class="action-btn" onclick="openUserModal({{ $user->id }})">👁️ View</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrapper">
                {{ $users->links('pagination::tailwind') }}
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="table-card">
            <div class="table-header">
                <h3>Recent Transactions</h3>
                <span class="table-badge">{{ $stats['total_transactions'] ?? 0 }} total</span>
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
                        @foreach($transactions as $tx)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">{{ strtoupper(substr($tx->user->username ?? 'N', 0, 1)) }}</div>
                                    <div>{{ $tx->user->username ?? 'N/A' }}</div>
                                </div>
                            </td>
                            <td><span style="background-color:#475569;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;color:#cbd5e1;">{{ $tx->type }}</span></td>
                            <td style="font-weight:600;">₦{{ number_format($tx->amount, 2) }}</td>
                            <td>
                                @if($tx->status == 'success' || $tx->status == 'completed')
                                    <span class="status-badge status-success">✓ Success</span>
                                @elseif($tx->status == 'pending')
                                    <span class="status-badge status-pending">⏳ Pending</span>
                                @elseif($tx->status == 'failed')
                                    <span class="status-badge status-failed">✗ Failed</span>
                                @else
                                    <span class="status-badge" style="background-color:#475569;color:#cbd5e1;">{{ $tx->status }}</span>
                                @endif
                            </td>
                            <td style="color:#94a3b8;">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrapper">
                {{ $transactions->links('pagination::tailwind') }}
            </div>
        </div>

    </div>
</div>

<!-- User Modal -->
<div id="userModal" style="display:none !important;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>User Management</h2>
            <button class="close-btn" onclick="closeUserModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="section">
                <h3 class="section-title">User Information</h3>
                <div id="userDetails"></div>
            </div>
            <div class="section">
                <h3 class="section-title">Admin Actions</h3>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="showPasswordForm()">🔑 Change Password</button>
                    <button class="btn btn-purple"  onclick="showPinForm()">🔐 Change PIN</button>
                    <button class="btn btn-green"   onclick="showFundsForm()">💳 Manage Funds</button>
                </div>
                <div id="actionMessage"></div>

                <!-- Password Form -->
                <div id="passwordForm" class="hidden-form">
                    <h4 style="margin-bottom:16px;font-weight:600;color:#f1f5f9;">Change Password</h4>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" id="newPassword" class="form-input" placeholder="Enter new password">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" id="confirmPassword" class="form-input" placeholder="Confirm password">
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="updatePassword()" style="width:100%;">✓ Update Password</button>
                </div>

                <!-- PIN Form -->
                <div id="pinForm" class="hidden-form">
                    <h4 style="margin-bottom:16px;font-weight:600;color:#f1f5f9;">Change PIN</h4>
                    <div class="form-group">
                        <label class="form-label">New 4-digit PIN</label>
                        <input type="password" id="newPin" class="form-input" placeholder="Enter 4-digit PIN" maxlength="4">
                    </div>
                    <button class="btn btn-purple" onclick="updatePin()" style="width:100%;">✓ Update PIN</button>
                </div>

                <!-- Funds Form -->
                <div id="fundsForm" class="hidden-form">
                    <h4 style="margin-bottom:16px;font-weight:600;color:#f1f5f9;">Wallet Management</h4>
                    <div style="margin-bottom:20px;padding:16px;background-color:rgba(21,128,61,0.15);border-radius:12px;border-left:4px solid #10b981;">
                        <h5 style="margin-bottom:12px;font-weight:600;color:#86efac;">Add Funds</h5>
                        <div class="form-group">
                            <label class="form-label">Amount</label>
                            <input type="number" id="addAmount" class="form-input" placeholder="Amount to add" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea id="addDescription" class="form-textarea" placeholder="Reason for adding funds"></textarea>
                        </div>
                        <button class="btn btn-green" onclick="addFunds()" style="width:100%;">+ Add Funds</button>
                    </div>
                    <div style="padding:16px;background-color:rgba(127,29,29,0.2);border-radius:12px;border-left:4px solid #ef4444;">
                        <h5 style="margin-bottom:12px;font-weight:600;color:#fca5a5;">Deduct Funds</h5>
                        <div class="form-group">
                            <label class="form-label">Amount</label>
                            <input type="number" id="deductAmount" class="form-input" placeholder="Amount to deduct" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea id="deductDescription" class="form-textarea" placeholder="Reason for deducting funds"></textarea>
                        </div>
                        <button class="btn btn-red" onclick="deductFunds()" style="width:100%;">− Deduct Funds</button>
                    </div>
                </div>
            </div>
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
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCSRFToken(), 'Accept': 'application/json' },
        body: JSON.stringify(data)
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
}

function showPasswordForm() { hideAllForms(); document.getElementById('passwordForm').classList.add('active'); }
function showPinForm()      { hideAllForms(); document.getElementById('pinForm').classList.add('active'); }
function showFundsForm()    { hideAllForms(); document.getElementById('fundsForm').classList.add('active'); }
function hideAllForms()     { document.querySelectorAll('.hidden-form').forEach(f => f.classList.remove('active')); }

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
    } catch (e) { showMessage('Error: ' + e.message); }
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
    } catch (e) { showMessage('Error: ' + e.message); }
}

async function updatePassword() {
    const password = document.getElementById('newPassword').value;
    const confirm  = document.getElementById('confirmPassword').value;
    if (!password || password !== confirm) { showMessage('Passwords must match'); return; }
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
    } catch (e) { showMessage('Error: ' + e.message); }
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
    } catch (e) { showMessage('Error: ' + e.message); }
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
                <div class="info-item"><div class="info-label">Full Name</div><div class="info-value">${user.first_name || ''} ${user.last_name || ''}</div></div>
                <div class="info-item"><div class="info-label">Username</div><div class="info-value">@${user.username || 'N/A'}</div></div>
                <div class="info-item"><div class="info-label">Email</div><div class="info-value">${user.email || 'N/A'}</div></div>
                <div class="info-item"><div class="info-label">Phone</div><div class="info-value">${user.phone_number || 'N/A'}</div></div>
                <div class="info-item"><div class="info-label">Wallet Balance</div><div class="info-value" style="color:#10b981;">₦${Number(user.wallet?.balance || 0).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2})}</div></div>
                <div class="info-item"><div class="info-label">Joined</div><div class="info-value">${new Date(user.created_at).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'})}</div></div>
            </div>`;

        const txHtml = transactions.length > 0 ? `
            <div style="overflow-x:auto;">
                <table style="width:100%;font-size:13px;">
                    <thead><tr style="background-color:#0f172a;">
                        <th style="padding:12px;text-align:left;font-weight:600;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #334155;">Type</th>
                        <th style="padding:12px;text-align:left;font-weight:600;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #334155;">Amount</th>
                        <th style="padding:12px;text-align:left;font-weight:600;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #334155;">Status</th>
                        <th style="padding:12px;text-align:left;font-weight:600;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #334155;">Date</th>
                    </tr></thead>
                    <tbody>
                        ${transactions.map(tx => {
                            let sc = tx.status === 'success' || tx.status === 'completed' ? 'status-success' : tx.status === 'pending' ? 'status-pending' : 'status-failed';
                            return `<tr style="border-bottom:1px solid #334155;">
                                <td style="padding:12px;color:#f1f5f9;">${tx.type || 'N/A'}</td>
                                <td style="padding:12px;font-weight:600;color:#f1f5f9;">₦${Number(tx.amount||0).toLocaleString('en-NG',{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
                                <td style="padding:12px;"><span class="status-badge ${sc}">${tx.status||'Unknown'}</span></td>
                                <td style="padding:12px;color:#94a3b8;">${new Date(tx.created_at).toLocaleDateString()}</td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>` : '<p style="color:#94a3b8;text-align:center;padding:20px;">No transactions yet.</p>';

        document.getElementById('userTransactions').innerHTML = txHtml;
        const modal = document.getElementById('userModal');
        modal.classList.add('active');
        modal.style.cssText = 'display:flex !important;';
        hideAllForms();
        document.getElementById('actionMessage').innerHTML = '';
    } catch (e) { alert('Error: ' + e.message); }
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.classList.remove('active');
    modal.style.cssText = 'display:none !important;';
}

document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) closeUserModal();
});

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('userModal');
    if (modal) { modal.style.cssText = 'display:none !important;'; modal.classList.remove('active'); }
});
</script>

@endsection