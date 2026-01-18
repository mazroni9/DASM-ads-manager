<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Include Ads Platform routes
require __DIR__.'/api-ads.php';

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
