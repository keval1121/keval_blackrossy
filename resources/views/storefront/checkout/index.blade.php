@extends('layouts.storefront')
@section('content')
<div class="container-store py-8 md:grid md:grid-cols-[1fr_340px] md:gap-8">
    <form method="post" action="{{ route('checkout.place') }}" class="space-y-4 rounded-[2rem] bg-white p-6">
        @csrf
        <h1 class="font-serif text-4xl">Checkout</h1>
        <p class="text-sm text-muted">No account needed. Pay with Cash on Delivery.</p>
        <input name="name" value="{{ old('name') }}" placeholder="Full name *" required>
        <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
            <input name="mobile" id="mobile" value="{{ old('mobile') }}" placeholder="Mobile number *" required>
            @if(setting('otp_enabled'))
                <button type="button" id="otp-btn" class="btn btn-outline">Send OTP</button>
            @endif
        </div>
        @if(setting('otp_enabled'))
            <input name="otp" placeholder="Enter 6-digit OTP" maxlength="6">
            <p id="otp-help" class="text-xs text-muted"></p>
        @endif
        <textarea name="address" rows="3" placeholder="Address *" required>{{ old('address') }}</textarea>
        <input name="area" value="{{ old('area') }}" placeholder="Area / Landmark">
        <div class="grid gap-3 sm:grid-cols-2">
            <input name="city" value="{{ old('city') }}" placeholder="City *" required>
            <select name="state" required>
                <option value="">State *</option>
                @foreach(indian_states() as $state)
                    <option value="{{ $state }}" @selected(old('state')===$state)>{{ $state }}</option>
                @endforeach
            </select>
        </div>
        <input name="pincode" value="{{ old('pincode') }}" placeholder="Pincode *" required>
        <label class="flex items-center gap-3 rounded-2xl bg-sand p-4 text-sm">
            <input type="radio" checked class="h-4 w-4"> Cash on Delivery
        </label>
        <button class="btn btn-gold w-full">Place order</button>
    </form>
    <aside class="mt-6 h-fit rounded-[2rem] bg-white p-6 md:mt-0">
        <h2 class="font-semibold">Order summary</h2>
        @foreach($items as $line)
            <p class="mt-3 flex justify-between text-sm"><span>{{ $line['name'] }} × {{ $line['qty'] }}</span><span>{{ money($line['total']) }}</span></p>
        @endforeach
        <p class="mt-4 flex justify-between"><span>Subtotal</span><span>{{ money($subtotal) }}</span></p>
        <p class="flex justify-between"><span>Delivery</span><span>{{ $delivery ? money($delivery) : 'Free' }}</span></p>
        <p class="mt-3 flex justify-between border-t border-line pt-3 font-semibold"><span>Total</span><span>{{ money($total) }}</span></p>
    </aside>
</div>
@if(setting('otp_enabled'))
<script>
    document.getElementById('otp-btn')?.addEventListener('click', async () => {
        const mobile = document.getElementById('mobile').value;
        try {
            const data = await BlackRossy.jsonFetch('{{ route('checkout.otp') }}', { method: 'POST', body: JSON.stringify({ mobile, _token: BlackRossy.csrf() }) });
            document.getElementById('otp-help').textContent = data.debug_otp ? `Demo OTP: ${data.debug_otp}` : data.message;
        } catch (e) { BlackRossy.toast(e.message, 'error'); }
    });
</script>
@endif
@endsection
