@props([
    'padding' => 'p-8 sm:p-10',
])

<div
    {{ $attributes->class([
        'rounded-3xl border border-slate-200/90 bg-white/85 shadow-2xl shadow-indigo-950/10 backdrop-blur-xl ring-1 ring-slate-900/[0.04]',
        $padding,
    ]) }}
>
    {{ $slot }}
</div>
