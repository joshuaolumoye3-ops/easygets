@extends('layouts.admin')

@section('title', 'Services Management - Admin')

@push('head')
<style>
    .service-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.3s ease;
        margin-bottom: 12px;
    }

    .service-card:hover {
        border-color: var(--border-hi);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .service-info {
        display: flex;
        align-items: center;
        gap: 16px;
        flex: 1;
    }

    .service-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--r-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        background: var(--accent-d);
    }

    .service-details h3 {
        font-size: 16px;
        font-weight: 700;
        color: var(--t1);
        margin-bottom: 4px;
    }

    .service-details p {
        font-size: 12px;
        color: var(--t3);
    }

    .toggle-switch {
        position: relative;
        width: 56px;
        height: 32px;
        background: var(--red-d);
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 1px solid var(--red);
        display: flex;
        align-items: center;
        padding: 0 4px;
    }

    .toggle-switch.active {
        background: var(--green-d);
        border-color: var(--green);
    }

    .toggle-thumb {
        width: 24px;
        height: 24px;
        background: var(--red);
        border-radius: 50%;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .toggle-switch.active .toggle-thumb {
        background: var(--green);
        margin-left: auto;
    }

    .toggle-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-left: 8px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-badge.active {
        background: rgba(16,185,129,0.15);
        color: #6ee7b7;
        border: 1px solid rgba(16,185,129,0.3);
    }

    .status-badge.inactive {
        background: rgba(239,68,68,0.15);
        color: #f87171;
        border: 1px solid rgba(239,68,68,0.3);
    }

    .services-grid {
        display: grid;
        gap: 8px;
    }

    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    @media (max-width: 768px) {
        .service-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .service-info {
            width: 100%;
            margin-bottom: 12px;
        }

        .toggle-switch {
            align-self: flex-end;
            margin-top: -36px;
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
                                @php
                                    $icons = [
                                        'data' => '📡',
                                        'airtime' => '📱',
                                        'electricity' => '⚡',
                                        'cabletv' => '📺',
                                        'betting' => '⚽',
                                        'esim' => '🛰️',
                                        'otp' => '🔐',
                                        'social' => '👥',
                                        'boost' => '🚀',
                                    ];
                                    $icon = $icons[$service->slug] ?? '🔧';
                                @endphp
                                {{ $icon }}
                            </div>
                            <div class="service-details">
                                <h3>{{ $service->name }}</h3>
                                <p style="display:flex; align-items:center; gap:6px;">
                                    Service ID: <span style="font-family:var(--mono); font-weight:600;">{{ $service->slug }}</span>
                                </p>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:16px;">
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
