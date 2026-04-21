@extends('layouts.admin')

@section('title', 'Services Management - Admin')

@push('head')
<style>
    .service-card {
        background: linear-gradient(145deg, var(--bg-card), #0f172a);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.25s ease;
        margin-bottom: 12px;
        position: relative;
    }

    .service-card:hover {
        transform: translateY(-2px);
        border-color: var(--accent);
        box-shadow: 0 8px 24px rgba(0,0,0,0.35);
    }

    .service-info {
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 1;
    }

    .service-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(99,102,241,0.12);
        color: #818cf8;
    }

    .service-icon svg {
        width: 22px;
        height: 22px;
    }

    .service-details h3 {
        font-size: 15px;
        font-weight: 600;
        color: var(--t1);
        margin-bottom: 3px;
    }

    .service-details p {
        font-size: 12px;
        color: var(--t3);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
    }

    .status-badge.active {
        background: rgba(16,185,129,0.12);
        color: #34d399;
    }

    .status-badge.inactive {
        background: rgba(239,68,68,0.12);
        color: #f87171;
    }

    /* Modern toggle */
    .toggle-switch {
        position: relative;
        width: 48px;
        height: 26px;
        border-radius: 999px;
        background: #1f2937;
        cursor: pointer;
        transition: 0.25s;
    }

    .toggle-switch.active {
        background: #22c55e;
    }

    .toggle-thumb {
        position: absolute;
        top: 3px;
        left: 4px;
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        transition: 0.25s;
    }

    .toggle-switch.active .toggle-thumb {
        transform: translateX(20px);
    }

    .services-grid {
        display: grid;
        gap: 10px;
    }

    /* Empty state */
    .empty-st {
        text-align: center;
        padding: 40px;
        opacity: 0.7;
    }

    .empty-st svg {
        width: 40px;
        margin-bottom: 10px;
    }

    /* Mobile */
    @media (max-width: 768px) {
        .service-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .service-actions {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
            filter: blur(0.3px);
        }
    }
</style>
@endpush

@section('content')
<div class="d-wrap">
<div class="d-inner">

    {{-- Top bar --}}
    <div class="top-bar">
        <div class="tb-l">
            <h1>Services Management</h1>
            <p>Manage and control service availability</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="flash ok" style="display:block; margin-bottom:16px;">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="flash err" style="display:block; margin-bottom:16px;">
        {{ session('error') }}
    </div>
    @endif

    {{-- Services List --}}
    <div class="tc">
        <div class="tc-head">
            <div class="tc-title">
                <h3>Available Services</h3>
                <span class="pill">{{ $services->count() }}</span>
            </div>
        </div>

        <div style="padding:24px;">
            @if($services && count($services) > 0)
                <div class="services-grid">
                    @foreach($services as $service)
                    <div class="service-card" data-service-id="{{ $service->id }}">
                        <div class="service-info">
                            <div class="service-icon">
                                @switch($service->slug)

                                    @case('data')
                                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                                    </svg>
                                    @break

                                    @case('airtime')
                                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M7 4h10v16H7z"/>
                                    </svg>
                                    @break

                                    @case('electricity')
                                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/>
                                    </svg>
                                    @break

                                    @case('cabletv')
                                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <rect x="3" y="5" width="18" height="12" rx="2"/>
                                    </svg>
                                    @break

                                    @case('betting')
                                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9"/>
                                    </svg>
                                    @break

                                    @case('otp')
                                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M12 15v2m6-6V9a6 6 0 10-12 0v2"/>
                                    </svg>
                                    @break

                                    @default
                                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path d="M10 14l2-2 2 2m0-6l-2 2-2-2"/>
                                    </svg>

                                @endswitch
                            </div>
                            <div class="service-details">
                                <h3>{{ $service->name }}</h3>
                                <p style="display:flex; align-items:center; gap:6px;">
                                    Service ID: <span style="font-family:var(--mono); font-weight:600;">{{ $service->slug }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="service-actions">
                            <span class="status-badge {{ $service->is_active ? 'active' : 'inactive' }}">
                                <span>●</span>
                                {{ $service->is_active ? '✓ Active' : '✗ Inactive' }}
                            </span>
                            <div class="toggle-switch {{ $service->is_active ? 'active' : '' }}" 
                                 onclick="toggleService(this, {{ $service->id }})" 
                                 title="{{ $service->is_active ? 'Click to deactivate' : 'Click to activate' }}">
                                <div class="toggle-thumb"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="empty-st">
                    <div class="ei">🔍</div>
                    <p>No services found. Please add services first.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Info Box --}}
    <div style="margin-top: 24px; padding:20px; background:var(--accent-d); border:1px solid var(--accent); border-radius:var(--r-lg); border-left:3px solid var(--accent);">
        <div style="display:flex; gap:12px; align-items:flex-start;">
            <div style="font-size:20px; margin-top:2px;">ℹ️</div>
            <div>
                <p style="font-weight:700; color:var(--t1); margin-bottom:6px;">How Service Management Works</p>
                <ul style="font-size:12px; color:var(--t2); line-height:1.6; margin:0; padding-left:16px;">
                    <li><strong>Active Services:</strong> Users can access these services normally through all available channels</li>
                    <li><strong>Inactive Services:</strong> When disabled, users are redirected to the dashboard with a maintenance message</li>
                    <li><strong>All Links Protected:</strong> Direct links to inactive services are also blocked automatically</li>
                    <li><strong>Audit Trail:</strong> All service status changes are logged for security and accountability</li>
                </ul>
            </div>
        </div>
    </div>

</div>
</div>

<script>
const BASE = @json(url('/'));
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

async function toggleService(element, serviceId) {
    const card = element.closest('.service-card');
    const originalState = element.classList.contains('active');

    try {
        // Add loading state
        card.classList.add('loading');
        element.style.pointerEvents = 'none';

        const response = await fetch(`${BASE}/app/admin/services/${serviceId}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to update service status');
        }

        // Update UI
        element.classList.toggle('active');
        
        // Update status badge
        const badge = card.querySelector('.status-badge');
        if (data.is_active) {
            badge.classList.remove('inactive');
            badge.classList.add('active');
            badge.innerHTML = '<span>●</span> ✓ Active';
            element.title = 'Click to deactivate';
        } else {
            badge.classList.remove('active');
            badge.classList.add('inactive');
            badge.innerHTML = '<span>●</span> ✗ Inactive';
            element.title = 'Click to activate';
        }

        // Show success message
        showFlashMessage(data.message, 'success');

    } catch (error) {
        console.error('Toggle service error:', error);

        // Revert UI on error
        element.classList.toggle('active');
        showFlashMessage(error.message || 'Failed to update service status', 'error');

    } finally {
        card.classList.remove('loading');
        element.style.pointerEvents = 'auto';
    }
}

function showFlashMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `flash ${type === 'success' ? 'ok' : 'err'}`;
    messageDiv.style.cssText = 'position:fixed; top:20px; right:20px; z-index:9999; animation:slideIn 0.3s ease;';
    messageDiv.textContent = message;

    document.body.appendChild(messageDiv);

    setTimeout(() => {
        messageDiv.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => messageDiv.remove(), 300);
    }, 4000);
}

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

@endsection
