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

//METRICS/DASHBAORD
Route::get('/kube/metrics', [KubeController::class, 'clusterMetrics']);



Route::get('/kube/nodes', [KubeController::class, 'nodes']);
//PODS
Route::get('/kube/pods', [KubeController::class, 'pods']);
Route::post('kube/pods', [KubeController::class, 'createPod']);
Route::delete('kube/pods/{namespace}/{name}', [KubeController::class, 'deletePod']);



Route::get('/kube/services', [KubeController::class, 'services']);

//NAMESPACES
Route::get('/kube/namespaces', [KubeController::class, 'namespaces']);
Route::post('/kube/namespaces', [KubeController::class, 'createNamespace']);
Route::delete('/kube/namespaces/{name}', [KubeController::class, 'deleteNamespace']);


//DEPLOYMENTS
Route::get('/kube/deployments', [KubeController::class, 'deployments']);
Route::post('/kube/deployments', [KubeController::class, 'createDeployment']);
Route::delete('/kube/deployments/{namespace}/{name}', [KubeController::class, 'deleteDeployment']);


Route::get('/kube/ingresses', [KubeController::class, 'ingresses']);







Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('games', GameController::class);
    Route::apiResource('transactions', TransactionController::class);


});
