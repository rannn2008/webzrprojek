<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with('customer', 'order')
            ->orderBy('created_at', 'DESC')
            ->get();

        return view('admin.reviews.index', compact('reviews'));
    }
}
