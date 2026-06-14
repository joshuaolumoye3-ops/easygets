@extends('layouts.admin')
@section('title', 'Manage eSIM Countries')

@section('content')
<main style="padding-top:70px; width:100%; display:flex; flex-direction:column; min-height:100vh; background:#0b0d12;">
<div style="padding:32px 28px; width:100%; max-width:1400px; margin:0 auto; flex-grow:1;">

    <!-- HEADER -->
    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:20px; margin-bottom:32px;">
        <div>
            <h1 style="font-size:28px; font-weight:700; color:#fff; margin:0;">eSIM Countries</h1>
            <p style="color:#9ca3af; font-size:14px; margin:6px 0 0 0;">Manage countries, pricing, descriptions and packages</p>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <button onclick="seedCountries()"
                style="background:linear-gradient(90deg,#16a34a,#15803d);color:white;padding:11px 18px;border-radius:10px;border:none;font-size:13px;font-weight:600;cursor:pointer;transition:.2s;"
                onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                + Seed Sample Data
            </button>
            <button onclick="refreshCountries()"
                style="background:linear-gradient(90deg,#3b82f6,#2563eb);color:white;padding:11px 18px;border-radius:10px;border:none;font-size:13px;font-weight:600;cursor:pointer;transition:.2s;"
                onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                🔄 Refresh API
            </button>
        </div>
    </div>

    <!-- SEARCH -->
    <div style="background:linear-gradient(135deg,#141821,#1a1f2e);padding:16px 18px;border-radius:12px;margin-bottom:24px;border:1px solid rgba(255,255,255,.05);">
        <input id="searchInput" type="text" placeholder="Search countries by name or code…"
            style="width:100%;padding:12px 14px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#0d1017;color:#fff;font-size:14px;outline:none;transition:.2s;"
            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,.1)'">
    </div>

    <!-- GRID -->
    <div id="countriesGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin-bottom:40px;">

        @foreach($countries as $country)
        <div class="country-card"
            data-country-name="{{ strtolower($country->country_name) }}"
            data-country-code="{{ strtolower($country->country_code) }}"
            style="background:linear-gradient(135deg,#141821,#1a1f2e);border-radius:16px;padding:20px;color:#fff;border:1px solid rgba(255,255,255,.08);transition:all .3s ease;">

            <!-- COUNTRY HEADER -->
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid rgba(255,255,255,.1);">
                @if($country->flag_url)
                    <img src="{{ $country->flag_url }}" style="width:48px;height:32px;border-radius:6px;object-fit:cover;box-shadow:0 2px 8px rgba(0,0,0,.3);">
                @else
                    <div style="width:48px;height:32px;background:linear-gradient(135deg,#3b82f6,#2563eb);display:flex;align-items:center;justify-content:center;border-radius:6px;font-weight:700;font-size:12px;">
                        {{ strtoupper(substr($country->country_code,0,2)) }}
                    </div>
                @endif
                <div>
                    <h2 style="font-size:16px;font-weight:700;margin:0;">{{ $country->country_name }}</h2>
                    <span style="font-size:12px;color:#9ca3af;">{{ strtoupper($country->country_code) }}</span>
                </div>
            </div>

            <!-- BASE PRICE -->
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:6px;font-weight:600;text-transform:uppercase;">Base Price (₦)</label>
                <form class="update-price-form" data-id="{{ $country->id }}" style="display:flex;gap:8px;">
                    @csrf
                    <input type="number" step="0.01" name="base_price" value="{{ $country->base_price }}"
                        style="flex:1;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#0d1017;color:#fff;font-size:13px;outline:none;transition:.2s;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,.1)'">
                    <button type="submit"
                        style="background:#22c55e;border:none;padding:10px 14px;border-radius:8px;color:white;cursor:pointer;font-weight:600;transition:.2s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">Save</button>
                </form>
            </div>

            <!-- DESCRIPTION -->
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:6px;font-weight:600;text-transform:uppercase;">
                    Description <span style="color:#4b5563;font-weight:400;text-transform:none;">(optional — shown to users)</span>
                </label>
                <textarea
                    class="desc-textarea"
                    data-id="{{ $country->id }}"
                    rows="3"
                    placeholder="e.g. Fast 4G LTE coverage across all major cities. Compatible with iPhone XS and later…"
                    style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#0d1017;color:#fff;font-size:13px;resize:vertical;outline:none;transition:.2s;font-family:inherit;line-height:1.5;"
                    onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='rgba(255,255,255,.1)'"
                >{{ $country->description }}</textarea>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
                    <button
                        class="save-desc-btn"
                        data-id="{{ $country->id }}"
                        style="background:#7c3aed;border:none;padding:7px 14px;border-radius:7px;color:white;cursor:pointer;font-size:12px;font-weight:600;transition:.2s;"
                        onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">
                        Save Description
                    </button>
                    <span class="desc-flash" data-id="{{ $country->id }}" style="font-size:12px;font-weight:600;display:none;"></span>
                </div>
            </div>

            <!-- TOGGLE LOCAL NUMBERS -->
            <button onclick="toggleLocalNumber({{ $country->id }}, this)"
                style="width:100%;padding:10px;border-radius:8px;border:none;font-size:13px;font-weight:600;margin-bottom:12px;cursor:pointer;transition:.2s;color:white;
                background:{{ $country->local_number_available ? '#10b981' : '#6b7280' }};"
                onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                {{ $country->local_number_available ? '✓ Local Numbers Enabled' : '◯ Local Numbers Disabled' }}
            </button>

            <!-- MANAGE PACKAGES -->
            <button onclick="openPackages({{ $country->id }}, '{{ addslashes($country->country_name) }}')"
                style="width:100%;background:linear-gradient(90deg,#3b82f6,#2563eb);padding:11px;border-radius:8px;border:none;color:white;font-weight:600;cursor:pointer;transition:.2s;"
                onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                📦 Manage Packages
            </button>
        </div>
        @endforeach
    </div>

    <!-- EMPTY STATE -->
    <div id="noResults" style="display:none;text-align:center;margin-top:60px;padding:40px;color:#9ca3af;">
        <div style="font-size:48px;margin-bottom:12px;">🔍</div>
        <h3 style="font-size:16px;font-weight:600;margin:0 0 6px 0;">No countries found</h3>
        <p style="font-size:13px;margin:0;">Try adjusting your search criteria</p>
    </div>

