<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'AA Laundry') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-100 text-slate-800">
<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex lg:flex-col w-64 bg-sky-900 text-white shrink-0">
        <div class="px-6 py-5 border-b border-sky-800">
            <div class="text-lg font-bold tracking-tight">AA Laundry</div>
            <div class="text-xs text-sky-300 mt-0.5">Super Clean Laundry</div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            @include('layouts.nav-links')
        </nav>
        <div class="px-4 py-4 border-t border-sky-800 text-sm">
            <div class="font-medium">{{ auth()->user()->name }}</div>
            <div class="text-sky-300 text-xs uppercase">{{ auth()->user()->role }}</div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        {{-- Top bar mobile --}}
        <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
            <div class="flex items-center justify-between px-4 py-3 lg:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" id="mobile-menu-btn" class="lg:hidden p-2 rounded-md hover:bg-slate-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-semibold text-slate-800">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('tracking.index') }}" target="_blank" class="hidden sm:inline text-sm text-sky-700 hover:underline">Lacak Cucian</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm text-slate-500 hover:text-red-600">Logout</button>
                    </form>
                </div>
            </div>
            {{-- Mobile nav --}}
            <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-200 bg-sky-900 text-white px-3 py-3 space-y-1">
                @include('layouts.nav-links')
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<script>
document.getElementById('mobile-menu-btn')?.addEventListener('click', () => {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});
</script>
@stack('scripts')
</body>
</html>
