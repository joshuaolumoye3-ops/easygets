<!DOCTYPE html>
<html lang="en">


<!-- Mirrored from demo.bootstrapdash.com/corona-tailwind/themes/modern-vertical/pages/user-pages/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 15 Sep 2025 11:58:53 GMT -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corona Tailwind</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="../../../assets/vendors/mdi/css/materialdesignicons.min.css">
    <!-- endinject -->
    <link rel="stylesheet" href="../../../assets/styles/modern-vertical.css">
    <link rel="shortcut icon" href="../../../assets/images/favicon.png" />
    <style>
        .modern-primary{
            background: #03624C !important;
        }
        .text-red{
            color: red;
        }
        .bg-red{
            background:red
        }
    </style>
</head>

<body class="bg-auth-login bg-cover bg-center bg-no-repeat flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm p-4 bg-navbar_bg border border-gray-200 rounded-lg shadow sm:p-6 md:p-8">
        <form class="space-y-6" action="{{ route('admin.login') }}" method="POST">
            @csrf
            <h5 class="text-xl font-medium text-light">Sign in</h5>

            {{-- Show general error (like invalid credentials) --}}
            @if ($errors->has('email'))
                <div class="p-2 mb-2 text-sm text-white bg-red rounded-md">
                    {{ $errors->first('email') }}
                </div>
            @endif

            <div>
                <label for="email" class="block mb-2 text-sm font-medium text-light">
                    Username or Email
                </label>
                <input type="text" name="email" value="{{ old('email') }}"
                    class="bg-input_bg border border-border_color text-white text-sm rounded-md focus:outline-none focus:ring-0 focus:ring-modern-success focus:border-modern-success/80 w-full p-2.5 @error('email') border-red-500 @enderror"
                    placeholder="Username or Email" required />

                @error('email')
                    <span class="text-red text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password" class="block mb-2 text-sm font-medium text-light">
                    Password
                </label>
                <input type="password" name="password"
                    class="bg-input_bg border border-border_color text-white text-sm rounded-md focus:outline-none focus:ring-0 focus:ring-modern-success focus:border-modern-success/80 w-full p-2.5 @error('password') border-red-500 @enderror"
                    placeholder="Password" required />

                @error('password')
                    <span class="text-red text-xs">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit"
                class="w-full text-white bg-modern-primary hover:bg-modern-primary/80 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                Login
            </button>
        </form>
    </div>

</body>



<!-- Mirrored from demo.bootstrapdash.com/corona-tailwind/themes/modern-vertical/pages/user-pages/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 15 Sep 2025 11:58:53 GMT -->
</html>