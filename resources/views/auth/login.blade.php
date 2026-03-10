<x-guest-layout>
    <div class="mb-4">
        <div class="h4 fw-bold mb-1">Sign in</div>
        <div class="text-muted">Use your account credentials to continue.</div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email"
                       name="email"
                       class="form-control"
                       value="{{ old('email') }}"
                       required autofocus autocomplete="username"
                       placeholder="you@office.gov.ph">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password"
                       name="password"
                       class="form-control"
                       required autocomplete="current-password"
                       placeholder="••••••••">
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                <label class="form-check-label" for="remember_me">Remember me</label>
            </div>

            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <button class="btn btn-primary w-100 py-2" type="submit">
            <i class="bi bi-box-arrow-in-right me-1"></i> Login
        </button>

        @if (Route::has('register'))
            <div class="text-center mt-3">
                <span class="text-muted">No account?</span>
                <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Create one</a>
            </div>
        @endif
        <div class="text-center mt-3">
                <span class="text-muted">Having trouble? Contact the MIS Office.</span>
            </div>
    </form>
</x-guest-layout>
