@extends('layouts.admin')
@section('title', 'Manage eSIM Countries')

@section('content')
<main style="padding-top:70px; width:100%; display:flex; flex-direction:column; min-height:100vh; background:#0b0d12;">

    <div style="padding:32px 28px; width:100%; max-width:1400px; margin:0 auto; flex-grow:1;">

        <!-- HEADER -->
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:20px; margin-bottom:32px;">
            <div>
                <h1 style="font-size:28px; font-weight:700; color:#fff; margin:0;">eSIM Countries</h1>
                <p style="color:#9ca3af; font-size:14px; margin:6px 0 0 0;">Manage countries, pricing and packages</p>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <button onclick="seedCountries()"
                    style="background:linear-gradient(90deg, #16a34a 0%, #15803d 100%); color:white; padding:11px 18px; border-radius:10px; border:none; font-size:13px; font-weight:600; cursor:pointer; transition:0.2s;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    + Seed Sample Data
                </button>

                <button onclick="refreshCountries()"
                    style="background:linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); color:white; padding:11px 18px; border-radius:10px; border:none; font-size:13px; font-weight:600; cursor:pointer; transition:0.2s;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    🔄 Refresh API
                </button>
            </div>
        </div>

        <!-- SEARCH -->
        <div style="background:linear-gradient(135deg, #141821 0%, #1a1f2e 100%); padding:16px 18px; border-radius:12px; margin-bottom:24px; border:1px solid rgba(255,255,255,0.05);">
            <input id="searchInput" type="text" placeholder="Search countries by name or code..."
                style="width:100%; padding:12px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#0d1017; color:#fff; font-size:14px; outline:none; transition:0.2s;"
                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
        </div>

        <!-- GRID -->
        <div id="countriesGrid"
            style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:20px; margin-bottom:40px;">

            @foreach($countries as $country)
            <div class="country-card"
                data-country-name="{{ strtolower($country->country_name) }}"
                data-country-code="{{ strtolower($country->country_code) }}"
                style="background:linear-gradient(135deg, #141821 0%, #1a1f2e 100%); border-radius:16px; padding:20px; color:#fff; border:1px solid rgba(255,255,255,0.08); transition:all 0.3s ease; cursor:pointer;"
                onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 32px rgba(59, 130, 246, 0.15)'; this.style.borderColor='rgba(59, 130, 246, 0.3)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'; this.style.borderColor='rgba(255,255,255,0.08)'">

                <!-- COUNTRY HEADER -->
                <div style="display:flex; align-items:center; gap:14px; margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid rgba(255,255,255,0.1);">
                    @if($country->flag_url)
                        <img src="{{ $country->flag_url }}" style="width:48px; height:32px; border-radius:6px; object-fit:cover; box-shadow:0 2px 8px rgba(0,0,0,0.3);">
                    @else
                        <div style="width:48px; height:32px; background:linear-gradient(135deg, #3b82f6, #2563eb); display:flex; align-items:center; justify-content:center; border-radius:6px; font-weight:700; font-size:12px;">
                            {{ strtoupper(substr($country->country_code,0,2)) }}
                        </div>
                    @endif

                    <div>
                        <h2 style="font-size:16px; font-weight:700; margin:0;">{{ $country->country_name }}</h2>
                        <span style="font-size:12px; color:#9ca3af;">Code: {{ strtoupper($country->country_code) }}</span>
                    </div>
                </div>

                <!-- PRICE -->
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-size:11px; color:#9ca3af; margin-bottom:6px; font-weight:600; text-transform:uppercase;">Base Price (₦)</label>
                    <form class="update-price-form" data-id="{{ $country->id }}"
                        style="display:flex; gap:8px;">
                        @csrf

                        <input type="number" step="0.01" name="base_price"
                            value="{{ $country->base_price }}"
                            style="flex:1; padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#0d1017; color:#fff; font-size:13px; outline:none; transition:0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">

                        <button type="submit" style="background:#22c55e; border:none; padding:10px 14px; border-radius:8px; color:white; cursor:pointer; font-weight:600; transition:0.2s;"
                            onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            Save
                        </button>
                    </form>
                </div>

                <!-- TOGGLE LOCAL NUMBERS -->
                <button onclick="toggleLocalNumber({{ $country->id }}, this)" style="width:100%; padding:10px; border-radius:8px; border:none; font-size:13px; font-weight:600; margin-bottom:12px; cursor:pointer; transition:0.2s;
                    background:{{ $country->local_number_available ? '#10b981' : '#6b7280' }};
                    color:white;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    {{ $country->local_number_available ? '✓ Local Numbers Enabled' : '◯ Local Numbers Disabled' }}
                </button>

                <!-- MANAGE PACKAGES -->
                <button onclick="openPackages({{ $country->id }}, '{{ addslashes($country->country_name) }}')" style="width:100%; background:linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); padding:11px; border-radius:8px; border:none; color:white; font-weight:600; cursor:pointer; transition:0.2s;"
                    onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    📦 Manage Packages
                </button>

            </div>
            @endforeach
        </div>

        <!-- EMPTY STATE -->
        <div id="noResults" style="display:none; text-align:center; margin-top:60px; padding:40px; color:#9ca3af;">
            <div style="font-size:48px; margin-bottom:12px;">🔍</div>
            <h3 style="font-size:16px; font-weight:600; margin:0 0 6px 0;">No countries found</h3>
            <p style="font-size:13px; margin:0;">Try adjusting your search criteria</p>
        </div>

    </div>
