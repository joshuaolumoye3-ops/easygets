@extends('layouts.admin')

@section('title', 'Services Management - Admin')

@push('head')
<style>
    .service-card {
        background: linear-gradient(135deg, var(--bg-card) 0%, rgba(15, 23, 42, 0.5) 100%);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 22px 24px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 20px;
        align-items: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 14px;
        position: relative;
        overflow: hidden;
    }

    .service-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.02) 0%, transparent 100%);
        pointer-events: none;
    }

    .service-card:hover {
        transform: translateY(-3px);
        border-color: var(--accent);
        box-shadow: 0 12px 32px rgba(59, 130, 246, 0.15);
    }

    .service-info {
        display: flex;
        align-items: center;
        gap: 18px;
        flex: 1;
        position: relative;
        z-index: 1;
    }

    .service-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0.05) 100%);
        color: #818cf8;
        flex-shrink: 0;
        border: 1px solid rgba(99, 102, 241, 0.2);
    }

    .service-icon svg {
        width: 24px;
        height: 24px;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .service-details h3 {
        font-size: 16px;
        font-weight: 700;
        color: var(--t1);
        margin: 0 0 6px 0;
        letter-spacing: -0.3px;
    }

    .service-details p {
        font-size: 13px;
        color: var(--t3);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .service-details .service-slug {
        font-family: var(--mono);
        font-weight: 700;
        color: var(--accent);
        background: var(--accent-d);
        padding: 2px 8px;
        border-radius: 4px;
        letter-spacing: 0.5px;
    }

    .service-actions {
        display: flex;
        align-items: center;
        gap: 16px;
        position: relative;
        z-index: 1;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .status-badge.active {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .status-badge.inactive {
        background: rgba(239, 68, 68, 0.15);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .status-badge::before {
        content: '';
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Modern toggle switch */
    .toggle-switch {
        position: relative;
        width: 52px;
        height: 30px;
        border-radius: 999px;
        background: #374151;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid #4b5563;
        display: flex;
        align-items: center;
        padding: 0 4px;
        flex-shrink: 0;
    }

    .toggle-switch:hover {
        border-color: #5f6b7a;
        background: #3f4956;
    }

    .toggle-switch.active {
        background: #22c55e;
        border-color: #16a34a;
        box-shadow: 0 0 12px rgba(34, 197, 94, 0.4);
    }

    .toggle-thumb {
        position: absolute;
        top: 3px;
        left: 4px;
        width: 22px;
        height: 22px;
        background: white;
        border-radius: 50%;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    }

    .toggle-switch.active .toggle-thumb {
        transform: translateX(22px);
    }

    .services-grid {
        display: grid;
        gap: 12px;
    }

    /* Empty state */
    .empty-st {
        text-align: center;
        padding: 60px 40px;
        opacity: 0.8;
    }

    .empty-st .ei {
        font-size: 48px;
        margin-bottom: 16px;
        display: block;
    }

    .empty-st p {
        font-size: 14px;
        color: var(--t3);
        margin: 0;
    }

    /* Loading state - should NOT hide content */
    .service-card.loading {
        opacity: 0.85;
        pointer-events: none;
    }

    .service-card.loading .toggle-switch {
        opacity: 0.6;
    }

    /* Info box improvements */
    .info-box {
        margin-top: 28px;
        padding: 24px;
        background: linear-gradient(135deg, var(--accent-d) 0%, rgba(59, 130, 246, 0.05) 100%);
        border: 1px solid var(--accent);
        border-radius: 16px;
        border-left: 4px solid var(--accent);
    }

    .info-box-header {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        margin-bottom: 0;
    }

    .info-box-icon {
        font-size: 24px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .info-box-content p {
        font-weight: 700;
        color: var(--t1);
        margin: 0 0 12px 0;
        font-size: 14px;
    }

    .info-box-content ul {
        font-size: 13px;
        color: var(--t2);
        line-height: 1.8;
        margin: 0;
        padding-left: 20px;
    }

    .info-box-content li {
        margin-bottom: 8px;
    }

    .info-box-content strong {
        color: var(--accent);
        font-weight: 700;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .service-card {
            grid-template-columns: 1fr;
            gap: 14px;
            padding: 16px;
        }

        .service-icon {
            width: 44px;
            height: 44px;
        }

        .service-actions {
            width: 100%;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }

        .status-badge {
            font-size: 11px;
            padding: 6px 11px;
        }

        .toggle-switch {
            width: 48px;
            height: 28px;
        }

        .toggle-thumb {
            width: 20px;
            height: 20px;
        }

        .toggle-switch.active .toggle-thumb {
            transform: translateX(20px);
        }

        .info-box {
            padding: 16px;
        }
    }

    @media (max-width: 480px) {
        .service-details h3 {
            font-size: 14px;
        }

        .service-details p {
            font-size: 12px;
        }

        .status-badge {
            flex: 1;
        }
    }

    /* Flash message improvements */
    .flash {
        margin-bottom: 20px;
        padding: 14px 16px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInDown 0.3s ease;
    }

    .flash.ok {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .flash.err {
        background: rgba(239, 68, 68, 0.15);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .flash::before {
        content: '';
        display: inline-block;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: currentColor;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
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
            <p>Enable or disable services available to your users</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="flash ok">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="flash err">
        {{ session('error') }}
    </div>
    @endif

    {{-- Services List --}}
    <div class="tc">
        <div class="tc-head">
            <div class="tc-title">
                <h3>Available Services</h3>
                <span class="pill">{{ $services->count() }} Total</span>
            </div>
        </div>

        <div style="padding:28px;">
            @if($services && count($services) > 0)
                <div class="services-grid">
                    @foreach($services as $service)
                    <div class="service-card" data-service-id="{{ $service->id }}">
                        <div class="service-info">
                            <div class="service-icon">
                                @switch($service->slug)
                                    @case('data')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                    </svg>
                                    @break

                                    @case('airtime')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 12h.01M16 12h.01M9 8a4 4 0 118 0 4 4 0 01-8 0z"/>
                                    </svg>
                                    @break

                                    @case('electricity')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    @break

                                    @case('cabletv')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16h10V4H7zm4 12h2m-5-3h8M8 8h8"/>
                                    </svg>
                                    @break

                                    @case('betting')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    @break

                                    @case('otp')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    @break

                                    @case('social-boost')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    @break

                                    @case('esim')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M9 18h.01M15 18h.01M8 14h8a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                    </svg>
                                    @break

                                    @default
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                @endswitch
                            </div>
                            <div class="service-details">
                                <h3>{{ $service->name }}</h3>
                                <p>
                                    Service ID: <span class="service-slug">{{ $service->slug }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="service-actions">
                            <span class="status-badge {{ $service->is_active ? 'active' : 'inactive' }}">
                                {{ $service->is_active ? 'Active' : 'Inactive' }}
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
                    <span class="ei">📭</span>
                    <p>No services found in the system yet.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Info Box --}}
    <div class="info-box">
        <div class="info-box-header">
            <span class="info-box-icon">ℹ️</span>
            <div class="info-box-content">
                <p>How Service Management Works</p>
                <ul>
                    <li><strong>Active Services:</strong> Users can access these services through all available channels</li>
                    <li><strong>Inactive Services:</strong> Users are redirected to dashboard with a maintenance message</li>
                    <li><strong>Protected Links:</strong> Direct URLs to disabled services are automatically blocked</li>
                    <li><strong>Audit Trail:</strong> All status changes are logged for security and accountability</li>
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

        element.classList.toggle('active');
        
        const badge = card.querySelector('.status-badge');
        if (data.is_active) {
            badge.classList.remove('inactive');
            badge.classList.add('active');
            badge.textContent = 'Active';
            element.title = 'Click to deactivate';
        } else {
            badge.classList.remove('active');
            badge.classList.add('inactive');
            badge.textContent = 'Inactive';
            element.title = 'Click to activate';
        }

        showFlashMessage(data.message, 'success');

    } catch (error) {
        console.error('Toggle service error:', error);
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
    messageDiv.style.cssText = 'position:fixed; top:20px; right:20px; z-index:9999; max-width:400px;';
    messageDiv.textContent = message;

    document.body.appendChild(messageDiv);

    setTimeout(() => {
        messageDiv.style.animation = 'slideInDown 0.3s ease reverse';
        setTimeout(() => messageDiv.remove(), 300);
    }, 4000);
}
</script>

@endsection
