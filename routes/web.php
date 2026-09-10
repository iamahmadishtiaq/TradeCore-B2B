<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\VendorDashboardController;
use App\Http\Middleware\EnsureUserIsVendor;
use App\Http\Controllers\Api\PricingApiController;
use App\Http\Controllers\VendorPayoutController;
use App\Http\Controllers\Admin\AdminPayoutController;
use App\Http\Middleware\EnsureUserIsAdmin;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/orders/{id}', [CheckoutController::class, 'show'])->name('orders.show');
    Route::get('/orders/{id}/invoice', [CheckoutController::class, 'downloadInvoice'])->name('orders.invoice.download');
});

Route::middleware(['auth', EnsureUserIsVendor::class])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function () {
        Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('dashboard');
        Route::post('/batches/{batchId}/restock', [VendorDashboardController::class, 'restockBatch'])->name('batches.restock');
        Route::patch('/shipments/{shipmentId}', [VendorDashboardController::class, 'updateShipment'])->name('shipments.update');
        Route::post('/inventory/import', [VendorDashboardController::class, 'uploadInventoryCsv'])->name('inventory.import');
        Route::get('/inventory/batches/{batchId}/progress', [VendorDashboardController::class, 'checkBatchProgress'])->name('inventory.batch.progress');
        Route::get('/payouts', [VendorPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [VendorPayoutController::class, 'store'])->name('payouts.store');
    });

Route::middleware(['auth', EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/{id}/approve', [AdminPayoutController::class, 'approve'])->name('payouts.approve');
        Route::post('/payouts/{id}/reject', [AdminPayoutController::class, 'reject'])->name('payouts.reject');
    });

Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{variantId}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{variantId}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/api/pricing/{variantId}/quote', [PricingApiController::class, 'quote'])->name('api.pricing.quote');

require __DIR__ . '/auth.php';
