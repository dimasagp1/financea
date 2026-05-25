<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-800 flex items-center justify-center p-6">
    <div class="w-full max-w-2xl rounded-3xl bg-white/95 p-10 shadow-2xl border border-white/50 text-center">
        <p class="inline-flex px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-sm font-medium mb-4">403</p>
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Forbidden</h1>
        <p class="text-slate-500 mb-8">Anda tidak memiliki izin untuk membuka halaman ini.</p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('dashboard.index') }}" class="rounded-xl bg-slate-900 text-white px-5 py-2.5">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
