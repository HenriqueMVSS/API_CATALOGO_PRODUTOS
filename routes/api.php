<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::post('products/{id}/image', [ProductController::class, 'uploadImage']);
    Route::get('search/products', [SearchController::class, 'products']);
});
