<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lacak Cucian — AA Laundry</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-sky-50 to-slate-100 font-sans antialiased flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
        <div class="text-center mb-6">
            <div class="text-2xl font-bold text-sky-900">AA Laundry</div>
            <p class="text-sm text-slate-500 mt-1">Lacak status cucian Anda</p>
        </div>
        <form method="GET" action="{{ route('tracking.show') }}" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kode Invoice</label>
                <input type="text" name="invoice_code" required placeholder="SCL-XXXXXX-XXXX"
                       class="w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500 text-sm uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">No. Telepon</label>
                <input type="text" name="phone" required placeholder="08xxxxxxxxxx"
                       class="w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500 text-sm">
            </div>
            <button type="submit" class="w-full bg-sky-700 hover:bg-sky-800 text-white font-semibold py-2.5 rounded-lg text-sm">
                Lacak
            </button>
        </form>
        <p class="text-xs text-center text-slate-400 mt-6">Gunakan kode invoice + nomor telepon saat registrasi.</p>
    </div>
</body>
</html>
