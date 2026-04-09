@extends('layouts.admin')

@section('title', 'Manage Social Service Descriptions')

@section('content')
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background-color: #0f172a; }

    .social-container { padding-top: 70px; min-height: 100vh; background: linear-gradient(135deg, #0f172a 0%, #1a202c 100%); }
    .main-content { padding: 40px 30px; max-width: 1400px; margin: 0 auto; }

    /* Header Section */
    .header-bar {
        background: #1e293b;
        border-radius: 16px;
        padding: 24px 28px;
        margin-bottom: 32px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border: 1px solid #334155;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .header-left h1 {
        font-size: 28px;
        font-weight: 700;
        color: #f1f5f9;
        margin-bottom: 4px;
    }

    .header-left p {
        font-size: 14px;
        color: #cbd5e1;
        font-weight: 500;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .refresh-btn {
        padding: 12px 20px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .refresh-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }

    .refresh-btn:active {
        transform: translateY(0);
    }

    /* Search Section */
    .search-section {
        background: #1e293b;
        border-radius: 16px;
        padding: 24px 28px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border: 1px solid #334155;
    }

    .search-box {
        position: relative;
        max-width: 500px;
    }

    .search-box input {
        width: 100%;
        padding: 14px 20px 14px 50px;
        border: 2px solid #334155;
        border-radius: 12px;
        font-size: 16px;
        background-color: #0f172a;
        color: #f1f5f9;
        transition: all 0.3s ease;
        font-family: inherit;
    }

    .search-box input:focus {
        outline: none;
        border-color: #3b82f6;
        background-color: #1e293b;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .search-box input::placeholder {
        color: #94a3b8;
    }

    .search-box svg {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        color: #94a3b8;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: #1e293b;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border: 1px solid #334155;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        transform: translateY(-2px);
        border-color: #475569;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #8b5cf6, #a855f7);
    }

    .stat-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .stat-info {
        flex: 1;
    }

    .stat-label {
        font-size: 13px;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #f1f5f9;
        margin-bottom: 8px;
    }

    .stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background-color: #10b98144;
        color: #86efac;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
        color: white;
    }

    /* Table Section */
    .table-card {
        background: #1e293b;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        overflow: hidden;
        border: 1px solid #334155;
    }

    .table-header {
        padding: 24px 28px;
        border-bottom: 1px solid #334155;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .table-header h3 {
        font-size: 20px;
        font-weight: 700;
        color: #f1f5f9;
    }

    .table-badge {
        padding: 6px 14px;
        background-color: #1e3a8a;
        color: #93c5fd;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead tr {
        background-color: #0f172a;
    }

    th {
        padding: 16px 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #334155;
        white-space: nowrap;
    }

    td {
        padding: 16px 20px;
        border-bottom: 1px solid #334155;
        font-size: 14px;
        color: #cbd5e1;
        vertical-align: top;
    }

    tbody tr {
        transition: all 0.2s ease;
        cursor: pointer;
    }

    tbody tr:hover {
        background-color: #334155;
        transform: scale(1.01);
    }

    .service-name {
        font-weight: 600;
        color: #f1f5f9;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .custom-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .description-text {
        color: #94a3b8;
        font-size: 13px;
        line-height: 1.4;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .action-btn {
        padding: 8px 16px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .empty-state svg {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        color: #475569;
    }

    .empty-state h4 {
        font-size: 18px;
        font-weight: 600;
        color: #f1f5f9;
        margin-bottom: 8px;
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background-color: rgba(0,0,0,0.7);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
        backdrop-filter: blur(4px);
    }

    .modal.active {
        display: flex;
        animation: modalFadeIn 0.3s ease;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        background: #1e293b;
        border-radius: 20px;
        width: 100%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        animation: modalSlideUp 0.3s ease;
        border: 1px solid #334155;
    }

    @keyframes modalSlideUp {
        from {
            transform: translateY(20px) scale(0.95);
            opacity: 0;
        }
        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    .modal-header {
        padding: 24px 28px;
        border-bottom: 1px solid #334155;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 {
        font-size: 24px;
        font-weight: 700;
        color: #f1f5f9;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 28px;
        color: #94a3b8;
        cursor: pointer;
        transition: all 0.2s ease;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .close-btn:hover {
        color: #cbd5e1;
        background-color: #334155;
    }

    .modal-body {
        padding: 28px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #f1f5f9;
        margin-bottom: 8px;
    }

    .form-input, .form-textarea {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #334155;
        border-radius: 12px;
        font-size: 14px;
        font-family: inherit;
        color: #f1f5f9;
        background-color: #0f172a;
        transition: all 0.3s ease;
        resize: vertical;
    }

    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        background-color: #1e293b;
    }

    .form-input::placeholder, .form-textarea::placeholder {
        color: #64748b;
    }

    .form-textarea {
        min-height: 120px;
        line-height: 1.5;
    }

    .form-help {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 6px;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 24px;
        border-top: 1px solid #334155;
        margin-top: 28px;
    }

    .btn {
        padding: 12px 20px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 100px;
    }

    .btn-secondary {
        background-color: #475569;
        color: #cbd5e1;
    }

    .btn-secondary:hover {
        background-color: #334155;
        color: #f1f5f9;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    /* Loading States */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        margin: 0 8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Alert Messages */
    .alert {
        padding: 16px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .alert-success {
        background-color: #064e3b;
        color: #86efac;
        border-left: 4px solid #10b981;
    }

    .alert-error {
        background-color: #7f1d1d;
        color: #fca5a5;
        border-left: 4px solid #ef4444;
    }

    .alert svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .header-bar {
            flex-direction: column;
            align-items: flex-start;
            text-align: center;
        }

        .header-left h1 {
            font-size: 24px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .table-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .modal-content {
            margin: 10px;
            max-width: none;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }

        th, td {
            padding: 12px 16px;
            font-size: 13px;
        }

        .service-name {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
    }

    @media (max-width: 640px) {
        .main-content {
            padding: 20px 16px;
        }

        .header-bar, .search-section, .table-card {
            padding: 20px;
        }

        .stat-card {
            padding: 20px;
        }

        .stat-value {
            font-size: 24px;
        }
    }
</style>

<div class="social-container">
    <div class="main-content">

        <!-- Header -->
        <div class="header-bar">
            <div class="header-left">
                <h1>Social Service Management</h1>
                <p>Manage descriptions and settings for social media services</p>
            </div>
            <div class="header-right">
                <button onclick="refreshServices()" class="refresh-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh Services
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <div class="stat-label">Total Services</div>
                        <div class="stat-value">{{ $services->count() }}</div>
                        <div class="stat-badge">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            Active
                        </div>
                    </div>
                    <div class="stat-icon">📊</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <div class="stat-label">Custom Names</div>
                        <div class="stat-value">{{ $services->where('custom_name', '!=', '')->count() }}</div>
                        <div class="stat-badge">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            Modified
                        </div>
                    </div>
                    <div class="stat-icon">✏️</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-info">
                        <div class="stat-label">With Descriptions</div>
                        <div class="stat-value">{{ $services->where('description', '!=', '')->count() }}</div>
                        <div class="stat-badge">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                            </svg>
                            Complete
                        </div>
                    </div>
                    <div class="stat-icon">📝</div>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search services by name, category, or platform..."
                    onkeyup="filterServices()"
                >
            </div>
        </div>

        <!-- Services Table -->
        <div class="table-card">
            <div class="table-header">
                <h3>Social Media Services</h3>
                <span class="table-badge">{{ $services->count() }} services</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Service ID</th>
                            <th>Service Name</th>
                            <th>Admin %</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTable">
                        @forelse($services as $service)
                            <tr class="service-row">
                                <td>
                                    <span style="font-family: 'Courier New', monospace; background: #334155; padding: 4px 8px; border-radius: 6px; font-size: 12px;">{{ $service['service_id'] }}</span>
                                </td>
                                <td>
                                    <div class="service-name">
                                        {{ $service['name'] }}
                                        @if(!empty($service['custom_name']))
                                            <span class="custom-badge">custom</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: #10b981;">{{ $service['admin_percentage'] ?? 0 }}%</span>
                                </td>
                                <td>
                                    <span style="background: #475569; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 500;">{{ $service['category'] }}</span>
                                </td>
                                <td>
                                    <span style="background: #1e3a8a; color: #93c5fd; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 500;">{{ $service['type'] }}</span>
                                </td>
                                <td>
                                    <div class="description-text" title="{{ $service['description'] ?? 'No description' }}">
                                        {{ $service['description'] ? Str::limit($service['description'], 60) : 'No description available' }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <button
                                        onclick="openDescriptionModal('{{ $service['service_id'] }}', '{{ addslashes($service['name']) }}', {{ json_encode($service['description'] ?? '') }}, {{ json_encode($service['custom_name'] ?? '') }}, {{ json_encode($service['category'] ?? '') }}, {{ json_encode($service['admin_percentage'] ?? 0) }})"
                                        class="action-btn"
                                    >
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                    <h4>No Services Available</h4>
                                    <p>Click the "Refresh Services" button to load services from the API.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Description Modal -->
<div id="descriptionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Service Configuration</h2>
            <button class="close-btn" onclick="closeDescriptionModal()">×</button>
        </div>

        <div class="modal-body">
            <form id="descriptionForm" method="POST" action="{{ route('admin.social.updateDescription') }}">
                @csrf
                <input type="hidden" name="service_id" id="modalServiceId">

                <div class="form-group">
                    <label class="form-label">Service Name (Custom)</label>
                    <input
                        type="text"
                        name="custom_name"
                        id="modalServiceName"
                        placeholder="Enter custom service name (optional)"
                        class="form-input"
                    />
                    <p class="form-help">Leave empty to use the default API name</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Category (Custom)</label>
                    <input
                        type="text"
                        name="custom_category"
                        id="modalServiceCategory"
                        placeholder="Enter custom category (optional)"
                        class="form-input"
                    />
                    <p class="form-help">Leave empty to use the default category</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Admin Markup Percentage</label>
                    <input
                        type="number"
                        name="admin_percentage"
                        id="modalAdminPercentage"
                        min="0"
                        step="1"
                        placeholder="e.g., 10"
                        class="form-input"
                    />
                    <p class="form-help">Percentage added to base price (e.g., 10 = 10% markup)</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Service Description</label>
                    <textarea
                        name="description"
                        id="modalDescription"
                        rows="6"
                        placeholder="Enter detailed service description..."
                        class="form-textarea"
                    ></textarea>
                    <p class="form-help">Provide clear information about what this service offers</p>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="closeDescriptionModal()" class="btn btn-secondary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Setup CSRF token for all AJAX requests
const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.csrfToken = csrfToken.getAttribute('content');
}

function openDescriptionModal(serviceId, serviceName, description, customName = '', customCategory = '', adminPercentage = 0) {
    document.getElementById('modalServiceId').value = serviceId;
    // If admin has provided a custom name, show it; otherwise show the original API name
    document.getElementById('modalServiceName').value = customName || serviceName;
    document.getElementById('modalServiceCategory').value = customCategory || '';
    document.getElementById('modalAdminPercentage').value = adminPercentage || 0;
    document.getElementById('modalDescription').value = description || '';
    document.getElementById('descriptionModal').classList.add('active');
}

function closeDescriptionModal() {
    document.getElementById('descriptionModal').classList.remove('active');
}

function filterServices() {
    const searchValue = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.service-row');

    rows.forEach(row => {
        const serviceId = row.cells[0].textContent.toLowerCase();
        const serviceName = row.cells[1].textContent.toLowerCase();
        const adminPercentage = row.cells[2].textContent.toLowerCase();
        const category = row.cells[3].textContent.toLowerCase();
        const type = row.cells[4].textContent.toLowerCase();
        const description = row.cells[5].textContent.toLowerCase();

        if (serviceId.includes(searchValue) ||
            serviceName.includes(searchValue) ||
            adminPercentage.includes(searchValue) ||
            category.includes(searchValue) ||
            type.includes(searchValue) ||
            description.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function refreshServices() {
    const button = event.target.closest('button');
    if (button) {
        button.classList.add('loading');
        button.innerHTML = '<span class="loading"></span> Refreshing...';
    }
    window.location.href = "{{ route('admin.social.refresh') }}";
}

// Close modal when clicking outside
document.getElementById('descriptionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDescriptionModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('descriptionModal').classList.contains('active')) {
        closeDescriptionModal();
    }
});

// Ensure form has fresh CSRF token on submit
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('descriptionForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.classList.add('loading');
                button.innerHTML = '<span class="loading"></span> Saving...';
            }

            // Refresh CSRF token from meta tag
            const token = document.querySelector('meta[name="csrf-token"]');
            const csrfInput = form.querySelector('input[name="_token"]');
            if (token && csrfInput) {
                csrfInput.value = token.getAttribute('content');
            }
        });
    }
});
</script>
@endsection

