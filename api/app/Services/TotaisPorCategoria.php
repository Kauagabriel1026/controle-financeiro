<?php

namespace App\Services;

use App\Enums\TipoConta;
use App\Models\User;
use Carbon\CarbonImmutable;

class TotaisPorCategoria
{
    /**
     * Soma as contas de um tipo (a pagar ou a receber) por categoria, da maior
     * para a menor, dentro do período.
     * Como no fluxo de caixa, a soma é feita em centavos.
     *
     * @return array{total: string, categorias: list<array{categoria: string, total: string, percentual: float}>}
     */
    public function calcular(User $user, TipoConta $tipo, CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $contas = $user->contas()
            ->with('categoria')
            ->where('tipo', $tipo)
            ->whereDate('vencimento', '>=', $de->toDateString())
            ->whereDate('vencimento', '<=', $ate->toDateString())
            ->get();

        $porCategoria = [];

        foreach ($contas as $conta) {
            $nome = $conta->categoria?->nome ?? 'Sem categoria';
            $porCategoria[$nome] = ($porCategoria[$nome] ?? 0) + (int) round(((float) $conta->valor) * 100);
        }

        arsort($porCategoria);
        $total = array_sum($porCategoria);

        $categorias = [];

        foreach ($porCategoria as $nome => $centavos) {
            $categorias[] = [
                'categoria' => $nome,
                'total' => number_format($centavos / 100, 2, '.', ''),
                'percentual' => round($centavos / $total * 100, 1),
            ];
        }

        return [
            'total' => number_format($total / 100, 2, '.', ''),
            'categorias' => $categorias,
        ];
    }
}
