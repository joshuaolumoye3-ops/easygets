@extends('layouts.admin')
@section('title', 'Manage eSIM Countries')

@section('content')
<main style="padding-top:70px; width:100%; display:flex; flex-direction:column; min-height:100vh; background:#0b0d12;">

    <div style="padding:32px 28px; width:100%; max-width:1400px; margin:0 auto; flex-grow:1;">

        <!-- HEADER -->
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:15px; margin-bottom:28px;">
            <div>
                <h1 style="font-size:26px; font-weight:700; color:#fff;">eSIM Countries</h1>
                <p style="color:#9ca3af; font-size:14px;">Manage countries and pricing packages</p>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button onclick="seedCountries()"
                    style="background:#16a34a; color:white; padding:10px 16px; border-radius:8px; border:none; font-size:13px; font-weight:600; cursor:pointer;"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    + Seed Samples
                </button>

                <button onclick="refreshCountries()"
                    style="background:#2563eb; color:white; padding:10px 16px; border-radius:8px; border:none; font-size:13px; font-weight:600; cursor:pointer;"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    Refresh API
                </button>
            </div>
        </div>

        <!-- SEARCH -->
        <div style="background:#141821; padding:16px; border-radius:12px; margin-bottom:20px;">
            <input id="searchInput" type="text" placeholder="Search countries..."
                style="width:100%; padding:12px 14px; border-radius:8px; border:none; outline:none;">
        </div>

        <!-- GRID -->
        <div id="countriesGrid"
            style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px,1fr)); gap:18px;">

            @foreach($countries as $country)
            <div class="country-card"
                data-country-name="{{ strtolower($country->country_name) }}"
                data-country-code="{{ strtolower($country->country_code) }}"
                style="background:#141821; border-radius:14px; padding:18px; color:#fff; border:1px solid rgba(255,255,255,0.05); transition:0.25s;"
                onmouseover="this.style.transform='translateY(-5px)'; this.style.background='#1b2130'"
                onmouseout="this.style.transform='translateY(0)'; this.style.background='#141821'">

                <!-- COUNTRY -->
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                    @if($country->flag_url)
                        <img src="{{ $country->flag_url }}" style="width:48px; height:32px; border-radius:6px; object-fit:cover;">
                    @else
                        <div style="width:48px; height:32px; background:#333; display:flex; align-items:center; justify-content:center; border-radius:6px;">
                            {{ strtoupper(substr($country->country_code,0,2)) }}
                        </div>
                    @endif

                    <div>
                        <h2 style="font-size:15px; font-weight:600;">{{ $country->country_name }}</h2>
                        <span style="font-size:11px; color:#9ca3af;">{{ $country->country_code }}</span>
                    </div>
                </div>

                <!-- PRICE -->
                <form class="update-price-form" data-id="{{ $country->id }}"
                    style="display:flex; gap:8px; margin-bottom:10px;">
                    @csrf

                    <input type="number" step="0.01" name="base_price"
                        value="{{ $country->base_price }}"
                        style="flex:1; padding:8px; border-radius:6px; border:none; font-size:13px;">

                    <button type="submit"
                        style="background:#22c55e; border:none; padding:8px 10px; border-radius:6px; color:white; cursor:pointer;">
                        ✓
                    </button>
                </form>

                <!-- TOGGLE -->
                <button onclick="toggleLocalNumber({{ $country->id }}, this)"
                    style="width:100%; padding:8px; border-radius:6px; border:none; font-size:12px; margin-bottom:12px;
                    background:{{ $country->local_number_available ? '#16a34a' : '#374151' }};
                    color:white;">
                    {{ $country->local_number_available ? 'Local numbers enabled' : 'Local numbers disabled' }}
                </button>

                <!-- ACTION -->
                <button onclick="openPackages({{ $country->id }}, '{{ addslashes($country->country_name) }}')"
                    style="width:100%; background:#3b82f6; padding:10px; border-radius:8px; border:none; color:white; font-weight:600; cursor:pointer;"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    Manage Packages
                </button>

            </div>
            @endforeach
        </div>

        <!-- EMPTY -->
        <div id="noResults" style="display:none; text-align:center; margin-top:40px; color:#9ca3af;">
            No countries found
        </div>

    </div>
</main>

<!-- MODAL -->
<div id="packagesModal"
    style="position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.75); z-index:50;">

    <div style="background:#121417; width:100%; max-width:600px; border-radius:12px; padding:20px; color:white; max-height:90vh; overflow:auto;">

        <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
            <h2 id="modalTitle" style="font-size:18px; font-weight:600;"></h2>
            <button onclick="closeModal()" style="background:none; border:none; color:#aaa; font-size:18px;">×</button>
        </div>

        <div id="packagesList" style="margin-bottom:20px;"></div>

        <form id="addPackageForm" style="display:flex; flex-direction:column; gap:10px;">
            @csrf
            <input type="hidden" id="packageId" name="package_id">

            <input type="text" name="plan" placeholder="Plan (e.g. 5GB)" required
                style="padding:10px; border-radius:6px; border:none;">

            <input type="number" name="validity_days" placeholder="Days" required
                style="padding:10px; border-radius:6px; border:none;">

            <input type="number" name="price" placeholder="Price" required
                style="padding:10px; border-radius:6px; border:none;">

            <button type="submit"
                style="background:#16a34a; padding:10px; border:none; border-radius:6px; color:white;">
                Save Package
            </button>
        </form>

    </div>
</div>

<script>
// SEARCH
document.getElementById('searchInput').addEventListener('input', function(e){
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
</script>
@endsection