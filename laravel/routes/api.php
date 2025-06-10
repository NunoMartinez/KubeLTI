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
Route::post('/auth/logout', [AuthController::class, "logout"])->middleware('auth:sanctum');
Route::post('/auth/refreshtoken', [AuthController::class, "refreshToken"])->middleware('auth:sanctum');

// Profile routes - protected by auth middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/users/profile', 'App\Http\Controllers\ProfileController@updateProfile');
    Route::put('/users/password', 'App\Http\Controllers\ProfileController@updatePassword');
    Route::post('/users/photo', 'App\Http\Controllers\ProfileController@updatePhoto');
    
    // User management routes (admin only)
    Route::post('/users', 'App\Http\Controllers\UserController@store');
});

Route::get('/kube/api', [KubeController::class, "proxy"]);

//METRICS/DASHBAORD
Route::get('/kube/metrics', [KubeController::class, 'clusterMetrics']);


//NODES
Route::get('/kube/nodes', [KubeController::class, 'nodes']);

//PODS
Route::get('/kube/pods', [KubeController::class, 'pods']);
Route::post('kube/pods', [KubeController::class, 'createPod']);
Route::delete('kube/pods/{namespace}/{name}', [KubeController::class, 'deletePod']);
Route::get('/kube/pods/{namespace}/{name}', [KubeController::class, 'getPod']);
Route::put('/kube/pods/{namespace}/{name}', [KubeController::class, 'updatePod']);

//SERVICES
Route::get('/kube/services', [KubeController::class, 'services']);
Route::post('/kube/services', [KubeController::class, 'createService']);
Route::delete('/kube/services/{namespace}/{name}', [KubeController::class, 'deleteService']);
Route::get('/kube/services/{namespace}/{name}', [KubeController::class, 'getService']);
Route::put('/kube/services/{namespace}/{name}', [KubeController::class, 'updateService']);

//NAMESPACES
Route::get('/kube/namespaces', [KubeController::class, 'namespaces']);
Route::post('/kube/namespaces', [KubeController::class, 'createNamespace']);
Route::delete('/kube/namespaces/{name}', [KubeController::class, 'deleteNamespace']);
Route::get('/kube/namespaces/{name}', [KubeController::class, 'getNamespace']);
Route::put('/kube/namespaces/{name}', [KubeController::class, 'updateNamespace']);


//DEPLOYMENTS
Route::get('/kube/deployments', [KubeController::class, 'deployments']);
Route::post('/kube/deployments', [KubeController::class, 'createDeployment']);
Route::delete('/kube/deployments/{namespace}/{name}', [KubeController::class, 'deleteDeployment']);
Route::get('/kube/deployments/{namespace}/{name}', [KubeController::class, 'getDeployment']);
Route::put('/kube/deployments/{namespace}/{name}', [KubeController::class, 'updateDeployment']);

//INGRESS
Route::get('/kube/ingresses', [KubeController::class, 'ingresses']);
Route::post('/kube/ingresses', [KubeController::class, 'createIngress']);
Route::delete('/kube/ingresses/{namespace}/{name}', [KubeController::class, 'deleteIngress']);
Route::get('/kube/ingresses/{namespace}/{name}', [KubeController::class, 'getIngress']);
Route::put('/kube/ingresses/{namespace}/{name}', [KubeController::class, 'updateIngress']);







Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('games', GameController::class);
    Route::apiResource('transactions', TransactionController::class);


});
