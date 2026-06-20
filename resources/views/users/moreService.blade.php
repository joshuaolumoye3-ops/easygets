@extends('layouts.app')

@section('title', 'More Services')

@section('content')
<div class="max-w-xl mx-auto py-8 font-[Inter] px-5">
    <div class="flex items-center space-x-2 mb-6">
        <a href="dashboard">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="text-lg font-semibold text-center flex-1">More Services</h1>
    </div>

    <div class="mb-6">
        <input type="text" placeholder="Search for a service..." class="w-full border rounded-full py-2 px-4 text-sm focus:outline-none focus:ring-2 focus:ring-green-800">
    </div>

    <h2 class="text-sm font-medium text-gray-500 mb-4">All Services</h2>

    <div class="space-y-4">
        @foreach ([
            ['icon'=>'assets/wifi.svg','name'=>'Data','btn'=>'Buy Data', 'href'=>'app/data'],
            ['icon'=>'assets/phone.svg','name'=>'Airtime','btn'=>'Recharge', 'href'=>'app/airtime'],
            ['icon'=>'assets/electricity.svg','name'=>'Electricity','btn'=>'Pay Bill', 'href'=>'app/electricity'],
            ['icon'=>'assets/tv.svg','name'=>'TV/Cable','btn'=>'Subscribe', 'href'=>'app/cabletv'],
            ['icon'=>'🏦','name'=>'Banking','btn'=>'Deposit', 'href'=>'app/dashboard'],
            ['icon'=>'assets/e-sim.svg','name'=>'eSIM','btn'=>'Activate', 'href'=>'app/esim'],
            ['icon'=>'assets/sim-icon.svg','name'=>'eSIM Renewal','btn'=>'Renew', 'href'=>'app/esim'],
            ['icon'=>'assets/ball.svg','name'=>'Bettinng','btn'=>'Betting', 'href'=>'app/betting'],
            ['icon'=>'📝','name'=>'JAMB e-PIN','btn'=>'Buy', 'href'=>'app/jambepin'],
            // ['icon'=>'📖','name'=>'WAEC e-PIN','btn'=>'Buy', 'href'=>'waecepin'],
        ] as $service)
        <div class="flex items-center justify-between border-b pb-3">
            <div class="flex items-center space-x-4">
                <div class="text-green-800 text-xl"><img src="{!! $service['icon'] !!}" alt="{{ $service['name'] }}" class="w-8"></div>
                <p class="text-base font-medium">{{ $service['name'] }}</p>
            </div>
            <a href="{{url($service['href'])}}" class="bg-green-800 text-white text-xs px-4 py-2 rounded-full">{{ $service['btn'] }}</a>
        </div>
        @endforeach
    </div>
</div>
@endsection
