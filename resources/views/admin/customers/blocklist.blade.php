@extends('layouts.admin')
@section('title', 'Blocklist')
@section('content')
<form method="post" class="mb-4 flex gap-2">
    @csrf
    <input class="admin-input w-48" name="mobile" placeholder="Mobile" required>
    <input class="admin-input w-64" name="reason" placeholder="Reason">
    <button class="rounded-xl bg-stone-900 px-4 text-white">Block</button>
</form>
<ul class="rounded-2xl bg-white p-5 text-sm">
    @foreach($numbers as $row)<li class="py-2">{{ $row->mobile }} — {{ $row->reason }}</li>@endforeach
</ul>
@endsection
