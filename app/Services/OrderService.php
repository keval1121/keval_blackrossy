<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private CartService $cart,
        private OtpService $otp,
        private NotificationService $notifications,
    ) {}

    public function place(array $data, Cart $cart): Order
    {
        if (! setting('cod_enabled', true)) {
            throw new \RuntimeException('Cash on Delivery is temporarily unavailable.');
        }

        $mobile = $this->otp->normalize($data['mobile']);
        $this->otp->assertNotBlocked($mobile);

        if ($this->otp->enabled() && ! $this->otp->isVerified($mobile)) {
            throw new \RuntimeException('Please verify your mobile number with the OTP before placing the order.');
        }

        $stockErrors = $this->cart->validateStock($cart);
        if ($stockErrors->isNotEmpty()) {
            throw new \RuntimeException($stockErrors->first());
        }

        $this->assertFraudRules($mobile, $data);

        return DB::transaction(function () use ($data, $cart, $mobile) {
            $summary = $this->cart->summary($cart);
            $lockedItems = $cart->items()->lockForUpdate()->with(['product', 'variant'])->get();

            foreach ($lockedItems as $item) {
                if ($item->variant) {
                    $variant = ProductVariant::query()->where('id', $item->variant->id)->lockForUpdate()->first();
                    if (! $variant || $variant->stock < $item->quantity) {
                        throw new \RuntimeException('Stock changed for '.$item->product->name.'. Please update your cart.');
                    }
                } else {
                    $product = Product::query()->where('id', $item->product_id)->lockForUpdate()->first();
                    if (! $product || $product->stock_quantity < $item->quantity) {
                        throw new \RuntimeException('Stock changed for '.$item->product->name.'. Please update your cart.');
                    }
                }
            }

            $order = Order::query()->create([
                'order_number' => $this->nextNumber(),
                'status' => OrderStatus::Pending,
                'payment_method' => PaymentMethod::Cod,
                'payment_status' => 'unpaid',
                'subtotal' => $summary['subtotal'],
                'discount' => $summary['discount'],
                'delivery_charge' => $summary['delivery'],
                'total' => $summary['total'],
                'coupon_id' => $summary['coupon']?->id,
                'coupon_code' => $summary['coupon']?->code,
                'customer_name' => $data['name'],
                'mobile' => $mobile,
                'email' => $data['email'] ?? null,
                'notes' => $data['notes'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
                'otp_verified_at' => $this->otp->enabled() ? now() : null,
                'is_suspicious' => $data['_suspicious'] ?? false,
                'suspicious_reason' => $data['_suspicious_reason'] ?? null,
            ]);

            $order->address()->create([
                'name' => $data['name'],
                'mobile' => $mobile,
                'address' => $data['address'],
                'area' => $data['area'] ?? null,
                'city' => $data['city'],
                'state' => $data['state'],
                'pincode' => $data['pincode'],
            ]);

            foreach ($lockedItems as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name_snapshot' => $item->product->name,
                    'sku_snapshot' => $item->variant?->sku ?: $item->product->sku,
                    'variant_snapshot' => $item->variant?->label(),
                    'image_snapshot' => $item->product->displayImage()?->path_thumb,
                    'price' => $item->unitPrice(),
                    'quantity' => $item->quantity,
                    'subtotal' => $item->lineTotal(),
                ]);

                if ($item->variant) {
                    ProductVariant::query()->where('id', $item->variant->id)->decrement('stock', $item->quantity);
                } else {
                    Product::query()->where('id', $item->product_id)->decrement('stock_quantity', $item->quantity);
                }

                Product::query()->where('id', $item->product_id)->increment('sold_count', $item->quantity);
            }

            if ($summary['coupon']) {
                $summary['coupon']->increment('used_count');
                CouponUsage::query()->create([
                    'coupon_id' => $summary['coupon']->id,
                    'order_id' => $order->id,
                    'mobile' => $mobile,
                    'discount_amount' => $summary['discount'],
                ]);
            }

            $order->statusLogs()->create([
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'note' => 'Order placed via Cash on Delivery',
            ]);

            $this->cart->clear($cart);

            return $order->fresh(['items', 'address']);
        });
    }

    public function changeStatus(Order $order, OrderStatus $status, ?string $note = null, ?int $adminId = null): Order
    {
        $from = $order->status;
        if ($from === $status) {
            return $order;
        }

        DB::transaction(function () use ($order, $status, $note, $adminId, $from) {
            $order->status = $status;
            if ($status === OrderStatus::Delivered) {
                $order->delivered_at = now();
                $order->payment_status = 'paid';
            }
            if ($status === OrderStatus::Cancelled) {
                $order->cancelled_at = now();
                $order->cancel_reason = $note;
            }
            $order->save();

            $order->statusLogs()->create([
                'from_status' => $from,
                'to_status' => $status,
                'note' => $note,
                'admin_id' => $adminId,
            ]);

            if ($status->restoresStock() && setting('restore_stock_on_cancel', true) && ! $from->restoresStock()) {
                $this->restoreStock($order);
            }
        });

        $this->notifications->orderStatusChanged($order->fresh(['items', 'address']));

        return $order->fresh();
    }

    public function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                ProductVariant::query()->where('id', $item->product_variant_id)->increment('stock', $item->quantity);
            } elseif ($item->product_id) {
                Product::query()->where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
            }
            if ($item->product_id) {
                Product::query()->where('id', $item->product_id)->decrement('sold_count', $item->quantity);
            }
        }
    }

    private function nextNumber(): string
    {
        $prefix = config('shop.order_prefix');
        $start = (int) config('shop.order_start');
        $last = Order::query()->max('id') ?? 0;

        return $prefix.($start + $last);
    }

    private function assertFraudRules(string $mobile, array &$data): void
    {
        $minutes = config('shop.duplicate_order_minutes');
        $duplicate = Order::query()
            ->where('mobile', $mobile)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Returned->value])
            ->latest()
            ->first();

        if ($duplicate && abs((float) $duplicate->total - (float) ($data['_cart_total'] ?? 0)) < 1) {
            throw new \RuntimeException('A similar order was just placed. Please check Track Order before ordering again.');
        }

        $todayMobile = Order::query()->where('mobile', $mobile)->whereDate('created_at', today())->count();
        $todayIp = Order::query()->where('ip_address', request()->ip())->whereDate('created_at', today())->count();

        if ($todayMobile >= config('shop.max_orders_per_mobile_day')) {
            throw new \RuntimeException('Daily order limit reached for this mobile number.');
        }

        if ($todayIp >= config('shop.max_orders_per_ip_day')) {
            $data['_suspicious'] = true;
            $data['_suspicious_reason'] = 'High order volume from same IP';
        }
    }
}