</div>
</main>

<!-- PACKAGES MODAL -->
<div id="packagesModal"
    style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.8);z-index:50;padding:20px;">
    <div style="background:linear-gradient(135deg,#121417,#1a1f2e);width:100%;max-width:700px;border-radius:16px;padding:0;color:white;max-height:90vh;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.1);">
        <div style="background:linear-gradient(90deg,#3b82f6,#2563eb);padding:24px;display:flex;justify-content:space-between;align-items:center;">
            <h2 id="modalTitle" style="font-size:20px;font-weight:700;margin:0;"></h2>
            <button onclick="closeModal()" style="background:none;border:none;color:#fff;font-size:28px;cursor:pointer;opacity:.8;"
                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.8'">×</button>
        </div>
        <div style="padding:24px;max-height:calc(90vh - 200px);overflow-y:auto;">
            <div style="margin-bottom:24px;">
                <h3 style="font-size:14px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;font-weight:600;">Current Packages</h3>
                <div id="packagesList" style="display:flex;flex-direction:column;gap:10px;max-height:300px;overflow-y:auto;"></div>
                <div id="noPackages" style="text-align:center;padding:20px;color:#9ca3af;display:none;">
                    <p style="margin:0;font-size:13px;">✨ No packages yet. Add one below!</p>
                </div>
            </div>
            <div style="background:#1a1f2e;padding:20px;border-radius:12px;border:1px solid rgba(255,255,255,.1);">
                <h3 style="font-size:14px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px;font-weight:600;">Add New Package</h3>
                <form id="addPackageForm" style="display:flex;flex-direction:column;gap:12px;">
                    @csrf
                    <input type="hidden" id="packageId" name="package_id">
                    <div>
                        <label style="display:block;font-size:12px;color:#9ca3af;margin-bottom:6px;font-weight:500;">Plan Name</label>
                        <input type="text" name="plan" placeholder="e.g., 5GB Data" required
                            style="width:100%;padding:12px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#121417;color:#fff;font-size:14px;transition:.2s;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,.1)'">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label style="display:block;font-size:12px;color:#9ca3af;margin-bottom:6px;font-weight:500;">Validity (Days)</label>
                            <input type="number" name="validity_days" placeholder="30" required
                                style="width:100%;padding:12px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#121417;color:#fff;font-size:14px;transition:.2s;"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,.1)'">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;color:#9ca3af;margin-bottom:6px;font-weight:500;">Price (₦)</label>
                            <input type="number" name="price" placeholder="5000" step="0.01" required
                                style="width:100%;padding:12px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#121417;color:#fff;font-size:14px;transition:.2s;"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,.1)'">
                        </div>
                    </div>
                    <button type="submit"
                        style="background:linear-gradient(90deg,#16a34a,#15803d);padding:12px;border:none;border-radius:8px;color:white;font-weight:600;cursor:pointer;transition:.2s;"
                        onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                        + Add Package
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ── Search ────────────────────────────────────────────────────
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const val = e.target.value.toLowerCase();
    let count = 0;
    document.querySelectorAll('.country-card').forEach(card => {
        const match = card.dataset.countryName.includes(val) || card.dataset.countryCode.includes(val);
        card.style.display = match ? 'block' : 'none';
        if (match) count++;
    });
    document.getElementById('noResults').style.display = count ? 'none' : 'block';
});

