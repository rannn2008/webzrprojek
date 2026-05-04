<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'chats';

    protected $fillable = [
        'order_id',
        'sender_type',
        'sender_id',
        'receiver_id',
        'message',
        'is_read'
    ];

    public $timestamps = false; // Using legacy created_at timestamp

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'sender_type', 'customer' ? 'sender_id' : 'receiver_id');
    }
}
