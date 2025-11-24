<?php

use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/orders', [OrderController::class, 'store']);

// Event routes - using route model binding
Route::post('/events/{event}/replay', [OrderController::class, 'replayEvent']);