// ── Price update ──────────────────────────────────────────────
document.querySelectorAll('.update-price-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const id         = this.dataset.id;
        const basePrice  = this.querySelector('input[name="base_price"]').value;
        const btn        = this.querySelector('button[type="submit"]');
        const orig       = btn.textContent;
        btn.disabled     = true;
        btn.textContent  = '…';
        try {
            const r = await fetch(`/app/admin/esim/${id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ base_price: basePrice })
            });
            if (r.ok) {
                btn.textContent     = '✓ Saved';
                btn.style.background = '#10b981';
                setTimeout(() => { btn.textContent = orig; btn.style.background = '#22c55e'; btn.disabled = false; }, 2000);
            } else {
                showNotification('Error saving price', 'error');
                btn.textContent = orig; btn.disabled = false;
            }
        } catch(err) {
            showNotification('Error saving price', 'error');
            btn.textContent = orig; btn.disabled = false;
        }
    });
});

// ── Description save ──────────────────────────────────────────
document.querySelectorAll('.save-desc-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const id       = this.dataset.id;
        const textarea = document.querySelector(`.desc-textarea[data-id="${id}"]`);
        const flash    = document.querySelector(`.desc-flash[data-id="${id}"]`);
        const desc     = textarea.value.trim();
        const orig     = this.textContent;

        this.disabled    = true;
        this.textContent = 'Saving…';

        try {
            const r = await fetch(`/app/admin/esim/${id}/description`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ description: desc || null })
            });
            const data = await r.json();
            if (r.ok && data.success) {
                flash.textContent   = '✓ Saved';
                flash.style.color   = '#10b981';
                flash.style.display = 'inline';
            } else {
                flash.textContent   = data.message || 'Failed';
                flash.style.color   = '#ef4444';
                flash.style.display = 'inline';
            }
        } catch(err) {
            flash.textContent   = 'Error';
            flash.style.color   = '#ef4444';
            flash.style.display = 'inline';
        }
        this.disabled    = false;
        this.textContent = orig;
        setTimeout(() => { flash.style.display = 'none'; }, 3000);
    });
});

// ── Modal / packages ──────────────────────────────────────────
let currentCountryId = null;

function openPackages(countryId, countryName) {
    currentCountryId = countryId;
    document.getElementById('modalTitle').textContent = countryName;
    document.getElementById('packagesModal').style.display = 'flex';
    loadPackages(countryId);
}

function closeModal() {
    document.getElementById('packagesModal').style.display = 'none';
    currentCountryId = null;
}

async function loadPackages(countryId) {
    try {
        const r    = await fetch(`/app/admin/esim/${countryId}/packages`);
        const data = await r.json();
        const list = document.getElementById('packagesList');
        const noPkg = document.getElementById('noPackages');
        list.innerHTML = '';

        let packages = Array.isArray(data) ? data : (data.packages ?? []);
        if (!packages.length) { noPkg.style.display = 'block'; return; }
        noPkg.style.display = 'none';

        packages.forEach(pkg => {
            const div = document.createElement('div');
            div.style.cssText = 'background:#1a1d23;padding:14px;border-radius:8px;border:1px solid rgba(255,255,255,.05);';
            div.setAttribute('data-pkg-id', pkg.id);

            div.innerHTML = `
                <div class="display-view">
                    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;">
                        <div>
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                                <strong style="color:#fff;">${pkg.plan}</strong>
                                <span style="background:#2563eb;padding:3px 8px;border-radius:4px;font-size:11px;color:#fff;font-weight:600;">${pkg.validity_days}d</span>
                            </div>
                            <span style="color:#9ca3af;font-size:13px;">Price: <strong style="color:#10b981;">₦${parseFloat(pkg.price).toLocaleString('en-NG',{minimumFractionDigits:2})}</strong></span>
                        </div>
                        <div style="display:flex;gap:6px;">
                            <button onclick="editPackageStart(${pkg.id})" style="background:#3b82f6;border:none;padding:8px 12px;border-radius:6px;color:white;cursor:pointer;font-size:12px;font-weight:600;">✎ Edit</button>
                            <button onclick="deletePackage(${pkg.id})" style="background:#ef4444;border:none;padding:8px 12px;border-radius:6px;color:white;cursor:pointer;font-size:12px;font-weight:600;">🗑 Delete</button>
                        </div>
                    </div>
                </div>
                <div class="edit-view" style="display:none;">
                    <div style="background:#0d1017;padding:12px;border-radius:8px;margin-top:10px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                            <div>
                                <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:4px;">Plan Name</label>
                                <input type="text" class="edit-plan" value="${pkg.plan}"
                                    style="width:100%;padding:8px;border-radius:6px;border:1px solid rgba(255,255,255,.1);background:#121417;color:#fff;font-size:13px;">
                            </div>
                            <div>
                                <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:4px;">Validity (days)</label>
                                <input type="number" class="edit-validity" value="${pkg.validity_days}"
                                    style="width:100%;padding:8px;border-radius:6px;border:1px solid rgba(255,255,255,.1);background:#121417;color:#fff;font-size:13px;">
                            </div>
                        </div>
                        <div style="margin-bottom:12px;">
                            <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:4px;">Price (₦)</label>
                            <input type="number" class="edit-price" value="${pkg.price}" step="0.01"
                                style="width:100%;padding:8px;border-radius:6px;border:1px solid rgba(255,255,255,.1);background:#121417;color:#fff;font-size:13px;">
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button onclick="updatePackage(${pkg.id})" style="flex:1;background:#16a34a;border:none;padding:8px;border-radius:6px;color:white;cursor:pointer;font-size:12px;font-weight:600;">✓ Save</button>
                            <button onclick="cancelEdit(${pkg.id})" style="flex:1;background:#6b7280;border:none;padding:8px;border-radius:6px;color:white;cursor:pointer;font-size:12px;font-weight:600;">✕ Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            list.appendChild(div);
        });
    } catch(err) {
        console.error(err);
        showNotification('Error loading packages', 'error');
    }
}

