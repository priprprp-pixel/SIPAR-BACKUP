@props(['equipment', 'alt' => 'Foto alat'])

@php
    $photoAvailable = filled($equipment?->foto) && Storage::disk('public')->exists($equipment->foto);
@endphp

@if($photoAvailable)
    <img {{ $attributes->merge(['class' => 'equipment-photo']) }} src="{{ Storage::url($equipment->foto) }}" alt="{{ $alt }}" loading="lazy">
@else
    <span {{ $attributes->merge(['class' => 'equipment-photo equipment-photo-placeholder']) }} role="img" aria-label="{{ $alt }}">
        <i class="bi bi-tools" aria-hidden="true"></i>
    </span>
@endif
