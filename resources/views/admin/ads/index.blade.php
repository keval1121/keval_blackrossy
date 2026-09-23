@extends('layouts.admin')
@section('title', 'Ad placements')
@section('content')
<p class="mb-4 text-sm text-stone-500">Paste AdSense unit code per placement. Ads stay off checkout, cart and order confirmation.</p>
<div class="space-y-4">
    @foreach($ads as $ad)
        <form method="post" action="{{ route('admin.ads.save', $ad) }}" class="rounded-2xl bg-white p-5">
            @csrf
            <p class="font-semibold">{{ $ad->name }} <span class="text-xs text-stone-400">{{ $ad->position }}</span></p>
            <textarea class="admin-input mt-2 min-h-24" name="code">{{ $ad->code }}</textarea>
            <input type="hidden" name="name" value="{{ $ad->name }}">
            <label class="mt-2 block text-sm"><input type="checkbox" name="is_active" @checked($ad->is_active)> Active</label>
            <button class="mt-3 rounded-xl bg-stone-900 px-4 py-2 text-white">Save</button>
        </form>
    @endforeach
</div>
@endsection
