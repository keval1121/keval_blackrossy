@extends('layouts.admin')
@section('title', 'Brands')
@section('content')
<form method="post" action="{{ route('admin.brands.save') }}" class="mb-4 flex gap-2">
    @csrf
    <input class="admin-input w-64" name="name" placeholder="Brand name" required>
    <button class="rounded-xl bg-stone-900 px-4 text-white">Add</button>
</form>
<ul class="rounded-2xl bg-white p-5 text-sm">
    @foreach($brands as $brand)<li class="border-b border-stone-100 py-2">{{ $brand->name }}</li>@endforeach
</ul>
@endsection
