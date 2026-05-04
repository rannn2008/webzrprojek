<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'admin_user',
        'action',
        'details'
    ];

    public $timestamps = true;
}