</main>

<!-- MODAL -->
<div id="packagesModal"
    style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.8); z-index:50; padding:20px;">

    <div style="background:linear-gradient(135deg, #121417 0%, #1a1f2e 100%); width:100%; max-width:700px; border-radius:16px; padding:0; color:white; max-height:90vh; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.1);">

        <!-- HEADER -->
        <div style="background:linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); padding:24px; display:flex; justify-content:space-between; align-items:center;">
            <h2 id="modalTitle" style="font-size:20px; font-weight:700; margin:0;"></h2>
            <button onclick="closeModal()" style="background:none; border:none; color:#fff; font-size:28px; cursor:pointer; opacity:0.8; transition:0.2s;"
                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">×</button>
        </div>

        <!-- CONTENT -->
        <div style="padding:24px; max-height:calc(90vh - 200px); overflow-y:auto;">

            <!-- PACKAGES LIST -->
            <div style="margin-bottom:24px;">
                <h3 style="font-size:14px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px; font-weight:600;">Current Packages</h3>
                <div id="packagesList" style="display:flex; flex-direction:column; gap:10px; max-height:300px; overflow-y:auto;"></div>
                <div id="noPackages" style="text-align:center; padding:20px; color:#9ca3af; display:none;">
                    <p style="margin:0; font-size:13px;">✨ No packages yet. Add one below!</p>
                </div>
            </div>

            <!-- ADD PACKAGE FORM -->
            <div style="background:#1a1f2e; padding:20px; border-radius:12px; border:1px solid rgba(255,255,255,0.1);">
                <h3 style="font-size:14px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:16px; font-weight:600;">Add New Package</h3>

                <form id="addPackageForm" style="display:flex; flex-direction:column; gap:12px;">
                    @csrf
                    <input type="hidden" id="packageId" name="package_id">

                    <div>
                        <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px; font-weight:500;">Plan Name</label>
                        <input type="text" name="plan" placeholder="e.g., 5GB Data" required
                            style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#121417; color:#fff; font-size:14px; transition:0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div>
                            <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px; font-weight:500;">Validity (Days)</label>
                            <input type="number" name="validity_days" placeholder="30" required
                                style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#121417; color:#fff; font-size:14px; transition:0.2s;"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                        </div>

                        <div>
                            <label style="display:block; font-size:12px; color:#9ca3af; margin-bottom:6px; font-weight:500;">Price (₦)</label>
                            <input type="number" name="price" placeholder="5000" step="0.01" required
                                style="width:100%; padding:12px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:#121417; color:#fff; font-size:14px; transition:0.2s;"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                        </div>
                    </div>

                    <button type="submit" style="background:linear-gradient(90deg, #16a34a 0%, #15803d 100%); padding:12px; border:none; border-radius:8px; color:white; font-weight:600; cursor:pointer; transition:0.2s;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        + Add Package
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// CSRF Token
const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ==================== SEARCH ====================
document.getElementById('searchInput')?.addEventListener('input', function(e){
    const val = e.target.value.toLowerCase();
    let count = 0;

    document.querySelectorAll('.country-card').forEach(card=>{
        const name = card.dataset.countryName;
        const code = card.dataset.countryCode;

        if(name.includes(val) || code.includes(val)){
            card.style.display='block';
            count++;
        } else {
            card.style.display='none';
        }
    });

    document.getElementById('noResults').style.display = count ? 'none':'block';
});

