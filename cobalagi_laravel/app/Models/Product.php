<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'nama',
        'harga',
        'deskripsi',
        'kategori',
        'gambar',
        'tersedia',
        'is_deleted'
    ];

    public $timestamps = false; // Using custom created_at column
}
