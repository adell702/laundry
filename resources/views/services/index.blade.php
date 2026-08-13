@extends('layouts.app')
@section('title', 'Layanan')
@section('content')
<div class="space-y-4">
    <div class="flex justify-end">
        <x-btn href="{{ route('services.create') }}">+ Layanan</x-btn>
    </div>
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nama</th>
                        <th class="px-5 py-3 font-medium">Satuan</th>
                        <th class="px-5 py-3 font-medium">Harga</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($services as $service)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="font-medium">{{ $service->name }}</div>
                                <div class="text-xs text-slate-400">{{ $service->description }}</div>
                            </td>
                            <td class="px-5 py-3">{{ $service->unit }}</td>
                            <td class="px-5 py-3 font-medium">Rp {{ number_format($service->price, 0, ',', '.') }}</td>
                            <td class="px-5 py-3">
                                <x-badge :color="$service->is_active ? 'green' : 'red'">{{ $service->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2">
                                <a href="{{ route('services.edit', $service) }}" class="text-sky-700 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('services.destroy', $service) }}" class="inline" onsubmit="return confirm('Hapus?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t">{{ $services->links() }}</div>
    </x-card>
</div>
@endsection
