<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login</title>
    @vite(['resources/css/admin.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-stone-950 text-white">
    <form method="post" class="w-full max-w-sm space-y-4 rounded-3xl bg-white p-8 text-stone-800">
        @csrf
        <p class="text-2xl font-semibold">{{ store_name() }} Admin</p>
        @error('email')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
        <input class="admin-input" name="email" type="email" value="{{ old('email') }}" placeholder="Email" required>
        <input class="admin-input" name="password" type="password" placeholder="Password" required>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Remember me</label>
        <button class="w-full rounded-xl bg-stone-900 py-3 text-white">Sign in</button>
    </form>
</body>
</html>
