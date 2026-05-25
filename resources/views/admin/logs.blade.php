@extends('layouts.admin')

@section('title', 'Logs Products')

@section('content')
<div class="dash">
    <div class="inner">
        <div class="top-bar">
            <div class="tb-l">
                <h1>Logs Product Catalog</h1>
                <p>Manage login and IP access products available to customers.</p>
            </div>
        </div>

        <div class="services-panel">
            <div class="panel-header">
                <div class="panel-title-group">
                    <h2 class="panel-title">Add New Logs Product</h2>
                    <span class="pill">Create / update</span>
                </div>
            </div>
            <div class="panel-footer" style="display:block;">
                <form action="{{ route('admin.logs.create') }}" method="POST" class="space-y-4">
                    @csrf
                    <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px;">
                        <label class="block text-sm text-slate-200">
                            Product Name
                            <input type="text" name="name" required maxlength="255" class="w-full mt-2 px-4 py-3 rounded-xl border border-slate-700 bg-slate-950 text-white" placeholder="Facebook login" />
                        </label>
                        <label class="block text-sm text-slate-200">
                            Country (optional)
                            <input type="text" name="country" maxlength="80" class="w-full mt-2 px-4 py-3 rounded-xl border border-slate-700 bg-slate-950 text-white" placeholder="United Kingdom" />
                        </label>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px;">
                        <label class="block text-sm text-slate-200">
                            Country Code
                            <input type="text" name="country_code" maxlength="2" class="w-full mt-2 px-4 py-3 rounded-xl border border-slate-700 bg-slate-950 text-white" placeholder="GB" />
                        </label>
                        <label class="block text-sm text-slate-200">
                            Price (NGN)
                            <input type="number" name="client_price" required step="0.01" min="0" class="w-full mt-2 px-4 py-3 rounded-xl border border-slate-700 bg-slate-950 text-white" placeholder="1500" />
                        </label>
                    </div>
                    <label class="block text-sm text-slate-200">
                        Description (optional)
                        <textarea name="description" rows="3" class="w-full mt-2 px-4 py-3 rounded-xl border border-slate-700 bg-slate-950 text-white" placeholder="Short product details or login type."></textarea>
                    </label>
                    <button type="submit" class="filter-btn" style="background:#10b981; color:#fff; border:none;">Create Logs Product</button>
                </form>
            </div>
        </div>

        <div class="services-panel">
            <div class="panel-header">
                <div class="panel-title-group">
                    <h2 class="panel-title">Existing Logs Products</h2>
                    <span class="pill">{{ $products->count() }} items</span>
                </div>
            </div>
            <div class="service-list">
                @forelse($products as $product)
                    <div class="service-row {{ !$product->is_active ? 'row-disabled' : '' }}">
                        <div class="svc-icon purple">L</div>
                        <div class="svc-info">
                            <div class="svc-name">{{ $product->name }}</div>
                            <div class="svc-meta">
                                <span class="svc-slug">{{ strtoupper($product->product_code) }}</span>
                                <span>{{ $product->country ?? 'Global' }}</span>
                                @if($product->description)
                                    <span>{{ Str::limit($product->description, 50) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="svc-controls">
                            <span class="status-pill {{ $product->is_active ? 'on' : 'off' }}">
                                <span class="status-dot"></span>
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <form action="{{ route('admin.products.toggle', ['id' => $product->id]) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="filter-btn" style="background:{{ $product->is_active ? '#ef4444' : '#22c55e' }}; color:#fff;">{{ $product->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <span class="ei">📝</span>
                        <p>No logs products yet. Create one above to make them available to users.</p>
                    </div>
                @endforelse
            </div>
            <div class="panel-footer info-block">
                <div class="info-icon">💡</div>
                <div class="info-text">Each logs product can be created with an optional country and an optional description. Use country code to surface the correct flag on the user listing.</div>
            </div>
        </div>
    </div>
</div>
@endsection
