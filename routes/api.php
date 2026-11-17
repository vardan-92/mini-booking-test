<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\SlotController;
use Illuminate\Support\Facades\Route;

Route::get('/slots', SlotController::class);
Route::post('/bookings', [BookingController::class, 'store']);
Route::delete('/bookings/{id}', [BookingController::class, 'destroy'])->whereNumber('id');
