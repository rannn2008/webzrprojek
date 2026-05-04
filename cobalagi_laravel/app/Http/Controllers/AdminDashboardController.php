<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'new_orders' => Order::whereIn('status', ['new', 'baru', ''])->orWhereNull('status')->count(),
            'total_products' => Product::where('is_deleted', 0)->count(),
            'total_customers' => Order::distinct('nama_customer')->count(),
            'total_done' => Order::whereIn('status', ['done', 'selesai'])->count(),
        ];

        // Revenue last 7 days for Chart.js
        $revenue_7days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $label = now()->subDays($i)->format('d M');
            $total = Order::whereDate('created_at', $date)
                ->whereIn('status', ['done', 'selesai'])
                ->sum('total_harga');

            $revenue_7days[] = [
                'label' => $label,
                'total' => $total ?? 0
            ];
        }

        // Top 5 Products
        $top_products = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['done', 'selesai'])
            ->select('order_items.nama_product', DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('order_items.nama_product')
            ->orderBy('total_qty', 'DESC')
            ->limit(5)
            ->get();

        // Ratings
        $avg_rating = Review::avg('rating') ?? 0;
        $total_reviews = Review::count();

        // Status Distribution for Bar Chart
        $status_dist = [
            'new' => Order::whereIn('status', ['new', 'baru', ''])->orWhereNull('status')->count(),
            'process' => Order::where('status', 'process')->count(),
            'done' => Order::whereIn('status', ['done', 'selesai'])->count(),
            'cancel' => Order::where('status', 'cancel')->count(),
        ];

        return view('admin.dashboard', compact('stats', 'revenue_7days', 'top_products', 'avg_rating', 'total_reviews', 'status_dist'));
    }
}
