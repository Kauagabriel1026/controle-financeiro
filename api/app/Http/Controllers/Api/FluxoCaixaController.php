<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FluxoDeCaixa;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FluxoCaixaController extends Controller
{
    /**
     * Sem datas informadas, o período é o mês atual mais os cinco seguintes.
     */
    public function __invoke(Request $request, FluxoDeCaixa $fluxo): JsonResponse
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
            : $de->addMonths(5)->endOfMonth()->startOfDay();

        abort_if($ate->lt($de), 422, 'A data final deve ser igual ou posterior à inicial.');
        abort_if($de->diffInMonths($ate) > 24, 422, 'O período máximo é de 24 meses.');

        return response()->json([
            'periodo' => ['de' => $de->toDateString(), 'ate' => $ate->toDateString()],
            ...$fluxo->calcular($request->user(), $de, $ate),
        ]);
    }
}
