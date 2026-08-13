@props(['label', 'value', 'hint' => null, 'color' => 'sky'])
@php
$bg = [
    'sky' => 'from-sky-500 to-sky-700',
    'emerald' => 'from-emerald-500 to-emerald-700',
    'amber' => 'from-amber-500 to-amber-700',
    'violet' => 'from-violet-500 to-violet-700',
    'rose' => 'from-rose-500 to-rose-700',
];
@endphp
<div class="rounded-xl bg-gradient-to-br {{ $bg[$color] ?? $bg['sky'] }} text-white p-5 shadow-sm">
    <div class="text-xs uppercase tracking-wide text-white/80">{{ $label }}</div>
    <div class="mt-2 text-2xl font-bold">{{ $value }}</div>
    @if($hint)
        <div class="mt-1 text-xs text-white/70">{{ $hint }}</div>
    @endif
</div>
