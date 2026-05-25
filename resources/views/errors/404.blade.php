<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-800 flex items-center justify-center p-6">
    <div class="w-full max-w-2xl rounded-3xl bg-white/95 p-10 shadow-2xl border border-white/50 text-center">
        <p class="inline-flex px-3 py-1 rounded-full bg-slate-200 text-slate-700 text-sm font-medium mb-4">404</p>
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Halaman Tidak Ditemukan</h1>
        <p class="text-slate-500 mb-8">URL yang Anda akses tidak tersedia di sistem ini.</p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('dashboard.index') }}" class="rounded-xl bg-slate-900 text-white px-5 py-2.5">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
