@extends('layouts.admin')
@section('title', 'Queries')
@section('content')
<p class="mb-4 text-sm text-stone-500">Messages from the Contact us form. Unread queries are listed first.</p>
<div class="space-y-3">
    @forelse($messages as $message)
        <article class="rounded-2xl bg-white p-4 {{ $message->is_read ? '' : 'ring-1 ring-amber-200' }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-medium">{{ $message->name }}</p>
                    <p class="text-sm text-stone-500">
                        {{ $message->email ?: 'No email' }}
                        ·
                        {{ $message->mobile ?: 'No mobile' }}
                        ·
                        {{ $message->created_at->format('d M Y, h:i A') }}
                    </p>
                    @if($message->subject)
                        <p class="mt-1 text-sm text-stone-600">{{ $message->subject }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @if($message->is_read)
                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Read</span>
                    @else
                        <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">New</span>
                        <form method="post" action="{{ route('admin.messages.read', $message) }}">
                            @csrf
                            <button class="font-semibold text-emerald-700 hover:underline">Mark read</button>
                        </form>
                    @endif
                    <form method="post" action="{{ route('admin.messages.delete', $message) }}" onsubmit="return confirm('Delete this query?')">
                        @csrf
                        @method('DELETE')
                        <button class="font-semibold text-rose-700 hover:underline">Delete</button>
                    </form>
                </div>
            </div>
            <p class="mt-3 whitespace-pre-line text-sm text-stone-700">{{ $message->message }}</p>
        </article>
    @empty
        <p class="rounded-2xl bg-white p-6 text-center text-stone-500">No queries yet.</p>
    @endforelse
</div>
<div class="mt-4">{{ $messages->links() }}</div>
@endsection
