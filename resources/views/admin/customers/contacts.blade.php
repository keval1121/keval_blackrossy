@extends('layouts.admin')
@section('title', 'Messages')
@section('content')
<div class="space-y-3">
    @foreach($messages as $message)
        <article class="rounded-2xl bg-white p-4">
            <p class="font-medium">{{ $message->name }} · {{ $message->email }} · {{ $message->mobile }}</p>
            <p class="text-sm text-stone-500">{{ $message->subject }}</p>
            <p class="mt-2 text-sm">{{ $message->message }}</p>
        </article>
    @endforeach
</div>
<div class="mt-4">{{ $messages->links() }}</div>
@endsection
