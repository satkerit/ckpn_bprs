<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - CKPN BPRS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-surface-100 p-4 sm:p-6">
    <main class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl shadow-surface-900/5 ring-1 ring-surface-200 sm:p-8">
        <div class="mb-8 flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-600 text-sm font-bold text-white">CB</span>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-primary-600">CKPN</p>
                <p class="font-bold text-surface-900">BPRS Babel</p>
            </div>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-surface-900">Masuk ke sistem</h1>
        <p class="mt-1 text-sm text-surface-500">Kelola perhitungan CKPN secara terpusat.</p>
        @if ($errors->any())
            <x-alert type="danger" class="mt-5">Email atau password tidak valid.</x-alert>
        @endif
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="form-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" aria-label="Email address" class="form-input px-3 py-2.5">
                @error('email') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="form-label">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" aria-label="Password" class="form-input px-3 py-2.5">
                @error('password') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            <label class="flex min-h-11 items-center gap-2 text-sm text-surface-600">
                <input name="remember" type="checkbox" value="1" class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500"> Ingat saya
            </label>
            <button type="submit" aria-label="Sign in" class="btn btn-primary w-full">Masuk</button>
        </form>
    </main>
</body>
</html>