// ==================== PRICE UPDATE (AJAX) ====================
document.querySelectorAll('.update-price-form').forEach(form => {
    form.addEventListener('submit', async function(e){
        e.preventDefault();

        const id = this.dataset.id;
        const basePrice = this.querySelector('input[name="base_price"]').value;
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.textContent;

        button.disabled = true;
        button.textContent = '...';

        try {
            const response = await fetch(`/app/admin/esim/${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ base_price: basePrice })
            });

            if(response.ok){
                button.textContent = '✓ Saved';
                button.style.background = '#10b981';
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '#22c55e';
                    button.disabled = false;
                }, 2000);
            } else {
                showNotification('Error saving price', 'error');
                button.textContent = originalText;
                button.disabled = false;
            }
        } catch(err){
            console.error(err);
            showNotification('Error saving price', 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    });
});

// ==================== MODAL FUNCTIONS ====================
let currentCountryId = null;

function openPackages(countryId, countryName){
    currentCountryId = countryId;
    document.getElementById('modalTitle').textContent = countryName;
    document.getElementById('packagesModal').style.display = 'flex';
    
    // Fetch packages
    loadPackages(countryId);
}

function closeModal(){
    document.getElementById('packagesModal').style.display = 'none';
    currentCountryId = null;
}

async function loadPackages(countryId){
    try {
        const response = await fetch(`/app/admin/esim/${countryId}/packages`);
        const data = await response.json();
        const list = document.getElementById('packagesList');
        const noPackages = document.getElementById('noPackages');
        list.innerHTML = '';

        let packages = [];
        if (Array.isArray(data)) {
            packages = data;
        } else if (data && data.packages) {
            packages = data.packages;
        }

        if (!packages.length) {
            noPackages.style.display = 'block';
            return;
        }

        noPackages.style.display = 'none';

        packages.forEach(pkg => {
            const div = document.createElement('div');
            div.style.cssText = 'background:#1a1d23; padding:14px; border-radius:8px; margin-bottom:10px; border:1px solid rgba(255,255,255,0.05); transition:0.2s;';
            div.className = 'package-item';
            div.setAttribute('data-pkg-id', pkg.id);
            
            const displayView = document.createElement('div');
            displayView.className = 'display-view';
            displayView.innerHTML = `
                <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px;">
                    <div style="flex:1; min-width:220px;">
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:6px;">
                            <strong style="font-size:15px; color:#fff;">${pkg.plan}</strong>
                            <span style="background:#2563eb; padding:3px 8px; border-radius:4px; font-size:11px; color:#fff; font-weight:600;">${pkg.validity_days}d</span>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:16px; font-size:13px;">
                            <span style="color:#9ca3af;">Price: <strong style="color:#10b981; font-weight:600;">₦${parseFloat(pkg.price).toLocaleString('en-NG', {minimumFractionDigits:2, maximumFractionDigits:2})}</strong></span>
                            <span style="color:#9ca3af;">ID: <strong style="color:#8b5cf6;">#${pkg.id}</strong></span>
                        </div>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        <button onclick="editPackageStart(${pkg.id})" style="background:#3b82f6; border:none; padding:8px 12px; border-radius:6px; color:white; cursor:pointer; font-size:12px; font-weight:600; transition:0.2s;"
                            onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            ✎ Edit
                        </button>
                        <button onclick="deletePackage(${pkg.id})" style="background:#ef4444; border:none; padding:8px 12px; border-radius:6px; color:white; cursor:pointer; font-size:12px; font-weight:600; transition:0.2s;"
                            onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            🗑 Delete
                        </button>
                    </div>
                </div>
            `;
            
            const editView = document.createElement('div');
            editView.className = 'edit-view';
            editView.style.display = 'none';
            editView.innerHTML = `
                <div style="background:#0d1017; padding:12px; border-radius:8px; margin-top:10px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <div>
                            <label style="display:block; font-size:11px; color:#9ca3af; margin-bottom:4px; font-weight:500;">Plan Name</label>
                            <input type="text" class="edit-plan" value="${pkg.plan}" placeholder="Plan name"
                                style="width:100%; padding:8px; border-radius:6px; border:1px solid rgba(255,255,255,0.1); background:#121417; color:#fff; font-size:13px; transition:0.2s;"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                        </div>
                        <div>
                            <label style="display:block; font-size:11px; color:#9ca3af; margin-bottom:4px; font-weight:500;">Validity (days)</label>
                            <input type="number" class="edit-validity" value="${pkg.validity_days}" placeholder="30"
                                style="width:100%; padding:8px; border-radius:6px; border:1px solid rgba(255,255,255,0.1); background:#121417; color:#fff; font-size:13px; transition:0.2s;"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                        </div>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label style="display:block; font-size:11px; color:#9ca3af; margin-bottom:4px; font-weight:500;">Price (₦)</label>
                        <input type="number" class="edit-price" value="${pkg.price}" step="0.01" placeholder="5000"
                            style="width:100%; padding:8px; border-radius:6px; border:1px solid rgba(255,255,255,0.1); background:#121417; color:#fff; font-size:13px; transition:0.2s;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <button onclick="updatePackage(${pkg.id})" style="flex:1; min-width:120px; background:#16a34a; border:none; padding:8px; border-radius:6px; color:white; cursor:pointer; font-size:12px; font-weight:600; transition:0.2s;"
                            onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            ✓ Save
                        </button>
                        <button onclick="cancelEdit(${pkg.id})" style="flex:1; min-width:120px; background:#6b7280; border:none; padding:8px; border-radius:6px; color:white; cursor:pointer; font-size:12px; font-weight:600; transition:0.2s;"
                            onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                            ✕ Cancel
                        </button>
                    </div>
                </div>
            `;
            
            div.appendChild(displayView);
            div.appendChild(editView);
            list.appendChild(div);
        });
    } catch (err) {
        console.error(err);
        showNotification('Error loading packages', 'error');
    }
}

function deletePackage(packageId){
    if(!confirm('Delete this package?')) return;

    fetch(`/app/admin/esim/package/${packageId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': token }
    })
    .then(r => r.json())
    .then(data => {
        if(data.success){
            showNotification('Package deleted!', 'success');
            loadPackages(currentCountryId);
        } else {
            showNotification('Error deleting package', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Error deleting package', 'error');
    });
}

function editPackageStart(packageId, plan, validityDays, price){
    const item = document.querySelector(`[data-pkg-id="${packageId}"]`);
    const displayView = item.querySelector('.display-view');
    const editView = item.querySelector('.edit-view');
    
    displayView.style.display = 'none';
    editView.style.display = 'block';
}

function cancelEdit(packageId){
    const item = document.querySelector(`[data-pkg-id="${packageId}"]`);
    const displayView = item.querySelector('.display-view');
    const editView = item.querySelector('.edit-view');
    
    editView.style.display = 'none';
    displayView.style.display = 'block';
}

async function updatePackage(packageId){
    const item = document.querySelector(`[data-pkg-id="${packageId}"]`);
    const plan = item.querySelector('.edit-plan').value.trim();
    const validityDays = item.querySelector('.edit-validity').value;
    const price = item.querySelector('.edit-price').value;

    if(!plan || !validityDays || !price){
        showNotification('Please fill all fields', 'error');
        return;
    }

    try {
        const response = await fetch(`/app/admin/esim/package/${packageId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                plan: plan,
                validity_days: parseInt(validityDays),
                price: parseFloat(price)
            })
        });

        const data = await response.json();

        if(response.ok){
            showNotification('Package updated!', 'success');
            loadPackages(currentCountryId);
        } else {
            showNotification(data.message || 'Error updating package', 'error');
        }
    } catch(err){
        console.error(err);
        showNotification('Error updating package', 'error');
    }
}

// ==================== ADD PACKAGE FORM ====================
document.getElementById('addPackageForm')?.addEventListener('submit', async function(e){
    e.preventDefault();

    const plan = this.querySelector('input[name="plan"]').value.trim();
    const validityDays = this.querySelector('input[name="validity_days"]').value;
    const price = this.querySelector('input[name="price"]').value;

    if(!plan || !validityDays || !price){
        showNotification('Please fill all fields', 'error');
        return;
    }

    try {
        const response = await fetch(`/app/admin/esim/${currentCountryId}/packages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                plan: plan,
                validity_days: parseInt(validityDays),
                price: parseFloat(price)
            })
        });

        const data = await response.json();

        if(response.ok && data.success){
            showNotification('Package saved!', 'success');
            this.reset();
            loadPackages(currentCountryId);
        } else {
            showNotification(data.message || 'Error saving package', 'error');
        }
    } catch(err){
        console.error(err);
        showNotification('Error saving package', 'error');
    }
});

// ==================== STUB FUNCTIONS ====================
async function seedCountries(){
    try {
        const response = await fetch('/app/admin/esim/seed-countries', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token }
        });
        const data = await response.json();
        if(response.ok && data.success){
            showNotification(data.message || 'Sample countries seeded', 'success');
            window.location.reload();
        } else {
            showNotification(data.message || 'Failed to seed countries', 'error');
        }
    } catch(err){
        console.error(err);
        showNotification('Failed to seed countries', 'error');
    }
}

