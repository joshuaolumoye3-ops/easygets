<style>
    .sidebar {
        width: 244px;
        min-height: 100vh;
        background: #191c24;
        position: fixed;
        left: 0;
        top: 0;
        transition: transform 0.3s ease;
        z-index: 40;
        overflow-y: auto;
    }
    
    .sidebar.collapsed { transform: translateX(-244px); }
    
    .sidebar-header {
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 1rem;
        border-bottom: 1px solid #2c2e33;
    }
    
    .sidebar-profile {
        padding: 1rem 1.17rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border-bottom: 1px solid #2c2e33;
    }
    
    .profile-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e5e7eb;
    }

    .nav-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 10px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(16,185,129,0.3);
    }
    
    .nav-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(16,185,129,0.4);
    }

    /* ===== MODAL STYLING ===== */
    @keyframes modalFade {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .modal-animate {
        animation: modalFade 0.2s ease-out;
    }

    .modal-input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        background: #0f1117;
        border: 1px solid #2c2e33;
        color: white;
        outline: none;
        transition: 0.2s;
    }

    .modal-input:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
    }

    .btn-submit {
        background: linear-gradient(135deg, #10b981, #059669);
        padding: 8px 16px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
    }

    .btn-cancel {
        background: #374151;
        padding: 8px 16px;
        border-radius: 8px;
        color: white;
    }
</style>

<!-- Top Navbar -->
<nav class="top-navbar" id="topNavbar">
    <button class="toggle-btn" id="sidebarToggle">
        ☰
    </button>
    
    <div class="navbar-actions">
        @auth('admins')
            @if(Auth::guard('admins')->user()->role === 'super_admin')
                <button class="nav-btn" onclick="openAdminModal()">
                    + Add New Admin
                </button>
            @endif
        @endauth
        
        <!--
        MESSAGE ICON (COMMENTED)
        <button class="icon-btn">
            ...
        </button>

        NOTIFICATION ICON (COMMENTED)
        <button class="icon-btn">
            ...
        </button>
        -->

        <button class="icon-btn">
            <svg class="profile-avatar" style="width: 32px; height: 32px;" viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="32" fill="#e5e7eb"/>
            </svg>
        </button>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="main-content-wrapper" id="mainContentWrapper">
    <div class="content-area">
        @yield('content')
    </div>
</div>

<!-- ===== MODAL ===== -->
<div id="addAdminModal" class="fixed inset-0 hidden items-center justify-center bg-black/70 backdrop-blur-sm z-50">
    
    <div class="bg-[#191c24] text-white rounded-2xl shadow-2xl w-full max-w-md p-6 border border-gray-700 modal-animate">

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Register New Admin</h3>
            <button onclick="closeAdminModal()">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.create') }}" class="space-y-4">
            @csrf

            <input type="text" name="name" placeholder="Name" required class="modal-input">
            <input type="email" name="email" placeholder="Email" required class="modal-input">
            <input type="text" name="phone" placeholder="Phone" class="modal-input">
            <input type="password" name="password" placeholder="Password" required class="modal-input">

            <select name="role" required class="modal-input">
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
            </select>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeAdminModal()" class="btn-cancel">
                    Cancel
                </button>
                <button type="submit" class="btn-submit">
                    Create Admin
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* ===== MODAL CONTROL ===== */
function openAdminModal() {
    const modal = document.getElementById('addAdminModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAdminModal() {
    const modal = document.getElementById('addAdminModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

/* CLOSE ON OUTSIDE CLICK */
document.getElementById('addAdminModal').addEventListener('click', function(e) {
    if (e.target === this) closeAdminModal();
});

/* CLOSE ON ESC */
document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") closeAdminModal();
});
</script>