<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Protected Admin Routes
    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Orders Management
        Route::get('/orders', [\App\Http\Controllers\AdminOrderController::class, 'index'])->name('admin.orders.index');
        Route::get('/orders/{id}', [\App\Http\Controllers\AdminOrderController::class, 'show'])->name('admin.orders.show');
        Route::post('/orders/update-status', [\App\Http\Controllers\AdminOrderController::class, 'updateStatus'])->name('admin.orders.updateStatus');

        // Product Management
        Route::resource('products', \App\Http\Controllers\AdminProductController::class)->names('admin.products');

        // Activity Logs
        Route::get('/logs', [\App\Http\Controllers\AdminLogController::class, 'index'])->name('admin.logs.index');

        // Chat Management
        Route::get('/chats', [\App\Http\Controllers\AdminChatController::class, 'index'])->name('admin.chats.index');
        Route::get('/chats/{customer_id}', [\App\Http\Controllers\AdminChatController::class, 'show'])->name('admin.chats.show');
        Route::post('/chats/send', [\App\Http\Controllers\AdminChatController::class, 'send'])->name('admin.chats.send');

        // Review Management
        Route::get('/reviews', [\App\Http\Controllers\AdminReviewController::class, 'index'])->name('admin.reviews.index');

        // Account Profile
        Route::get('/profile', [\App\Http\Controllers\AdminProfileController::class, 'show'])->name('admin.profile');
        Route::post('/profile', [\App\Http\Controllers\AdminProfileController::class, 'update'])->name('admin.profile.update');
    });
});
