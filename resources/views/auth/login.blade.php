<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AI Marketing Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.3);
            padding: 36px 32px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                <i class="fa-solid fa-paper-plane fs-3"></i>
            </div>
            <h4 class="fw-bold mb-1">AI Marketing Manager</h4>
            <p class="text-muted small">Sign in to your administration panel</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2 small mb-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold small">Email Address</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', 'admin@thenexteck.com') }}" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Password</label>
                <input type="password" name="password" class="form-control" value="password" required>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="rememberCheck" checked>
                <label class="form-check-label small text-muted" for="rememberCheck">Remember this session</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                Sign In
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center text-muted small">
            Domain: <strong>marketing.thenexteck.com</strong><br>
            AWS SES Region: <strong>ap-south-1 (Mumbai)</strong>
        </div>
    </div>
</body>
</html>
