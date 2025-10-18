<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EscrowController;

// JSON API used by your pages
Route::post('/escrows', [EscrowController::class, 'create'])->name('api.escrows.store');
Route::get('/escrows/{id}', [EscrowController::class, 'show'])->name('api.escrows.show');
Route::post('/escrows/{id}/release', [EscrowController::class, 'release'])->name('api.escrows.release');
Route::post('/escrows/{id}/refund',  [EscrowController::class, 'refund'])->name('api.escrows.refund');
Route::post('/escrows/{id}/fund-demo', [EscrowController::class, 'fundDemo'])->name('api.escrows.fundDemo');

// (Optional sanity route)
Route::get('/ping', fn () => response()->json(['ok' => true]));
