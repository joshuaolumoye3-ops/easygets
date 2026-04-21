@extends('layouts.admin')
@section('title', 'Manage Services')

@section('content')
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background-color: #0f172a; }
    
    .container { padding-top: 70px; min-height: 100vh; background: linear-gradient(135deg, #0f172a 0%, #1a202c 100%); }
    .main-content { padding: 40px 30px; max-width: 1400px; margin: 0 auto; }
    
    .header {
        background: #1e293b;
        border-radius: 16px;
        padding: 28px 32px;
        margin-bottom: 40px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 2px 6px rgba(0,0,0,0.2);
        border: 1px solid #334155;
    }
    
    .header h1 { font-size: 32px; font-weight: 700; color: #f1f5f9;  margin-bottom: 8px; }
    .header p { font-size: 15px; color: #cbd5e1; }
    
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 28px;
    }
    
    .service-card {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 16px;
        padding: 32px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3), 0 2px 6px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 240px;
    }
    
    .service-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2), 0 8px 24px rgba(0,0,0,0.3);
        transform: translateY(-4px);
    }
    
    .service-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin-bottom: 20px;
    }
    
    .service-name {
        font-size: 22px;
        font-weight: 700;
        color: #f1f5f9;
        margin-bottom: 10px;
    }
    
    .service-description {
        font-size: 14px;
        color: #cbd5e1;
        margin-bottom: 24px;
        flex-grow: 1;
    }
    
    .service-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 16px;
        border-top: 1px solid #334155;
    }
    
    .service-count {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 600;
    }
    
    .service-arrow {
        color: #3b82f6;
        font-size: 20px;
        transition: transform 0.3s ease;
    }
    
    .service-card:hover .service-arrow {
        transform: translateX(4px);
    }
    
    .empty-state {
        padding: 60px 40px;
        text-align: center;
        background: #1e293b;
        border: 2px dashed #334155;
        border-radius: 16px;
    }
    
    .empty-state svg {
        width: 80px;
        height: 80px;
        color: #475569;
        margin: 0 auto 24px;
    }
    
    .empty-state h2 {
        font-size: 20px;
        font-weight: 700;
        color: #f1f5f9;
        margin-bottom: 8px;
    }
    
    .empty-state p {
        font-size: 15px;
        color: #94a3b8;
    }
    .header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    /* Button */
    .manage-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(59,130,246,0.25);
    }

    .manage-btn svg {
        width: 16px;
        height: 16px;
    }

    .manage-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(59,130,246,0.35);
    }

    /* Mobile fix */
    @media (max-width: 768px) {
        .header-top {
            flex-direction: column;
            align-items: flex-start;
        }

        .manage-btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 768px) {
        .services-grid {
            grid-template-columns: 1fr;
        }
        .header { padding: 20px 24px; }
        .header h1 { font-size: 24px; }
    }
</style>

<div class="container">
    <div class="main-content">
        
        <div class="header">
            <div class="header-top">
                <div>
                    <h1>📋 Manage Services</h1>
                    <p>Manage all platform services and their configurations</p>
                </div>

                <a href="{{ route('admin.manage-services') }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Manage Service
                </a>
            </div>
        </div>

        <!-- Services Grid -->
        @if($services && $services->count() > 0)
            <div class="services-grid">
                @foreach($services as $service)
                    <div class="service-card" onclick="window.location='{{ route('admin.providers', $service->id) }}'">
                        <div>
                            <div class="service-icon">🔧</div>
                            <h2 class="service-name">{{ $service->name }}</h2>
                            <p class="service-description">Click to manage {{ $service->name }} providers and configurations</p>
                        </div>
                        <div class="service-footer">
                            <span class="service-count">Service ID: #{{ $service->id }}</span>
                            <span class="service-arrow">→</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h2>No Services Found</h2>
                <p>There are currently no services available to manage.</p>
            </div>
        @endif
        
    </div>
</div>
@endsection

