<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ContaController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    DB::connection()->getPdo();

    return response()->json([
        'status' => 'ok',
        'banco' => DB::connection()->getDriverName(),
    ]);
});

Route::post('/registrar', [AuthController::class, 'registrar']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/eu', [AuthController::class, 'eu']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::post('/categorias', [CategoriaController::class, 'store']);
    Route::put('/categorias/{id}', [CategoriaController::class, 'update'])->whereNumber('id');
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy'])->whereNumber('id');

    Route::get('/contas', [ContaController::class, 'index']);
    Route::post('/contas', [ContaController::class, 'store']);
    Route::get('/contas/{id}', [ContaController::class, 'show'])->whereNumber('id');
    Route::put('/contas/{id}', [ContaController::class, 'update'])->whereNumber('id');
    Route::delete('/contas/{id}', [ContaController::class, 'destroy'])->whereNumber('id');
    Route::post('/contas/{id}/pagar', [ContaController::class, 'pagar'])->whereNumber('id');
    Route::post('/contas/{id}/estornar', [ContaController::class, 'estornar'])->whereNumber('id');
});
