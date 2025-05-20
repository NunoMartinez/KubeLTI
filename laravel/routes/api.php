<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KubeController;

Route::get('/users/me', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/login', [AuthController::class, "login"]);
Route::get('/kube/api', [KubeController::class, "proxy"]);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('games', GameController::class);
    Route::apiResource('transactions', TransactionController::class);
});
