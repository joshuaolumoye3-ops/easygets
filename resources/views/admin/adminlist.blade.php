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
