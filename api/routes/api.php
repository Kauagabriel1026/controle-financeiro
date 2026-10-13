<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ContaController;
use App\Http\Controllers\Api\FluxoCaixaController;
use App\Http\Controllers\Api\PerfilController;
use App\Http\Controllers\Api\RedefinicaoDeSenhaController;
use App\Http\Controllers\Api\TotaisPorCategoriaController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    DB::connection()->getPdo();

    return response()->json([
        'status' => 'ok',
        'banco' => DB::connection()->getDriverName(),
    ]);
});

// Limite de 10 tentativas por minuto por IP, contra adivinhação de senha.
// O captcha barra robôs nas rotas que criam contas, entram ou enviam e-mail.
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/registrar', [AuthController::class, 'registrar'])->middleware('captcha');
    Route::post('/login', [AuthController::class, 'login'])->middleware('captcha');
    Route::post('/demonstracao', [AuthController::class, 'demonstracao'])->middleware('captcha');
    Route::post('/esqueci-senha', [RedefinicaoDeSenhaController::class, 'enviarLink'])->middleware('captcha');
    Route::post('/redefinir-senha', [RedefinicaoDeSenhaController::class, 'redefinir']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/eu', [AuthController::class, 'eu']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/perfil', [PerfilController::class, 'update']);
    Route::put('/perfil/senha', [PerfilController::class, 'trocarSenha'])->middleware('throttle:10,1');

    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::post('/categorias', [CategoriaController::class, 'store']);
    Route::post('/categorias/sugeridas', [CategoriaController::class, 'sugeridas']);
    Route::put('/categorias/{id}', [CategoriaController::class, 'update'])->whereNumber('id');
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy'])->whereNumber('id');

    Route::get('/contas', [ContaController::class, 'index']);
    Route::get('/contas/exportar', [ContaController::class, 'exportar']);
    Route::post('/contas', [ContaController::class, 'store']);
    Route::get('/contas/{id}', [ContaController::class, 'show'])->whereNumber('id');
    Route::put('/contas/{id}', [ContaController::class, 'update'])->whereNumber('id');
    Route::delete('/contas/{id}', [ContaController::class, 'destroy'])->whereNumber('id');
    Route::post('/contas/{id}/pagar', [ContaController::class, 'pagar'])->whereNumber('id');
    Route::post('/contas/{id}/estornar', [ContaController::class, 'estornar'])->whereNumber('id');

    Route::get('/fluxo-caixa', FluxoCaixaController::class);
    Route::get('/totais-por-categoria', TotaisPorCategoriaController::class);
});
