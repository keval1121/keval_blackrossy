@props(['position'])
@php($code = ad_code($position))
@if($code)
    <aside class="ad-slot my-8 rounded-3xl p-4" aria-label="Advertisement">{!! $code !!}</aside>
@endif