function editPackageStart(id) {
    const item = document.querySelector(`[data-pkg-id="${id}"]`);
    item.querySelector('.display-view').style.display = 'none';
    item.querySelector('.edit-view').style.display    = 'block';
}

function cancelEdit(id) {
    const item = document.querySelector(`[data-pkg-id="${id}"]`);
    item.querySelector('.edit-view').style.display    = 'none';
    item.querySelector('.display-view').style.display = 'block';
}

async function updatePackage(id) {
    const item = document.querySelector(`[data-pkg-id="${id}"]`);
    const plan = item.querySelector('.edit-plan').value.trim();
    const validity = item.querySelector('.edit-validity').value;
    const price    = item.querySelector('.edit-price').value;
    if (!plan || !validity || !price) { showNotification('Please fill all fields', 'error'); return; }
    try {
        const r = await fetch(`/app/admin/esim/package/${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ plan, validity_days: parseInt(validity), price: parseFloat(price) })
        });
        const data = await r.json();
        if (r.ok) { showNotification('Package updated!', 'success'); loadPackages(currentCountryId); }
        else showNotification(data.message || 'Error updating package', 'error');
    } catch(err) { showNotification('Error updating package', 'error'); }
}

function deletePackage(id) {
    if (!confirm('Delete this package?')) return;
    fetch(`/app/admin/esim/package/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token } })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showNotification('Package deleted!', 'success'); loadPackages(currentCountryId); }
            else showNotification('Error deleting package', 'error');
        })
        .catch(() => showNotification('Error deleting package', 'error'));
}

