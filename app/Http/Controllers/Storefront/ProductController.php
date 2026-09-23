<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ReviewRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\CatalogService;
use App\Services\OtpService;

class ProductController extends Controller
{
    public function show(Product $product, CatalogService $catalog)
    {
        abort_unless($product->status->value === 'active', 404);

        $product->load([
            'images',
            'brand',
            'category',
            'subCategory',
            'tags',
            'activeVariants.values.attribute',
            'activeVariants.values.attributeValue',
            'approvedReviews',
        ]);

        $attributes = $product->activeVariants
            ->flatMap->values
            ->groupBy('attribute_id')
            ->map(function ($values) {
                $attribute = $values->first()->attribute;
                return [
                    'attribute' => $attribute,
                    'values' => $values->map->attributeValue->unique('id')->values(),
                ];
            })
            ->values();

        $recent = collect(session('recently_viewed', []))
            ->reject(fn ($id) => (int) $id === $product->id)
            ->take(8)
            ->values();
        session(['recently_viewed' => collect([$product->id])->merge($recent)->unique()->take(12)->values()->all()]);

        $recentProducts = Product::query()
            ->active()
            ->with('primaryImage')
            ->whereIn('id', $recent)
            ->get();

        return view('storefront.product.show', [
            'product' => $product,
            'attributes' => $attributes,
            'related' => $catalog->related($product),
            'recentProducts' => $recentProducts,
            'variantMap' => $product->activeVariants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->sellingPrice(),
                'mrp' => $variant->listPrice(),
                'stock' => $variant->stock,
                'label' => $variant->label(),
                'attrs' => $variant->attributeMap(),
            ])->values(),
        ]);
    }

    public function review(ReviewRequest $request, Product $product, OtpService $otp)
    {
        $order = Order::query()
            ->where('order_number', strtoupper($request->string('order_number')))
            ->where('mobile', $otp->normalize($request->string('mobile')))
            ->first();

        if (! $order || ! $order->items()->where('product_id', $product->id)->exists()) {
            return back()->withErrors(['order_number' => 'We could not match this order with the product.'])->withInput();
        }

        Review::query()->create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'name' => $request->string('name'),
            'rating' => $request->integer('rating'),
            'body' => $request->string('body'),
            'is_approved' => false,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Thank you. Your review will appear after approval.');
    }
}
