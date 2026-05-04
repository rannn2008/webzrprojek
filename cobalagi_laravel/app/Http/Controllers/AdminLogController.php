<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::orderBy('created_at', 'DESC')->paginate(20);
        return view('admin.logs.index', compact('logs'));
    }
}
