<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — WA Meta</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif; background: #0a0f1a; color: #f3f4f6;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(37,211,102,0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(59,130,246,0.06) 0%, transparent 50%);
        }
        .login-container {
            width: 100%; max-width: 420px; padding: 20px;
        }
        .login-card {
            background: rgba(17,24,39,0.8); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px; padding: 40px 32px; backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .login-brand {
            text-align: center; margin-bottom: 32px;
        }
        .login-brand .logo {
            width: 56px; height: 56px; background: linear-gradient(135deg, #25D366, #128c50);
            border-radius: 14px; display: inline-flex; align-items: center; justify-content: center;
            font-size: 28px; color: white; margin-bottom: 16px;
        }
        .login-brand h1 { font-size: 24px; font-weight: 800; }
        .login-brand p { font-size: 14px; color: #6b7280; margin-top: 6px; }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #9ca3af; margin-bottom: 8px; }
        .form-control {
            width: 100%; padding: 12px 14px; background: #0a0f1a;
            border: 1px solid rgba(255,255,255,0.06); border-radius: 10px;
            color: #f3f4f6; font-size: 14px; font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }
        .form-control:focus { outline: none; border-color: #25D366; box-shadow: 0 0 0 3px rgba(37,211,102,0.12); }

        .btn-login {
            width: 100%; padding: 12px; background: #25D366; color: #000;
            border: none; border-radius: 10px; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif;
        }
        .btn-login:hover { background: #1fb855; transform: translateY(-1px); }

        .remember-row { display: flex; align-items: center; gap: 8px; margin-bottom: 24px; }
        .remember-row input { accent-color: #25D366; }
        .remember-row label { font-size: 13px; color: #9ca3af; cursor: pointer; }

        .alert-danger {
            background: rgba(239,68,68,0.12); color: #ef4444;
            border: 1px solid rgba(239,68,68,0.2); padding: 12px 16px;
            border-radius: 10px; margin-bottom: 20px; font-size: 14px;
            display: flex; align-items: center; gap: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-brand">
                <div class="logo"><i class="bi bi-whatsapp"></i></div>
                <h1>WA Meta</h1>
                <p>WhatsApp Business API Manager</p>
            </div>

            @if($errors->any())
                <div class="alert-danger">
                    <i class="bi bi-x-circle-fill"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Email address" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Ingat saya</label>
                </div>
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>
        </div>
    </div>
</body>
</html>