document.getElementById('addPackageForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const plan     = this.querySelector('input[name="plan"]').value.trim();
    const validity = this.querySelector('input[name="validity_days"]').value;
    const price    = this.querySelector('input[name="price"]').value;
    if (!plan || !validity || !price) { showNotification('Please fill all fields', 'error'); return; }
    try {
        const r = await fetch(`/app/admin/esim/${currentCountryId}/packages`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ plan, validity_days: parseInt(validity), price: parseFloat(price) })
        });
        const data = await r.json();
        if (r.ok && data.success) { showNotification('Package saved!', 'success'); this.reset(); loadPackages(currentCountryId); }
        else showNotification(data.message || 'Error saving package', 'error');
    } catch(err) { showNotification('Error saving package', 'error'); }
});

async function seedCountries() {
    try {
        const r = await fetch('/app/admin/esim/seed-countries', { method: 'POST', headers: { 'X-CSRF-TOKEN': token } });
        const data = await r.json();
        if (r.ok && data.success) { showNotification(data.message || 'Seeded', 'success'); window.location.reload(); }
        else showNotification(data.message || 'Failed', 'error');
    } catch(err) { showNotification('Failed to seed countries', 'error'); }
}

async function refreshCountries() {
    try {
        const r = await fetch('/app/admin/esim/refresh-countries', { method: 'POST', headers: { 'X-CSRF-TOKEN': token } });
        const data = await r.json();
        if (r.ok && data.success) { showNotification(data.message || 'Refreshed', 'success'); window.location.reload(); }
        else showNotification(data.message || 'Failed', 'error');
    } catch(err) { showNotification('Failed to refresh', 'error'); }
}

async function toggleLocalNumber(id, btn) {
    try {
        const r = await fetch(`/app/admin/esim/${id}/toggle-local-number`, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json' }
        });
        const data = await r.json();
        if (r.ok && data.success) {
            btn.textContent        = data.local_number_available ? '✓ Local Numbers Enabled' : '◯ Local Numbers Disabled';
            btn.style.background   = data.local_number_available ? '#10b981' : '#6b7280';
            showNotification(data.message || 'Updated', 'success');
        } else showNotification(data.message || 'Failed', 'error');
    } catch(err) { showNotification('Failed to update', 'error'); }
}

function showNotification(msg, type = 'success') {
    const div = document.createElement('div');
    div.style.cssText = `position:fixed;top:20px;right:20px;padding:12px 16px;border-radius:8px;background:${type === 'success' ? '#16a34a' : '#ef4444'};color:white;z-index:9999;font-size:14px;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.4);`;
    div.textContent = msg;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}

document.getElementById('packagesModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endsection