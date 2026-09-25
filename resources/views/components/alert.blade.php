@props(['type' => 'success'])

@php
    $styles = [
        'success' => 'bg-success-50 text-success-600 ring-success-100',
        'danger' => 'bg-danger-50 text-danger-700 ring-danger-100',
        'warning' => 'bg-accent-50 text-accent-700 ring-accent-100',
        'info' => 'bg-primary-50 text-primary-700 ring-primary-100',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg px-4 py-3 text-sm font-medium ring-1 '.($styles[$type] ?? $styles['info'])]) }} role="alert">
    {{ $slot }}
</div>
