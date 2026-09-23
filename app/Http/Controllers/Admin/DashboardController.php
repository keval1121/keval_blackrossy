<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();

        $cards = [
            'today_orders' => Order::query()->whereDate('created_at', $today)->count(),
            'today_revenue' => (float) Order::query()->whereDate('created_at', $today)->whereNotIn('status', [OrderStatus::Cancelled->value])->sum('total'),
            'pending' => Order::query()->where('status', OrderStatus::Pending)->count(),
            'confirmed' => Order::query()->where('status', OrderStatus::Confirmed)->count(),
            'delivered' => Order::query()->where('status', OrderStatus::Delivered)->count(),
            'cancelled' => Order::query()->where('status', OrderStatus::Cancelled)->count(),
            'products' => Product::query()->count(),
            'low_stock' => Product::query()->where('stock_quantity', '<=', config('shop.low_stock_threshold'))->count(),
        ];

        $days = collect(range(6, 0))->map(fn ($ago) => $today->copy()->subDays($ago));
        $orderSeries = $days->map(function ($day) {
            return [
                'label' => $day->format('d M'),
                'orders' => Order::query()->whereDate('created_at', $day)->count(),
                'sales' => (float) Order::query()->whereDate('created_at', $day)->whereNotIn('status', [OrderStatus::Cancelled->value])->sum('total'),
            ];
        });

        $topProducts = DB::table('order_items')
            ->select('product_name_snapshot', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(subtotal) as revenue'))
            ->groupBy('product_name_snapshot')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $topCategories = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->select('categories.name', DB::raw('SUM(order_items.quantity) as qty'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $recent = Order::query()->latest()->limit(8)->get();

        return view('admin.dashboard.index', compact('cards', 'orderSeries', 'topProducts', 'topCategories', 'recent'));
    }
}
