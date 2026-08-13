@php
    $link = 'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition';
    $active = 'bg-sky-800 text-white';
    $idle = 'text-sky-100 hover:bg-sky-800/60';
@endphp

<a href="{{ route('dashboard') }}" class="{{ $link }} {{ request()->routeIs('dashboard') ? $active : $idle }}">Dashboard</a>
<a href="{{ route('transactions.index') }}" class="{{ $link }} {{ request()->routeIs('transactions.*') ? $active : $idle }}">Transaksi</a>
<a href="{{ route('customers.index') }}" class="{{ $link }} {{ request()->routeIs('customers.*') ? $active : $idle }}">Pelanggan</a>
<a href="{{ route('expenses.index') }}" class="{{ $link }} {{ request()->routeIs('expenses.*') ? $active : $idle }}">Pengeluaran</a>

@if(auth()->user()->isAdmin())
    <div class="pt-3 pb-1 px-3 text-[10px] uppercase tracking-wider text-sky-400">Admin</div>
    <a href="{{ route('services.index') }}" class="{{ $link }} {{ request()->routeIs('services.*') ? $active : $idle }}">Layanan</a>
    <a href="{{ route('users.index') }}" class="{{ $link }} {{ request()->routeIs('users.*') ? $active : $idle }}">Karyawan</a>
    <a href="{{ route('reports.index') }}" class="{{ $link }} {{ request()->routeIs('reports.*') ? $active : $idle }}">Laporan</a>
    <a href="{{ route('activity-logs.index') }}" class="{{ $link }} {{ request()->routeIs('activity-logs.*') ? $active : $idle }}">Log Aktivitas</a>
@endif

<a href="{{ route('profile.edit') }}" class="{{ $link }} {{ request()->routeIs('profile.*') ? $active : $idle }}">Profil</a>
