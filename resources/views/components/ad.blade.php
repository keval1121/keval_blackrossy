@props(['position'])
@php($code = ad_code($position))
@if($code)
    <aside class="ad-slot my-10" aria-label="Advertisement">
        <p class="ad-slot-label">Advertisement</p>
        <div class="ad-slot-unit">{!! $code !!}</div>
    </aside>
@endif
