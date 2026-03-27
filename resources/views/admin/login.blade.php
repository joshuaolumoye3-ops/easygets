<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easygets Admin Login</title>
    <link rel="shortcut icon" href="../../../assets/images/favicon.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #03624C 0%, #0a4a38 50%, #157A61 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(230, 245, 241, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(21, 122, 97, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 45px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(3, 98, 76, 0.25), 0 0 0 1px rgba(3, 98, 76, 0.05);
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            z-index: 10;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #03624C 0%, #157A61 100%);
            border-radius: 16px 16px 0 0;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            font-size: 32px;
            color: #03624C;
            margin-bottom: 10px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-size: 14px;
            color: #999;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: 600;
            color: #03624C;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 16px;
            border: 2px solid #E6F5F1;
            border-radius: 10px;
            font-size: 14px;
            color: #333;
            background: #F9FAFB;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
        }

        .form-group input:hover {
            border-color: #D0EAE6;
            background: #ffffff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #03624C;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(3, 98, 76, 0.1);
        }

        .form-group input::placeholder {
            color: #bbb;
            font-weight: 400;
        }

        .error-message {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #d32f2f;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .alert-error {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            border: 2px solid #ef5350;
            color: #c62828;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 13px;
            animation: slideDown 0.3s ease-out;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .submit-btn {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #03624C 0%, #157A61 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 12px;
            box-shadow: 0 4px 15px rgba(3, 98, 76, 0.2);
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(3, 98, 76, 0.35);
        }

        .submit-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 35px 25px;
                margin: 20px;
                border-radius: 14px;
            }

            .login-header h1 {
                font-size: 28px;
            }

            .login-header {
                margin-bottom: 32px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Easygets Admin</h1>
            <p>Enter your credentials to continue</p>
        </div>

        <form action="{{ route('admin.login') }}" method="POST">
            @csrf

            {{-- Show general error --}}
            @if ($errors->has('email'))
                <div class="alert-error">
                    {{ $errors->first('email') }}
                </div>
            @endif

            {{-- Email Field --}}
            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="text" 
                    id="email"
                    name="email" 
                    value="{{ old('email') }}"
                    placeholder="admin@easygets.com" 
                    required 
                />
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            {{-- Password Field --}}
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password"
                    name="password"
                    placeholder="Enter your password" 
                    required 
                />
                @error('password')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            {{-- Submit Button --}}
            <button type="submit" class="submit-btn">
                Sign In
            </button>
        </form>
    </div>
</body>
</html>