<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminChatController extends Controller
{
    public function index()
    {
        // Get unique customers who have chat history
        $chatUsers = DB::table('chats')
            ->select('sender_id', 'sender_type', 'receiver_id', DB::raw('MAX(created_at) as last_chat'))
            ->groupBy('sender_id', 'sender_type', 'receiver_id')
            ->get();

        $customerIds = [];
        foreach ($chatUsers as $cu) {
            if ($cu->sender_type == 'customer')
                $customerIds[] = $cu->sender_id;
            else
                $customerIds[] = $cu->receiver_id;
        }

        $customers = Customer::whereIn('id', array_unique($customerIds))
            ->withCount([
                'chats' => function ($q) {
                    $q->where('is_read', 0)->where('sender_type', 'customer');
                }
            ])
            ->get();

        return view('admin.chats.index', compact('customers'));
    }

    public function show($customer_id)
    {
        $customer = Customer::findOrFail($customer_id);

        $chats = Chat::where(function ($q) use ($customer_id) {
            $q->where('sender_type', 'customer')->where('sender_id', $customer_id);
        })
            ->orWhere(function ($q) use ($customer_id) {
                $q->where('sender_type', 'admin')->where('receiver_id', $customer_id);
            })
            ->orderBy('created_at', 'ASC')
            ->get();

        // Mark as read
        Chat::where('sender_type', 'customer')
            ->where('sender_id', $customer_id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return view('admin.chats.show', compact('customer', 'chats'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'message' => 'required|string'
        ]);

        Chat::create([
            'sender_type' => 'admin',
            'sender_id' => 0, // System Admin ID as per legacy
            'receiver_id' => $request->customer_id,
            'message' => $request->message,
            'is_read' => 0
        ]);

        return response()->json(['success' => true]);
    }
}
