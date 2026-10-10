<?php

namespace App\Services;

use App\Enums\TipoConta;
use App\Models\User;
use Carbon\CarbonImmutable;

class FluxoDeCaixa
{
    /**
     * Soma as contas do usuário por mês de vencimento, dentro do período.
     *
     * Os valores são somados em centavos (inteiros) para não acumular erro de
     * arredondamento, e só viram texto com duas casas na saída.
     *
     * @return array{meses: list<array<string, string>>, total: array<string, string>}
     */
    public function calcular(User $user, CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $contas = $user->contas()
            ->whereDate('vencimento', '>=', $de->toDateString())
            ->whereDate('vencimento', '<=', $ate->toDateString())
            ->get(['tipo', 'valor', 'vencimento', 'pago_em']);

        // Todo mês do período aparece no resultado, mesmo sem nenhuma conta.
        $meses = [];
        for ($mes = $de->startOfMonth(); $mes->lte($ate); $mes = $mes->addMonth()) {
            $meses[$mes->format('Y-m')] = ['receber' => 0, 'pagar' => 0, 'recebido' => 0, 'pago' => 0];
        }

        foreach ($contas as $conta) {
            $mes = $conta->vencimento->format('Y-m');
            $centavos = (int) round(((float) $conta->valor) * 100);
            $previsto = $conta->tipo === TipoConta::Receber ? 'receber' : 'pagar';
            $realizado = $conta->tipo === TipoConta::Receber ? 'recebido' : 'pago';

            $meses[$mes][$previsto] += $centavos;

            if ($conta->pago_em !== null) {
                $meses[$mes][$realizado] += $centavos;
            }
        }

        $total = ['receber' => 0, 'pagar' => 0, 'recebido' => 0, 'pago' => 0];
        $acumulado = 0;
        $linhas = [];

        foreach ($meses as $mes => $valores) {
            $saldo = $valores['receber'] - $valores['pagar'];
            $acumulado += $saldo;

            foreach ($valores as $chave => $centavos) {
                $total[$chave] += $centavos;
            }

            $linhas[] = [
                'mes' => $mes,
                'receber' => $this->reais($valores['receber']),
                'pagar' => $this->reais($valores['pagar']),
                'saldo' => $this->reais($saldo),
                'saldo_acumulado' => $this->reais($acumulado),
                'recebido' => $this->reais($valores['recebido']),
                'pago' => $this->reais($valores['pago']),
            ];
        }

        return [
            'meses' => $linhas,
            'total' => [
                'receber' => $this->reais($total['receber']),
                'pagar' => $this->reais($total['pagar']),
                'saldo' => $this->reais($total['receber'] - $total['pagar']),
                'recebido' => $this->reais($total['recebido']),
                'pago' => $this->reais($total['pago']),
            ],
        ];
    }

    private function reais(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
