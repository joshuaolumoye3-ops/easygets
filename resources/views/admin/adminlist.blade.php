@extends('layouts.admin')

@section('title', 'Administrator Management')

@section('content')
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background-color: #0f172a; }
    
    .admin-container { padding-top: 70px; min-height: 100vh; background: linear-gradient(135deg, #0f172a 0%, #1a202c 100%); }
    .main-content { padding: 40px 30px; max-width: 1400px; margin: 0 auto; }
    
    /* Header Bar */
    .header-bar {
        background: #1e293b;
        border-radius: 16px;
        padding: 28px 32px;
        margin-bottom: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 2px 6px rgba(0,0,0,0.2);
        border: 1px solid #334155;
    }
    
    .header-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
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
        color: #f1f5f9;
        transition: all 0.3s ease;
    }
    .search-box input::placeholder { color: #94a3b8; }
    .search-box input:focus { outline: none; border-color: #3b82f6; background-color: #475569; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
    .search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; }
    
    .avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); }
    
    /* Table Card */
    .table-card {
        background: #1e293b;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 2px 6px rgba(0,0,0,0.2);
        overflow: hidden;
        border: 1px solid #334155;
    }
    
    .table-header { padding: 24px 28px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
    .table-header h2 { font-size: 18px; font-weight: 700; color: #f1f5f9; }
    .table-badge { padding: 6px 14px; background-color: #1e3a8a; color: #93c5fd; border-radius: 20px; font-size: 12px; font-weight: 600; }
    
    .table-wrapper { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background-color: #0f172a; }
    th {
        padding: 16px 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #334155;
    }
    td {
        padding: 16px 20px;
        border-bottom: 1px solid #334155;
        font-size: 14px;
        color: #f1f5f9;
    }
    tbody tr { transition: background-color 0.2s ease; cursor: pointer; }
    tbody tr:hover { background-color: #334155; }
    
    .admin-cell { display: flex; align-items: center; gap: 12px; }
    .admin-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; }
    .admin-info { flex: 1; }
    .admin-name { font-weight: 600; color: #f1f5f9; }
    .admin-email { font-size: 12px; color: #94a3b8; }
    
    .role-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        width: fit-content;
    }
    .role-super { background-color: #6b21a844; color: #d8b4fe; }
    .role-admin { background-color: #1e3a8a44; color: #93c5fd; }
    
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
    .status-active { background-color: #15803d44; color: #86efac; }
    .status-inactive { background-color: #7f1d1d44; color: #fca5a5; }
    
    .action-btn {
        padding: 8px 14px;
        background-color: #1e3a8a;
        color: #93c5fd;
        border: 1px solid #3b82f6;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .action-btn:hover { background-color: #1e40af; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3); }
    
    .empty-state { padding: 60px 20px; text-align: center; }
    .empty-state svg { width: 64px; height: 64px; color: #475569; margin: 0 auto 16px; }
    .empty-state h3 { font-size: 18px; font-weight: 600; color: #f1f5f9; margin-bottom: 8px; }
    .empty-state p { font-size: 14px; color: #94a3b8; }
    
    .pagination { padding: 20px 28px; border-top: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
    .pagination-info { font-size: 14px; color: #cbd5e1; }
    
    /* Modal */
    .modal { display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
    .modal.active { display: flex; }
    .modal-content {
        background: #1e293b;
        border-radius: 20px;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        animation: slideUp 0.3s ease;
        border: 1px solid #334155;
    }
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .modal-header { padding: 24px 28px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
    .modal-header h2 { font-size: 20px; font-weight: 700; color: #f1f5f9; }
    .close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: color 0.2s; }
    .close-btn:hover { color: #cbd5e1; }
    
    .modal-body { padding: 28px; }
    .section { margin-bottom: 28px; }
    .section-title { font-size: 16px; font-weight: 700; color: #f1f5f9; margin-bottom: 16px; }
    
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .info-item { padding: 14px; background-color: #334155; border-radius: 10px; border: 1px solid #475569; }
    .info-label { font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 6px; text-transform: uppercase; }
    .info-value { font-size: 15px; color: #f1f5f9; font-weight: 600; }
    
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 14px; font-weight: 600; color: #f1f5f9; margin-bottom: 8px; }
    .form-input, .form-select {
        width: 100%;
        padding: 12px 14px;
        border: 2px solid #475569;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        color: #f1f5f9;
        background-color: #334155;
        transition: all 0.3s ease;
    }
    .form-input::placeholder { color: #94a3b8; }
    .form-input:focus, .form-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
    
    .btn-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 24px; }
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
    .btn-primary:hover { background-color: #2563eb; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); }
    .btn-secondary { background-color: #475569; color: #f1f5f9; }
    .btn-secondary:hover { background-color: #64748b; }
    
    @media (max-width: 768px) {
        .header-content { flex-direction: column; align-items: flex-start; }
        .search-box { width: 100%; }
        .info-grid { grid-template-columns: 1fr; }
        .btn-group { grid-template-columns: 1fr; }
    }
</style>

<!-- Main Content -->
<div class="admin-container">
    <div class="main-content">
        
        <!-- Header -->
        <div class="header-bar">
            <div class="header-content">
                <div class="header-left">
                    <h1>Administrator Management</h1>
                    <p>Manage platform administrators and their permissions</p>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" placeholder="Search administrators..." id="searchInput">
                    </div>
                    <div class="avatar">A</div>
                </div>
            </div>
        </div>
        
        <!-- Admins Table -->
        <div class="table-card">
            <div class="table-header">
                <h2>All Administrators</h2>
                <span class="table-badge">{{ $admins->count() }} total</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Administrator</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($admins as $admin)
                        <tr onclick="openAdminModal({{ $admin->id }})">
                            <td>
                                <div class="admin-cell">
                                    <div class="admin-avatar">{{ strtoupper(substr($admin->name ?? 'A', 0, 1)) }}</div>
                                    <div class="admin-info">
                                        <div class="admin-name">{{ $admin->name }}</div>
                                        <div class="admin-email">{{ $admin->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $admin->email }}</td>
                            <td>
                                @if($admin->role === 'super_admin')
                                    <span class="role-badge role-super">👑 Super Admin</span>
                                @else
                                    <span class="role-badge role-admin">🔐 Admin</span>
                                @endif
                            </td>
                            <td>{{ $admin->phone ?? '—' }}</td>
                            <td>
                                @if($admin->status === 'active')
                                    <span class="status-badge status-active">● Active</span>
                                @else
                                    <span class="status-badge status-inactive">● Inactive</span>
                                @endif
                            </td>
                            <td><button class="action-btn" onclick="event.stopPropagation(); openAdminModal({{ $admin->id }})">👁️ View</button></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <h3>No administrators found</h3>
                                    <p>Get started by creating your first administrator.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <div class="pagination-info">
                    Showing {{ $admins->count() }} administrators
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Admin Modal -->
<div id="adminModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Administrator Details</h2>
            <button class="close-btn" onclick="closeAdminModal()">×</button>
        </div>
        
        <div class="modal-body">
            <!-- Admin Info -->
            <div class="section">
                <h3 class="section-title">Administrator Information</h3>
                <div id="adminDetails" class="info-grid"></div>
            </div>
            
            <!-- Update Form -->
            <div class="section">
                <h3 class="section-title">Update Administrator</h3>
                <form id="updateAdminForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select id="adminRole" class="form-select">
                                <option value="admin">Administrator</option>
                                <option value="super_admin">Super Administrator</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select id="adminStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">New Password (Optional)</label>
                            <input type="password" id="newPassword" class="form-input" placeholder="Leave blank to keep current">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" id="confirmPassword" class="form-input" placeholder="Confirm password">
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" onclick="updateAdmin(event)" class="btn btn-primary">💾 Update Admin</button>
                        <button type="button" onclick="closeAdminModal()" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentAdminId = null;

function openAdminModal(adminId) {
    currentAdminId = adminId;
    document.getElementById('adminModal').classList.add('active');
    loadAdminDetails(adminId);
}

function closeAdminModal() {
    document.getElementById('adminModal').classList.remove('active');
    currentAdminId = null;
}

function loadAdminDetails(adminId) {
    fetch(`/admin/api/admin/${adminId}`)
        .then(res => res.json())
        .then(data => {
            const admin = data.admin;
            const detailsHtml = `
                <div class="info-item">
                    <div class="info-label">Name</div>
                    <div class="info-value">${admin.name}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value">${admin.email}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value">${admin.phone || '—'}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Role</div>
                    <div class="info-value">${admin.role === 'super_admin' ? '👑 Super Admin' : '🔐 Administrator'}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">${admin.status === 'active' ? '✓ Active' : '✗ Inactive'}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Created At</div>
                    <div class="info-value">${new Date(admin.created_at).toLocaleDateString()}</div>
                </div>
            `;
            document.getElementById('adminDetails').innerHTML = detailsHtml;
            document.getElementById('adminRole').value = admin.role;
            document.getElementById('adminStatus').value = admin.status;
        })
        .catch(err => console.error(err));
}

function updateAdmin(e) {
    e.preventDefault();
    const role = document.getElementById('adminRole').value;
    const status = document.getElementById('adminStatus').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword && newPassword !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    const data = { role, status };
    if (newPassword) data.password = newPassword;
    
    fetch(`/admin/api/admin/${currentAdminId}/update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Administrator updated successfully!');
            closeAdminModal();
            location.reload();
        }
    })
    .catch(err => console.error(err));
}

// Search functionality
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const filter = e.target.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Close modal on outside click
document.getElementById('adminModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAdminModal();
});
</script>
@endsection
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Administrator</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($admins as $admin)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="openAdminModal({{ $admin->id }})">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-medium">
                                        {{ strtoupper(substr($admin->name, 0, 1)) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $admin->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $admin->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $admin->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $admin->role === 'super_admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $admin->role)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $admin->phone ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($admin->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <h3 class="text-sm font-medium text-gray-900 mb-1">No administrators found</h3>
                                    <p class="text-sm text-gray-500">Get started by adding your first administrator.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ $admins->count() }} administrators
                    </div>
                    <div class="flex space-x-2">
                        {{ $admins->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Admin Details Modal -->
<div id="adminModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center overflow-y-auto">
    <div id="modalContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-0 relative transform scale-95 opacity-0 transition-all duration-300 max-h-[90vh] overflow-hidden">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-900">Administrator Details</h3>
            <button type="button" onclick="closeAdminModal()" 
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
            <!-- Admin Info -->
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Administrator Information</h4>
                <div id="adminDetails" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
            </div>

            <!-- Transactions -->
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h4>
                <div id="transactions" class="max-h-64 overflow-y-auto space-y-3"></div>
            </div>

            <!-- Update Form -->
            <div class="p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Update Administrator</h4>
                <form id="updateAdminForm" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                            <select name="role" id="adminRole" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="admin">Administrator</option>
                                <option value="super_admin">Super Administrator</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" id="adminStatus" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password (Optional)</label>
                            <input type="password" name="password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input type="password" name="password_confirmation" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Administrator
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openAdminModal(adminId) {
    const baseUrl = '{{ url('/') }}';
    fetch(`${baseUrl}/app/admin/${adminId}`)
        .then(res => res.json())
        .then(admin => {
            // Fill Admin Details
            document.getElementById('adminDetails').innerHTML = `
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                            ${admin.name ? admin.name.charAt(0).toUpperCase() : 'A'}
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-gray-900">${admin.name}</h4>
                            <p class="text-sm text-gray-600">${admin.email}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Role</label>
                            <p class="text-sm text-gray-900 capitalize">${admin.role.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Phone</label>
                            <p class="text-sm text-gray-900">${admin.phone ?? 'N/A'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                ${admin.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${admin.status === 'active' ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Joined</label>
                            <p class="text-sm text-gray-900">${admin.created_at ? new Date(admin.created_at).toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric' 
                            }) : 'N/A'}</p>
                        </div>
                    </div>
                </div>
            `;

            // Fill Transactions
            let txHtml = '';
            if (admin.transactions && admin.transactions.length > 0) {
                txHtml = admin.transactions.map(tx => {
                    const statusClass = tx.status === 'success' || tx.status === 'completed'
                        ? 'bg-green-50 border-green-200 text-green-800'
                        : tx.status === 'pending'
                        ? 'bg-yellow-50 border-yellow-200 text-yellow-800'
                        : 'bg-red-50 border-red-200 text-red-800';

                    return `
                    <div class="border rounded-lg p-4 ${statusClass}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium capitalize">${tx.type ?? 'Transaction'}</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                ${tx.status === 'success' || tx.status === 'completed' ? 'bg-green-100 text-green-800' : 
                                  tx.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                                ${tx.status}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold">₦${Number(tx.amount).toLocaleString()}</span>
                            <span class="text-gray-600">${tx.reference ?? 'N/A'}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            ${tx.created_at ? new Date(tx.created_at).toLocaleString() : 'N/A'}
                        </div>
                    </div>`;
                }).join('');
            } else {
                txHtml = `
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-gray-500 text-sm">No transactions found</p>
                    </div>
                `;
            }
            document.getElementById('transactions').innerHTML = txHtml;

            // Update Form Action
            document.getElementById('updateAdminForm').action = `${baseUrl}/app/admin/${admin.id}/update`;

            // Set current values
            document.getElementById('adminRole').value = admin.role;
            document.getElementById('adminStatus').value = admin.status;

            // Show Modal
            const modal = document.getElementById('adminModal');
            const modalContent = document.getElementById('modalContent');
            modal.classList.remove('hidden');
            setTimeout(() => { 
                modalContent.classList.remove('scale-95', 'opacity-0'); 
                modalContent.classList.add('scale-100', 'opacity-100'); 
            }, 50);
        })
        .catch(error => {
            console.error('Error loading admin details:', error);
            alert('Failed to load administrator details. Please try again.');
        });
}

function closeAdminModal() {
    const modal = document.getElementById('adminModal');
    const modalContent = document.getElementById('modalContent');
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Close modal when clicking outside
document.getElementById('adminModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdminModal();
    }
});
</script>

<style>
/* Custom scrollbar for transactions */
#transactions::-webkit-scrollbar {
    width: 6px;
}
#transactions::-webkit-scrollbar-thumb {
    background-color: #cbd5e0;
    border-radius: 3px;
}
#transactions::-webkit-scrollbar-track {
    background: transparent;
}
</style>
@endsection