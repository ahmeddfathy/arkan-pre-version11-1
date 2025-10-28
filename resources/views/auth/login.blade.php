<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Arkan') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #EBF5FB;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .background-bubbles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .bubble {
            position: absolute;
            background-color: rgba(74, 170, 227, 0.1);
            border-radius: 50%;
        }

        .login-container {
            width: 100%;
            max-width: 460px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .login-container:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
            transform: translateY(-5px);
        }

        .login-header {
            background: white;
            padding: 40px 0 30px;
            text-align: center;
            color: #333;
            position: relative;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(0,0,0,0.01) 0%, rgba(0,0,0,0) 70%);
            z-index: 1;
        }

        .login-header img {
            width: auto;
            height: 90px;
            margin-bottom: 25px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.08));
            display: inline-block;
            transition: transform 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .login-header h2,
        .login-header p {
            position: relative;
            z-index: 2;
        }

        .login-header h2 {
            position: relative;
            z-index: 2;
            color: #1c82b7;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .login-header p {
            position: relative;
            z-index: 2;
            color: #666;
            font-size: 16px;
        }

        .login-form {
            padding: 35px 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-size: 16px;
            font-weight: 500;
            color: #333;
            margin-bottom: 10px;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #4aaae3;
            font-size: 18px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
        }

        .toggle-password:hover {
            color: #4aaae3;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #fff;
        }

        .form-control:focus {
            border-color: #4aaae3;
            box-shadow: 0 0 0 3px rgba(74, 170, 227, 0.1);
        }

        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .remember-checkbox {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            margin-right: 10px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }

        .remember-checkbox:checked {
            background-color: #4aaae3;
            border-color: #4aaae3;
        }

        .remember-checkbox:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 14px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .remember-label {
            font-size: 14px;
            color: #555;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 14px;
            color: #4aaae3;
            text-decoration: none;
            transition: all 0.3s;
        }

        .forgot-link:hover {
            color: #1c82b7;
            text-decoration: underline;
        }

        .btn-login {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 15px;
            background: #4aaae3;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: #1c82b7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 170, 227, 0.3);
        }

        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-size: 14px;
        }

        .alert-success {
            background-color: rgba(209, 250, 229, 0.8);
            color: #0f766e;
            border-left: 4px solid #0f766e;
        }

        .alert-danger {
            background-color: rgba(254, 226, 226, 0.8);
            color: #b91c1c;
            border-left: 4px solid #b91c1c;
        }

        .login-icon {
            margin-right: 8px;
        }

        @media (max-width: 576px) {
            .login-container {
                max-width: 100%;
                margin: 0 15px;
            }

            .login-form {
                padding: 30px 20px;
            }

            .login-header {
                padding: 30px 0;
            }
        }
    </style>
</head>
<body>
    <div class="background-bubbles" id="background-bubbles"></div>

    <div class="login-container">
        <div class="login-header">
            <img src="{{ asset('assets/images/arkan.png') }}" alt="Arkan Logo" class="img-fluid">
            <h2>Welcome to Arkan</h2>
            <p>Economic Consultancy Services</p>
        </div>

        <div class="login-form">
            @if (session('status'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
            </div>
            @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>
            </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password">
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
            </div>

                <div class="options-row">
                    <div class="remember-me">
                        <input type="checkbox" class="remember-checkbox" id="remember_me" name="remember">
                        <label class="remember-label" for="remember_me">Remember me</label>
            </div>

                @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">
                            Forgot password?
                    </a>
                @endif
            </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt login-icon"></i> Sign In
                </button>
        </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Create floating bubbles
            const bubblesContainer = document.getElementById('background-bubbles');
            const numberOfBubbles = 15;

            for (let i = 0; i < numberOfBubbles; i++) {
                const size = Math.random() * 100 + 50;
                const bubble = document.createElement('div');
                bubble.classList.add('bubble');
                bubble.style.width = `${size}px`;
                bubble.style.height = `${size}px`;
                bubble.style.left = `${Math.random() * 100}%`;
                bubble.style.top = `${Math.random() * 100}%`;

                // Animation
                bubble.style.animation = `floating ${Math.random() * 10 + 15}s infinite ease-in-out`;
                bubble.style.animationDelay = `${Math.random() * 5}s`;

                // Add a unique animation
                const keyframes = `
                    @keyframes floating_${i} {
                        0% { transform: translate(0, 0) rotate(0deg); }
                        25% { transform: translate(${Math.random() * 50 - 25}px, ${Math.random() * 50 - 25}px) rotate(${Math.random() * 20}deg); }
                        50% { transform: translate(${Math.random() * 50 - 25}px, ${Math.random() * 50 - 25}px) rotate(${Math.random() * 20}deg); }
                        75% { transform: translate(${Math.random() * 50 - 25}px, ${Math.random() * 50 - 25}px) rotate(${Math.random() * 20}deg); }
                        100% { transform: translate(0, 0) rotate(0deg); }
                    }
                `;

                const style = document.createElement('style');
                style.innerHTML = keyframes;
                document.head.appendChild(style);

                bubble.style.animation = `floating_${i} ${Math.random() * 15 + 20}s infinite ease-in-out`;

                bubblesContainer.appendChild(bubble);
            }

            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    togglePassword.classList.remove('fa-eye');
                    togglePassword.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    togglePassword.classList.remove('fa-eye-slash');
                    togglePassword.classList.add('fa-eye');
                }
            });
        });
    </script>
</body>
</html>
