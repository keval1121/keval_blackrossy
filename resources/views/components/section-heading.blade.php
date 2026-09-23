@props(['title', 'subtitle' => null])
<div class="mb-6 flex items-end justify-between gap-4">
    <div>
        <h2 class="font-serif text-3xl md:text-4xl">{{ $title }}</h2>
        @if($subtitle)<p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>@endif
    </div>
    {{ $slot }}
</div>
