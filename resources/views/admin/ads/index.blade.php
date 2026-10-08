@extends('layouts.admin')
@section('title', 'Ad placements')
@section('content')
<p class="mb-2 text-sm text-stone-500">Paste <strong>ad unit</strong> code here (the block with <code>data-ad-slot</code>). The publisher script already loads from Settings → Client ID in the site &lt;head&gt;.</p>
<p class="mb-4 text-sm text-stone-500">Keep placements inactive and “Show ads” off until AdSense marks the site Ready. Ads (including Auto ads) never load on cart, checkout, order, tracking or error pages, and every placement carries a small “Advertisement” label.</p>
<div class="space-y-4">
    @foreach($ads as $ad)
        <form method="post" action="{{ route('admin.ads.save', $ad) }}" class="rounded-2xl bg-white p-5">
            @csrf
            <p class="font-semibold">{{ $ad->name }} <span class="text-xs text-stone-400">{{ $ad->position }}</span></p>
            <textarea class="admin-input mt-2 min-h-24" name="code" placeholder="<ins class=&quot;adsbygoogle&quot; ... data-ad-slot=&quot;...&quot;></ins> + push script">{{ $ad->code }}</textarea>
            <input type="hidden" name="name" value="{{ $ad->name }}">
            <label class="mt-2 block text-sm"><input type="checkbox" name="is_active" @checked($ad->is_active)> Active</label>
            <button class="mt-3 rounded-xl bg-stone-900 px-4 py-2 text-white">Save</button>
        </form>
    @endforeach
</div>
@endsection
