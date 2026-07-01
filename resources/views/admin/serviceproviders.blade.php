@extends('layouts.admin') 
@section('title', 'Manage Providers')

@section('content')
<main style="padding-top:70px; width:100%; display:flex; flex-direction:column; background:#0f1115; min-height:100vh;">

    <div style="padding:30px 28px; width:100%; flex-grow:1;">

        <h1 style="
            font-size:24px; 
            font-weight:700; 
            color:#ffffff; 
            margin-bottom:25px;
            letter-spacing:0.5px;
        ">
            Providers for {{ $service->name }}
        </h1>

        <div style="
            display:grid; 
            grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); 
            gap:20px;
        ">
            @foreach($providers as $provider)
                <div 
                    style="
                        background:#1a1d23;
                        color:#ffffff;
                        padding:22px;
                        border-radius:14px;
                        box-shadow:0 8px 20px rgba(0,0,0,0.25);
                        border:2px solid {{ $provider->status === 'active' ? '#10b981' : '#ef4444' }};
                        transition:all 0.25s ease;
                    "
                    onmouseover="this.style.boxShadow='0 12px 28px rgba(0,0,0,0.35)'"
                    onmouseout="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.25)'"
                >

                    <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:15px;">
                        <h2 style="
                            font-size:20px;
                            font-weight:600;
                            color:#f1f1f1;
                            margin:0;
                        ">
                            {{ $provider->name }}
                        </h2>
                        <span style="
                            font-size:12px;
                            padding:6px 12px;
                            border-radius:8px;
                            font-weight:600;
                            background:{{ $provider->status === 'active' ? '#065f46' : '#7f1d1d' }};
                            color:{{ $provider->status === 'active' ? '#86efac' : '#fca5a5' }};
                        ">
                            {{ ucfirst($provider->status) }}
                        </span>
                    </div>

                    <p style="
                        font-size:14px;
                        color:#9ca3af;
                        line-height:1.5;
                        margin-bottom:15px;
                    ">
                        Click to manage {{ $provider->name }} plans
                    </p>

                    <div style="display:flex; gap:10px;">
                        <button 
                            onclick="window.location='{{ route('admin.products', $provider->id) }}'"
                            style="
                                flex:1;
                                background:#10b981;
                                color:white;
                                border:none;
                                padding:10px 16px;
                                border-radius:8px;
                                font-weight:600;
                                cursor:pointer;
                                transition:all 0.2s ease;
                                font-size:14px;
                            "
                            onmouseover="this.style.background='#059669'"
                            onmouseout="this.style.background='#10b981'"
                        >
                            Manage Plans
                        </button>

                        <form action="{{ route('admin.providers.toggle', $provider->id) }}" method="POST" style="flex:1;">
                            @csrf
                            <button 
                                type="submit"
                                style="
                                    width:100%;
                                    background:{{ $provider->status === 'active' ? '#ef4444' : '#10b981' }};
                                    color:white;
                                    border:none;
                                    padding:10px 16px;
                                    border-radius:8px;
                                    font-weight:600;
                                    cursor:pointer;
                                    transition:all 0.2s ease;
                                    font-size:14px;
                                "
                                onmouseover="this.style.opacity='0.9'"
                                onmouseout="this.style.opacity='1'"
                            >
                                {{ $provider->status === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>

                </div>
            @endforeach
        </div>

    </div>

</main>
@endsection