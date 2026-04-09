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
            grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); 
            gap:20px;
        ">
            @foreach($providers as $provider)
                <div 
                    onclick="window.location='{{ route('admin.products', $provider->id) }}'"
                    style="
                        background:#1a1d23;
                        color:#ffffff;
                        padding:22px;
                        border-radius:14px;
                        box-shadow:0 8px 20px rgba(0,0,0,0.25);
                        cursor:pointer;
                        transition:all 0.25s ease;
                        border:1px solid rgba(255,255,255,0.05);
                    "
                    onmouseover="this.style.transform='translateY(-6px)'; this.style.background='#222630'; this.style.boxShadow='0 12px 28px rgba(0,0,0,0.35)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.background='#1a1d23'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.25)'"
                >

                    <h2 style="
                        font-size:20px;
                        font-weight:600;
                        margin-bottom:10px;
                        color:#f1f1f1;
                    ">
                        {{ $provider->name }}
                    </h2>

                    <p style="
                        font-size:14px;
                        color:#9ca3af;
                        line-height:1.5;
                    ">
                        Click to manage {{ $provider->name }} plans
                    </p>

                </div>
            @endforeach
        </div>

    </div>

</main>
@endsection