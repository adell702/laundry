@props(['color' => 'slate'])
@php
$colors = [
    'slate' => 'bg-slate-100 text-slate-700',
    'green' => 'bg-emerald-100 text-emerald-800',
    'yellow' => 'bg-amber-100 text-amber-800',
    'red' => 'bg-red-100 text-red-800',
    'blue' => 'bg-sky-100 text-sky-800',
    'purple' => 'bg-violet-100 text-violet-800',
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium '.($colors[$color] ?? $colors['slate'])]) }}>
    {{ $slot }}
</span>
