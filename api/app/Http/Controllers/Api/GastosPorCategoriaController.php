<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GastosPorCategoria;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GastosPorCategoriaController extends Controller
{
    /**
     * Sem datas informadas, o período é o mês atual.
     */
    public function __invoke(Request $request, GastosPorCategoria $gastos): JsonResponse
    {
        $dados = $request->validate([
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date'],
        ]);

        $de = isset($dados['de'])
            ? CarbonImmutable::parse($dados['de'])->startOfDay()
            : today()->toImmutable()->startOfMonth();

        $ate = isset($dados['ate'])
            ? CarbonImmutable::parse($dados['ate'])->startOfDay()
            : $de->endOfMonth()->startOfDay();

        abort_if($ate->lt($de), 422, 'A data final deve ser igual ou posterior à inicial.');

        return response()->json([
            'periodo' => ['de' => $de->toDateString(), 'ate' => $ate->toDateString()],
            ...$gastos->calcular($request->user(), $de, $ate),
        ]);
    }
}
