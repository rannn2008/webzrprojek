<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'points',
        'foto_profil'
    ];

    public $timestamps = true;

    public function chats()
    {
        return $this->hasMany(Chat::class, 'sender_id')->where('sender_type', 'customer');
    }
}
