@props(['equipment', 'alt' => 'Foto alat'])

@php
    $photoAvailable = filled($equipment?->foto) && Storage::disk('public')->exists($equipment->foto);
@endphp

@if($photoAvailable)
    <img {{ $attributes->merge(['class' => 'equipment-photo']) }} src="{{ asset('storage/'.$equipment->foto) }}" alt="{{ $alt }}" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
    <span {{ $attributes->merge(['class' => 'equipment-photo equipment-photo-placeholder']) }} role="img" aria-label="{{ $alt }}" hidden>
        <i class="bi bi-tools" aria-hidden="true"></i>
    </span>
@else
    <span {{ $attributes->merge(['class' => 'equipment-photo equipment-photo-placeholder']) }} role="img" aria-label="{{ $alt }}">
        <i class="bi bi-tools" aria-hidden="true"></i>
    </span>
@endif
