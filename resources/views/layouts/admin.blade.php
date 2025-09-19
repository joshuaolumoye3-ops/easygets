<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Easygets Admin Dashboard')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/owl-carousel-2/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/owl-carousel-2/owl.theme.default.min.css') }}">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('assets/styles/modern-vertical.css') }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />
    <style>
        .bg-green { background: #03624C; }
        .bg-red { background: red; }
        .text-white { color: white; }

        /* alert positioning */
        .alert-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 250px;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Sidebar -->
    @include('partials.admin.header')

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert-message mb-4 p-4 rounded-md bg-green text-white" id="flash-message">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert-message mb-4 p-4 rounded-md bg-red text-white" id="flash-message">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-message mb-4 p-4 rounded-md bg-red text-white" id="flash-message">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Content -->
    <div class="flex-1 min-h-screen flex flex-col bg-black">
        <main class="flex-1 p-4">
            @yield('content')
        </main>
    </div>

    <!-- JS -->
    <script src="{{ asset('assets/vendors/js/vendor.bundle.base.js') }}"></script>
    <script src="{{ asset('assets/vendors/chart.js/chart.umd.js') }}"></script>
    <script src="{{ asset('assets/vendors/owl-carousel-2/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
    <script src="{{ asset('assets/js/todolist.js') }}"></script>
    <script src="{{ asset('assets/js/sidebar.js') }}"></script>

    <script>
        // Auto-hide flash messages after 5 seconds
        document.addEventListener("DOMContentLoaded", function () {
            const flash = document.getElementById("flash-message");
            if (flash) {
                setTimeout(() => {
                    flash.style.animation = "fadeOut 0.5s forwards";
                    setTimeout(() => flash.remove(), 500); // remove after fadeout
                }, 5000);
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
