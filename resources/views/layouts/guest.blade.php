<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AA Laundry') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-sky-50 to-slate-100">
        <div class="text-center">
            <div class="text-2xl font-bold text-sky-900">AA Laundry</div>
            <div class="text-sm text-slate-500">Super Clean Laundry System</div>
        </div>
        <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-lg border border-slate-200 overflow-hidden sm:rounded-2xl">
            {{ $slot }}
        </div>
        <a href="{{ route('tracking.index') }}" class="mt-6 text-sm text-sky-700 hover:underline">Lacak cucian (pelanggan)</a>
    </div>
</body>
</html>
