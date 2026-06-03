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
    
    .sidebar-header img { max-width: 90%; height: 56px; }
    
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
        flex-shrink: 0;
    }
    
    .profile-info h5 {
        margin: 0 0 4px 0;
        color: white;
        font-size: 14px;
        font-weight: 500;
    }
    
    .profile-info span {
        color: #a7afb7;
        font-size: 12px;
    }
    
    .nav-category {
        padding: 1rem 1.25rem 0.5rem;
        color: #a7afb7;
        font-size: 13px;
        font-weight: 500;
    }
    
    .menu-item {
        padding-right: 1.25rem;
        margin-bottom: 2px;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.8rem 1.188rem;
        color: #a7afb7;
        text-decoration: none;
        transition: all 0.3s;
        border-radius: 0 50px 50px 0;
        margin-left: 12px;
    }
    
    .nav-link:hover, .nav-link.active {
        background: black;
        color: white;
    }
    
    .nav-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 50%;
        background: rgba(167, 175, 183, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
    }
    
    .nav-icon svg { width: 20px; height: 20px; }
    
    .menu-arrow {
        margin-left: auto;
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 5px solid currentColor;
        transition: transform 0.3s;
    }
    
    .menu-arrow.open { transform: rotate(180deg); }
    
    .submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        padding-left: 3.25rem;
        list-style: none;
    }
    
    .submenu.open { max-height: 500px; }
    
    .submenu-item {
        padding: 0.5rem 1.25rem 0.5rem 0;
    }
    
    .submenu-item a {
        color: #a7afb7;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.2s;
    }
    
    .submenu-item a:hover { color: white; }
    
    .main-content-wrapper {
        margin-left: 244px;
        transition: margin-left 0.3s ease;
        min-height: 100vh;
    }
    
    .main-content-wrapper.expanded { margin-left: 0; }
    
    .top-navbar {
        background: #191c24;
        height: 70px;
        position: fixed;
        top: 0;
        left: 244px;
        right: 0;
        display: flex;
        align-items: center;
        padding: 0 1rem;
        z-index: 50;
        transition: left 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    
    .top-navbar.expanded { left: 0; }
    
    .toggle-btn {
        background: none;
        border: none;
        color: #a7afb7;
        cursor: pointer;
        padding: 0.5rem;
        margin-right: 1rem;
    }
    
    .toggle-btn:hover { color: white; }
    
    .toggle-btn svg { width: 24px; height: 24px; stroke: currentColor; }
    
    .navbar-actions {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .nav-btn {
        background: #10b981;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.3s;
    }
    
    .nav-btn:hover { background: #059669; }
    
    .icon-btn {
        background: none;
        border: none;
        color: #a7afb7;
        cursor: pointer;
        padding: 0.5rem;
        position: relative;
    }
    
    .icon-btn:hover { color: white; }
    
    .icon-btn svg { width: 20px; height: 20px; stroke: currentColor; }
    
    .badge {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        border: 2px solid #191c24;
    }
    
    .badge.danger { background: #ef4444; }
    
    .content-area {
        padding-top: 70px;
    }
    
    @media (max-width: 1024px) {
        .sidebar { transform: translateX(-244px); }
        .sidebar.open { transform: translateX(0); }
        .main-content-wrapper { margin-left: 0; }
        .top-navbar { left: 0; }
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 35;
            display: none;
        }
        .sidebar-overlay.show { display: block; }
        .nav-btn { display: none; }
    }
</style>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="{{ asset('easygets_logo.jpg') }}" alt="EasyGets Logo">
    </div>
    
    <div class="sidebar-profile">
        <svg class="profile-avatar" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
            <circle cx="32" cy="32" r="32" fill="#e5e7eb"/>
            <circle cx="32" cy="24" r="12" fill="#9ca3af"/>
            <path d="M16 54c0-9 7-16 16-16s16 7 16 16" fill="#9ca3af"/>
        </svg>
        <div class="profile-info">
            @auth('admins')
                <h5>{{ Auth::guard('admins')->user()->name }}</h5>
                <span>{{ ucfirst(Auth::guard('admins')->user()->role) }}</span>
            @else
                <h5>Guest</h5>
                <span>Visitor</span>
            @endauth
        </div>
    </div>
    
    <ul style="list-style: none; margin: 0; padding: 0;">
        <li class="nav-category">Navigation</li>
        
        <li class="menu-item">
            <a class="nav-link {{ request()->is('app/admin') || request()->is('app/admin/') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <span class="nav-icon" style="color: #3b82f6;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="2" x2="12" y2="22"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 12 L12 2 A10 10 0 0 1 22 12 Z" fill="currentColor" opacity="0.15"/>
                    </svg>
                </span>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-item">
            <a class="nav-link {{ request()->is('app/admin/list*') ? 'active' : '' }}" href="{{ route('admin.list') }}">
                <span class="nav-icon" style="color: #f59e0b;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span>
                <span>Admin List</span>
            </a>
        </li>
        
        {{-- <li class="menu-item">
            <a class="nav-link {{ request()->is('admin/services*') ? 'active' : '' }}" href="{{ route('admin.services') }}" data-submenu="services">
                <span class="nav-icon" style="color: #10b981;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </span>
                <span>Service Settings</span>
                <i class="menu-arrow"></i>
            </a>
            <ul class="submenu" id="submenu-services">
                <li class="submenu-item"><a href="{{ route('admin.services') }}">Dashboard Settings</a></li>
                <li class="submenu-item"><a href="#">API Configuration</a></li>
                <li class="submenu-item"><a href="#">Payment Methods</a></li>
            </ul>
        </li> --}}

        <li class="menu-item">
            <a class="nav-link {{ request()->is('app/admin/services*') ? 'active' : '' }}" href="{{ route('admin.services') }}">
                <span class="nav-icon" style="color: #3b82f6;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </span>
                <span>Service Settings</span>
            </a>
        </li>

        <li class="menu-item">
            <a class="nav-link {{ request()->is('app/admin/esim-transaction*') ? 'active' : '' }}" href="{{ route('admin.esim.transaction') }}">
                <span class="nav-icon" style="color: #3b82f6;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </span>
                <span>eSIM Transactions</span>
            </a>
        </li>

        <li class="menu-item">
            <a class="nav-link {{ request()->is('app/admin/esim') || request()->is('app/admin/esim/*') ? 'active' : '' }}" href="{{ route('admin.esim.index') }}">
                <span class="nav-icon" style="color: #8b5cf6;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </span>
                <span>eSIM Countries</span>
            </a>
        </li>

        <li class="menu-item">
            <a class="nav-link {{ request()->is('app/admin/social/services*') ? 'active' : '' }}" href="{{ route('admin.social.services') }}">
                <span class="nav-icon" style="color: #10b981;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
                        <path d="M9 10h6M9 14h6M9 18h6"/>
                    </svg>
                </span>
                <span>Social Services</span>
            </a>
        </li>

        <li class="menu-item">
            <a class="nav-link {{ request()->is('app/admin/logs*') ? 'active' : '' }}" href="{{ route('admin.logs') }}">
                <span class="nav-icon" style="color: #06b6d4;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 7h16M4 12h16M4 17h16"/>
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                    </svg>
                </span>
                <span>Logs Products</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Top Navbar -->
<nav class="top-navbar" id="topNavbar">
    <button class="toggle-btn" id="sidebarToggle">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>
    
    <div class="navbar-actions">
        @auth('admins')
            <button class="nav-btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-weight: 600; padding: 0.6rem 1.2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35); transition: all 0.3s ease; margin-right: 0.75rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(16, 185, 129, 0.55)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.35)'" onclick="document.getElementById('announcementModal').style.display='flex'">
                + Announcement
            </button>
            @if(Auth::guard('admins')->user()->role === 'super_admin')
                <button class="nav-btn" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; font-weight: 600; padding: 0.6rem 1.2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(59, 130, 246, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'" onclick="document.getElementById('addAdminModal').style.display='flex'">
                    + Create Admin
                </button>
            @endif
        @endauth
        
        {{-- <button class="icon-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span class="badge"></span>
        </button>
        
        <button class="icon-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="badge danger"></span>
        </button> --}}
        
        <button class="icon-btn">
            <svg class="profile-avatar" style="width: 32px; height: 32px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                <circle cx="32" cy="32" r="32" fill="#e5e7eb"/>
                <circle cx="32" cy="24" r="12" fill="#9ca3af"/>
                <path d="M16 54c0-9 7-16 16-16s16 7 16 16" fill="#9ca3af"/>
            </svg>
        </button>
    </div>
</nav>

<!-- Announcement Modal -->
<div id="announcementModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #111827; border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; width: 100%; max-width: 520px; overflow: hidden; margin: 20px;">
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 22px; position: relative;">
            <h3 style="color: white; font-size: 22px; font-weight: 700; margin: 0;">Platform Announcement</h3>
            <p style="color: rgba(255,255,255,0.85); font-size: 14px; margin: 8px 0 0;">Share what’s new with users and control visibility.</p>
            <button type="button" onclick="document.getElementById('announcementModal').style.display='none'" style="position: absolute; top: 18px; right: 18px; background: rgba(255,255,255,0.18); border: none; color: white; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; font-size: 22px; transition: all 0.3s;">×</button>
        </div>
        <form method="POST" action="{{ route('admin.announcements.save') }}" style="padding: 26px; background: #0f172a;">
            @csrf
            <div style="margin-bottom: 18px;">
                <label style="display: block; color: #e2e8f0; font-size: 13px; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.8px; text-transform: uppercase;">Announcement Title</label>
                <input type="text" name="title" required placeholder="Headline for users" style="width: 100%; background: rgba(255,255,255,0.06); border: 1px solid rgba(148,163,184,0.2); color: #f8fafc; padding: 14px 16px; border-radius: 12px; font-size: 14px;" />
            </div>
            <div style="margin-bottom: 18px;">
                <label style="display: block; color: #e2e8f0; font-size: 13px; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.8px; text-transform: uppercase;">Details</label>
                <textarea name="body" required rows="5" placeholder="Describe what’s new or important" style="width: 100%; background: rgba(255,255,255,0.06); border: 1px solid rgba(148,163,184,0.2); color: #f8fafc; padding: 14px 16px; border-radius: 12px; font-size: 14px;"></textarea>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; color: #e2e8f0; font-size: 13px; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.8px; text-transform: uppercase;">Starts at</label>
                    <input type="datetime-local" name="starts_at" style="width: 100%; background: rgba(255,255,255,0.06); border: 1px solid rgba(148,163,184,0.2); color: #f8fafc; padding: 12px 14px; border-radius: 12px; font-size: 14px;" />
                </div>
                <div>
                    <label style="display: block; color: #e2e8f0; font-size: 13px; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.8px; text-transform: uppercase;">Ends at</label>
                    <input type="datetime-local" name="ends_at" style="width: 100%; background: rgba(255,255,255,0.06); border: 1px solid rgba(148,163,184,0.2); color: #f8fafc; padding: 12px 14px; border-radius: 12px; font-size: 14px;" />
                </div>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 10px; color: #e2e8f0; font-size: 14px;">
                    <input type="checkbox" name="is_active" value="1" style="width: 18px; height: 18px; border-radius: 8px; border: 1px solid rgba(148,163,184,0.4);" />
                    Show to users now
                </label>
                <button type="submit" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 12px 24px; border-radius: 999px; font-weight: 700; font-size: 14px; cursor: pointer; box-shadow: 0 12px 30px rgba(16,185,129,0.24); transition: transform 0.2s ease;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">Save Announcement</button>
            </div>

            @if(!empty($adminAnnouncements) && $adminAnnouncements->count())
                <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(148,163,184,0.16); border-radius: 14px; padding: 18px;">
                    <h4 style="color: #f8fafc; font-size: 15px; font-weight: 700; margin-bottom: 12px;">Existing announcements</h4>
                    <div style="display: grid; gap: 12px;">
                        @foreach($adminAnnouncements as $announcement)
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; background: rgba(255,255,255,0.04); border: 1px solid rgba(148,163,184,0.18); border-radius: 12px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <span style="font-size: 14px; font-weight: 700; color: #f8fafc;">{{ Str::limit($announcement->title, 42) }}</span>
                                        <span style="font-size: 11px; line-height: 1.2; color: {{ $announcement->is_active ? '#34d399' : '#fbbf24' }}; background: rgba(255,255,255,0.08); padding: 3px 8px; border-radius: 999px;">{{ $announcement->is_active ? 'Live' : 'Draft' }}</span>
                                    </div>
                                    <p style="font-size: 13px; color: #cbd5e1;">{{ Str::limit($announcement->body, 70) }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.announcements.toggle', $announcement) }}">
                                    @csrf
                                    <button type="submit" style="background: {{ $announcement->is_active ? '#f97316' : '#2563eb' }}; color: white; border: none; padding: 10px 16px; border-radius: 999px; font-weight: 700; font-size: 12px; cursor: pointer;">{{ $announcement->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Main Content Wrapper -->
<div class="main-content-wrapper" id="mainContentWrapper">
    <div class="content-area">
        @yield('content')
    </div>
</div>

<!-- Add Admin Modal -->
<div id="addAdminModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: linear-gradient(135deg, #1a1d23 0%, #222630 100%); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6); width: 100%; max-width: 450px; overflow: hidden; margin: 20px;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 24px; position: relative;">
            <h3 style="color: white; font-size: 24px; font-weight: 700; margin: 0; letter-spacing: 0.5px;">Create Admin Account</h3>
            <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin: 8px 0 0 0;">Register a new admin user</p>
            <button type="button" onclick="document.getElementById('addAdminModal').style.display='none'" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">×</button>
        </div>
        
        <!-- Form -->
        <form method="POST" action="{{ route('admin.create') }}" style="padding: 28px;">
            @csrf
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #e5e7eb; font-size: 14px; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Full Name</label>
                <input type="text" name="name" required style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px 14px; border-radius: 8px; font-size: 14px; transition: all 0.3s; box-sizing: border-box;" onfocus="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(59,130,246,0.5)'" onblur="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='rgba(255,255,255,0.1)'" placeholder="Enter full name">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #e5e7eb; font-size: 14px; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Email Address</label>
                <input type="email" name="email" required style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px 14px; border-radius: 8px; font-size: 14px; transition: all 0.3s; box-sizing: border-box;" onfocus="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(59,130,246,0.5)'" onblur="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='rgba(255,255,255,0.1)'" placeholder="Enter email address">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #e5e7eb; font-size: 14px; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Phone Number</label>
                <input type="text" name="phone" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px 14px; border-radius: 8px; font-size: 14px; transition: all 0.3s; box-sizing: border-box;" onfocus="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(59,130,246,0.5)'" onblur="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='rgba(255,255,255,0.1)'" placeholder="Enter phone number (optional)">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #e5e7eb; font-size: 14px; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Password</label>
                <input type="password" name="password" required style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px 14px; border-radius: 8px; font-size: 14px; transition: all 0.3s; box-sizing: border-box;" onfocus="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(59,130,246,0.5)'" onblur="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='rgba(255,255,255,0.1)'" placeholder="Enter password">
            </div>
            
            <div style="margin-bottom: 28px;">
                <label style="display: block; color: #e5e7eb; font-size: 14px; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Admin Role</label>
                <select name="role" required style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 12px 14px; border-radius: 8px; font-size: 14px; transition: all 0.3s; box-sizing: border-box; cursor: pointer;" onfocus="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(59,130,246,0.5)'" onblur="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='rgba(255,255,255,0.1)'">
                    <option value="admin" style="background: #1a1d23; color: white;">Admin</option>
                    <option value="super_admin" style="background: #1a1d23; color: white;">Super Admin</option>
                </select>
            </div>
            
            <!-- Buttons -->
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('addAdminModal').style.display='none'" style="padding: 12px 24px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #e5e7eb; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='white'" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.color='#e5e7eb'">
                    Cancel
                </button>
                <button type="submit" style="padding: 12px 32px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; color: white; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(59, 130, 246, 0.6)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'">
                    Create Admin
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const sidebar = document.getElementById('sidebar');
    const mainContentWrapper = document.getElementById('mainContentWrapper');
    const topNavbar = document.getElementById('topNavbar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const isMobile = () => window.innerWidth <= 1024;
    
    // Toggle sidebar
    sidebarToggle.addEventListener('click', function() {
        if (isMobile()) {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            mainContentWrapper.classList.toggle('expanded');
            topNavbar.classList.toggle('expanded');
        }
    });
    
    // Close sidebar on overlay click
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('show');
    });
    
    // Handle submenu toggles
    document.querySelectorAll('[data-submenu]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const submenuId = 'submenu-' + this.dataset.submenu;
            const submenu = document.getElementById(submenuId);
            const arrow = this.querySelector('.menu-arrow');
            
            submenu.classList.toggle('open');
            arrow.classList.toggle('open');
        });
    });
    
    // Close mobile sidebar when clicking menu items (except submenu triggers)
    if (isMobile()) {
        document.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                if (!this.dataset.submenu) {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('show');
                }
            });
        });
    }
})();
</script>