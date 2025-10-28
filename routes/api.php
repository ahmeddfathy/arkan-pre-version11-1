<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes that require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
