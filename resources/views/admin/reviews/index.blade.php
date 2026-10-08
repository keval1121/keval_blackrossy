@extends('layouts.admin')
@section('title', 'Reviews')
@section('content')
<p class="mb-4 text-sm text-stone-500">New reviews stay hidden on the store until you approve them. Pending reviews are listed first.</p>
<div class="overflow-x-auto rounded-2xl bg-white">
    <table class="w-full text-left text-sm">
        <thead class="bg-stone-50 text-xs uppercase tracking-wide text-stone-500">
            <tr>
                <th class="p-3">Product</th>
                <th class="p-3">Customer</th>
                <th class="p-3">Rating</th>
                <th class="p-3">Review</th>
                <th class="p-3">Status</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reviews as $review)
                <tr class="border-t border-stone-100 align-top">
                    <td class="p-3">
                        @if($review->product)
                            <a href="{{ $review->product->url() }}" target="_blank" class="font-medium hover:underline">{{ $review->product->name }}</a>
                        @else
                            <span class="text-stone-400">Deleted product</span>
                        @endif
                    </td>
                    <td class="p-3">
                        <p class="font-medium">{{ $review->name }}</p>
                        <p class="text-xs text-stone-500">Order {{ $review->order_number }}</p>
                        <p class="text-xs text-stone-500">{{ $review->created_at->format('d M Y, h:i A') }}</p>
                    </td>
                    <td class="whitespace-nowrap p-3 text-amber-600">{{ str_repeat('★', $review->rating) }}<span class="text-stone-300">{{ str_repeat('★', 5 - $review->rating) }}</span></td>
                    <td class="max-w-sm p-3 text-stone-600">{{ $review->body ?: '—' }}</td>
                    <td class="p-3">
                        @if($review->is_approved)
                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Approved</span>
                        @else
                            <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">Pending</span>
                        @endif
                    </td>
                    <td class="p-3">
                        <div class="flex items-center gap-3">
                            @unless($review->is_approved)
                                <form method="post" action="{{ route('admin.reviews.approve', $review) }}">
                                    @csrf
                                    <button class="font-semibold text-emerald-700 hover:underline">Approve</button>
                                </form>
                            @endunless
                            <form method="post" action="{{ route('admin.reviews.delete', $review) }}" onsubmit="return confirm('Delete this review?')">
                                @csrf
                                @method('DELETE')
                                <button class="font-semibold text-rose-700 hover:underline">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-6 text-center text-stone-500">No reviews yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $reviews->links() }}</div>
@endsection