async function refreshCountries(){
    try {
        const response = await fetch('/app/admin/esim/refresh-countries', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token }
        });
        const data = await response.json();
        if(response.ok && data.success){
            showNotification(data.message || 'Countries refreshed', 'success');
            window.location.reload();
        } else {
            showNotification(data.message || 'Failed to refresh countries', 'error');
        }
    } catch(err){
        console.error(err);
        showNotification('Failed to refresh countries', 'error');
    }
}

async function toggleLocalNumber(id, btn){
    try {
        const response = await fetch(`/app/admin/esim/${id}/toggle-local-number`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if(response.ok && data.success){
            btn.textContent = data.local_number_available ? '✓ Local Numbers Enabled' : '◯ Local Numbers Disabled';
            btn.style.background = data.local_number_available ? '#10b981' : '#6b7280';
            showNotification(data.message || 'Local number setting updated', 'success');
        } else {
            showNotification(data.message || 'Failed to update local number', 'error');
        }
    } catch(err){
        console.error(err);
        showNotification('Failed to update local number', 'error');
    }
}

// ==================== NOTIFICATION ====================
function showNotification(msg, type = 'success'){
    const div = document.createElement('div');
    div.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 16px;
        border-radius: 8px;
        background: ${type === 'success' ? '#16a34a' : '#ef4444'};
        color: white;
        z-index: 9999;
        font-size: 14px;
    `;
    div.textContent = msg;
    document.body.appendChild(div);

    setTimeout(() => div.remove(), 3000);
}

// Close modal on outside click
document.getElementById('packagesModal')?.addEventListener('click', function(e){
    if(e.target === this){
        closeModal();
    }
});
</script>
@endsection