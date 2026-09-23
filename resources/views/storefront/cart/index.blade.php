@extends('layouts.storefront')
@section('content')
<div class="container-store py-8" id="cart-contents">
    @include('storefront.cart.partials.contents')
</div>
@endsection
