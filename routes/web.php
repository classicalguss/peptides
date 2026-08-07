<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/shop', [StorefrontController::class, 'shop'])->name('shop');
Route::get('/stacks', [StorefrontController::class, 'stacks'])->name('stacks');
Route::get('/stacks/{slug}', [StorefrontController::class, 'stack'])->name('stack');
Route::get('/peptides/{slug}', [StorefrontController::class, 'compound'])->name('compound');
Route::get('/lab-reports', [StorefrontController::class, 'labReports'])->name('lab-reports');

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::patch('/cart/lines/{line}', [CartController::class, 'updateLine'])->name('cart.line.update');
Route::delete('/cart/lines/{line}', [CartController::class, 'removeLine'])->name('cart.line.remove');

Route::post('/checkout/begin', [CheckoutController::class, 'begin'])->name('checkout.begin');
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/confirmation/{reference}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::get('/account/orders/{reference}', [AccountController::class, 'order'])->name('account.order');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::patch('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
});

Route::view('/about', 'storefront.about')->name('about');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
