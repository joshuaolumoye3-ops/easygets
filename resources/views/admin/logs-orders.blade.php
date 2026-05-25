@extends('layouts.admin')

@section('title', 'Logs Orders')

@section('content')
<div class="dash">
    <div class="inner">
        <div class="top-bar">
            <div class="tb-l">
                <h1>Logs Orders</h1>
                <p>Review pending logs orders and fulfil them with login details.</p>
            </div>
        </div>

        <div class="services-panel">
            <div class="panel-header">
                <div class="panel-title-group">
                    <h2 class="panel-title">Pending and Completed Orders</h2>
                    <span class="pill">{{ $transactions->total() }} total</span>
                </div>
            </div>
            <div class="service-list">
                @forelse($transactions as $transaction)
                    @php
                        $response = is_array($transaction->response) ? $transaction->response : json_decode($transaction->response ?? '[]', true) ?: [];
                    @endphp
                    <div class="service-row {{ strtolower($transaction->status) !== 'pending' ? '' : 'row-disabled' }}" style="grid-template-columns: 1fr auto;">
                        <div class="svc-info">
                            <div class="svc-name">{{ $transaction->service_name ?? $transaction->recipient ?? 'Logs Order' }}</div>
                            <div class="svc-meta">
                                <span class="svc-slug">{{ $transaction->reference }}</span>
                                <span>{{ $transaction->country_name ?? 'Global' }}</span>
                                <span>{{ $transaction->amount ? '₦' . number_format($transaction->amount, 2) : '' }}</span>
                            </div>
                            <p class="svc-desc">{{ $transaction->description ?? 'User purchased a logs access product.' }}</p>
                            @if(!empty($response['login_details']))
                                <p class="svc-desc">Login details: {{ Str::limit($response['login_details'], 120) }}</p>
                            @endif
                        </div>
                        <div class="svc-controls" style="flex-direction:column; align-items:flex-end; gap:12px;">
                            <span class="status-pill {{ strtolower($transaction->status) === 'success' ? 'on' : (strtolower($transaction->status) === 'pending' ? 'off' : 'off') }}">
                                <span class="status-dot"></span>
                                {{ ucfirst($transaction->status) }}
                            </span>

                            @if(strtolower($transaction->status) === 'pending')
                                <button onclick="document.getElementById('fulfill-form-{{ $transaction->id }}').classList.toggle('hidden')" class="filter-btn" style="background:#3b82f6; color:#fff;">Fulfil</button>
                            @endif
                        </div>
                    </div>
                    @if(strtolower($transaction->status) === 'pending')
                        <div id="fulfill-form-{{ $transaction->id }}" class="service-row hidden" style="grid-template-columns: 1fr; padding: 18px 22px 22px; background: rgba(255,255,255,0.04);">
                            <form action="{{ route('admin.logs.fulfill', ['id' => $transaction->id]) }}" method="POST" class="space-y-4">
                                @csrf
                                <label class="block text-sm text-slate-200">
                                    Login Details
                                    <textarea name="login_details" rows="3" required class="w-full mt-2 px-4 py-3 rounded-xl border border-slate-700 bg-slate-950 text-white" placeholder="Username, password, IP, port, or platform info"></textarea>
                                </label>
                                <label class="block text-sm text-slate-200">
                                    Notes (optional)
                                    <textarea name="notes" rows="2" class="w-full mt-2 px-4 py-3 rounded-xl border border-slate-700 bg-slate-950 text-white" placeholder="Any support notes for the user."></textarea>
                                </label>
                                <button type="submit" class="filter-btn" style="background:#10b981; color:#fff;">Mark as Fulfilled</button>
                            </form>
                        </div>
                    @endif
                @empty
                    <div class="empty-state">
                        <span class="ei">📦</span>
                        <p>No logs orders yet. Orders will appear here once users purchase logs access.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="panel-footer info-block">
            <div class="info-icon">⚡</div>
            <div class="info-text">Fulfilled logs orders automatically transition to success, and the user can see login details on their history page.</div>
        </div>

        <div class="mt-6">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
