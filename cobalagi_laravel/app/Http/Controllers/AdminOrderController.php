<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('ajax_count')) {
            $count = Order::whereIn('status', ['new', 'baru', ''])->orWhereNull('status')->count();
            return response()->json(['count' => $count]);
        }

        $status = $request->get('status');
        $query = Order::with('customer')->orderBy('created_at', 'DESC');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(10);
        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['customer', 'items'])->findOrFail($id);

        // Mock data for missing fields if any, to match legacy 1:1 view
        $order->whatsapp = $order->customer->whatsapp ?? '6281234567890';
        $order->foto_profil = $order->customer->foto_profil ?? null;

        return response()->json([
            'success' => true,
            'order' => $order,
            'items' => $order->items
        ]);
    }

    public function updateStatus(Request $request)
    {
        $id = $request->input('order_id');
        $action = $request->input('action');
        $order = Order::findOrFail($id);
        $oldStatus = $order->status;

        $statusMap = [
            'accept' => 'process',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'done' => 'done',
            'reject' => 'cancel'
        ];

        if (!isset($statusMap[$action])) {
            return response()->json(['success' => false, 'message' => 'Aksi tidak valid: ' . $action]);
        }

        $newStatus = $statusMap[$action];
        $updateData = ['status' => $newStatus];

        if ($action === 'reject') {
            $updateData['alasan_batal'] = $request->input('alasan', 'Dibatalkan oleh Admin');
        }

        $order->update($updateData);

        // Loyalty points logic for 'done'
        if ($newStatus === 'done' && !in_array($oldStatus, ['done', 'selesai'])) {
            $customer = Customer::find($order->customer_id);
            if ($customer) {
                $points = floor($order->total_harga / 10000);
                $customer->increment('loyalty_points', $points);
            }
        }

        ActivityLog::create([
            'admin_user' => Auth::guard('admin')->user()->username,
            'action' => ucfirst($action) . " Order",
            'details' => "Pesanan #{$order->order_code} diubah statusnya menjadi {$newStatus}"
        ]);

        return response()->json(['success' => true, 'message' => 'Status pesanan berhasil diperbarui']);
    }
}
