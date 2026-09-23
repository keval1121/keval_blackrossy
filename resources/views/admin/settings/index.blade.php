@extends('layouts.admin')
@section('title', 'Settings')
@section('content')
<form method="post" action="{{ route('admin.settings.save') }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 rounded-2xl bg-white p-6">
    @csrf
    <input class="admin-input" name="website_name" value="{{ setting('website_name', store_name()) }}" placeholder="Website name">
    <input class="admin-input" name="contact_number" value="{{ setting('contact_number') }}" placeholder="Contact number">
    <input class="admin-input" name="whatsapp_number" value="{{ setting('whatsapp_number') }}" placeholder="WhatsApp number">
    <input class="admin-input" name="contact_email" value="{{ setting('contact_email') }}" placeholder="Email">
    <input class="admin-input" name="address" value="{{ setting('address') }}" placeholder="Address">
    <input class="admin-input" name="facebook" value="{{ setting('facebook') }}" placeholder="Facebook URL">
    <input class="admin-input" name="instagram" value="{{ setting('instagram') }}" placeholder="Instagram URL">
    <input class="admin-input" name="youtube" value="{{ setting('youtube') }}" placeholder="YouTube URL">
    <input class="admin-input" name="default_delivery_charge" value="{{ setting('default_delivery_charge', 49) }}" placeholder="Default delivery charge">
    <input class="admin-input" name="free_shipping_amount" value="{{ setting('free_shipping_amount', 999) }}" placeholder="Free shipping above">
    <input class="admin-input" name="seo_title" value="{{ setting('seo_title') }}" placeholder="Default SEO title">
    <textarea class="admin-input" name="seo_description" placeholder="Default SEO description">{{ setting('seo_description') }}</textarea>
    <p class="text-sm">Logo</p><input type="file" name="logo" class="admin-input">
    <p class="text-sm">Favicon</p><input type="file" name="favicon" class="admin-input">
    <label class="block text-sm"><input type="checkbox" name="cod_enabled" @checked(setting('cod_enabled', true))> COD enabled</label>
    <label class="block text-sm"><input type="checkbox" name="otp_enabled" @checked(setting('otp_enabled', false))> OTP at checkout</label>
    <label class="block text-sm"><input type="checkbox" name="coupons_enabled" @checked(setting('coupons_enabled', true))> Coupons</label>
    <label class="block text-sm"><input type="checkbox" name="reviews_enabled" @checked(setting('reviews_enabled', true))> Reviews</label>
    <label class="block text-sm"><input type="checkbox" name="adsense_enabled" @checked(setting('adsense_enabled', false))> AdSense</label>
    <label class="block text-sm"><input type="checkbox" name="restore_stock_on_cancel" @checked(setting('restore_stock_on_cancel', true))> Restore stock on cancel/return</label>
    <label class="block text-sm"><input type="checkbox" name="maintenance_mode" @checked(setting('maintenance_mode', false))> Maintenance mode</label>
    <button class="rounded-xl bg-stone-900 px-5 py-3 text-white">Save settings</button>
</form>
@endsection
