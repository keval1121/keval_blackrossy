@extends('layouts.admin')
@section('title', 'Reviews')
@section('content')
<table class="w-full rounded-2xl bg-white text-left text-sm">
    @foreach($reviews as $review)
        <tr class="border-t border-stone-100">
            <td class="p-3">{{ $review->product?->name }}</td>
            <td>{{ $review->name }} · ★ {{ $review->rating }}</td>
            <td>{{ $review->body }}</td>
            <td>
                @unless($review->is_approved)
                    <form method="post" action="{{ route('admin.reviews.approve', $review) }}">@csrf<button class="text-emerald-700">Approve</button></form>
                @endunless
                <form method="post" action="{{ route('admin.reviews.delete', $review) }}">@csrf @method('DELETE')<button class="text-rose-700">Delete</button></form>
            </td>
        </tr>
    @endforeach
</table>
<div class="mt-4">{{ $reviews->links() }}</div>
@endsection
