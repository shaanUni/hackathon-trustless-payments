<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EscrowPageController;

Route::get('/', fn () => view('welcome'));

// Static page for the create form – keep ABOVE the dynamic route
Route::view('/escrows/new', 'escrows.new')->name('escrows.new');

// Dynamic page to show a single escrow
Route::get('/escrows/{id}', [EscrowPageController::class, 'show'])
    ->whereNumber('id')
    ->name('escrows.show');
