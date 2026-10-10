<?php

namespace App\Support;

use App\Enums\TipoConta;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class ContaDeDemonstracao
{
    /** Por quantas horas uma conta de visitante fica guardada. */
    public const HORAS_DE_VIDA = 24;

    /**
     * Contas que se repetem todo mês: tipo, descrição, categoria, valor e dia do vencimento.
     */
    private const MENSAIS = [
        [TipoConta::Receber, 'Salário', 'Salário', 5200.00, 5],
        [TipoConta::Pagar, 'Mensalidade da faculdade', 'Educação', 420.00, 8],
        [TipoConta::Pagar, 'Aluguel', 'Moradia', 1500.00, 10],
        [TipoConta::Pagar, 'Internet fibra', 'Moradia', 129.90, 12],
        [TipoConta::Pagar, 'Energia', 'Moradia', 212.35, 15],
        [TipoConta::Pagar, 'Academia', 'Saúde', 99.90, 18],
        [TipoConta::Pagar, 'Mercado do mês', 'Alimentação', 890.00, 20],
        [TipoConta::Pagar, 'Passe de ônibus', 'Transporte', 180.00, 22],
    ];

    /**
     * Contas avulsas: tipo, descrição, categoria, valor, mês (0 = atual) e dia.
     */
    private const AVULSAS = [
        [TipoConta::Receber, 'Site para a barbearia', 'Outros', 1800.00, 0, 25],
        [TipoConta::Pagar, 'Show no fim de semana', 'Lazer', 160.00, 0, 24],
        [TipoConta::Receber, 'Décimo terceiro', 'Salário', 2600.00, 2, 20],
        [TipoConta::Pagar, 'Material do semestre', 'Educação', 350.00, 3, 6],
    ];

    /**
     * Cria um usuário visitante com categorias e contas de exemplo: o mês
     * passado, o atual e os cinco seguintes. O que já venceu entra como pago,
     * menos uma conta, para o painel mostrar como aparece uma conta vencida.
     */
    public static function criar(): User
    {
        return DB::transaction(function () {
            $user = new User([
                'name' => 'Visitante',
                'email' => 'visitante-'.Str::uuid().'@demo.invalid',
                'password' => Str::random(40),
            ]);
            $user->demonstracao = true;
            $user->save();

            CategoriasSugeridas::adicionarPara($user);

            $categorias = $user->categorias()->pluck('id', 'nome');
            $hoje = CarbonImmutable::today();
            $contas = [];

            foreach (range(-1, 5) as $mes) {
                foreach (self::MENSAIS as [$tipo, $descricao, $categoria, $valor, $dia]) {
                    $contas[] = [$tipo, $descricao, $categoria, $valor, $mes, $dia];
                }
            }

            foreach ([...$contas, ...self::AVULSAS] as [$tipo, $descricao, $categoria, $valor, $mes, $dia]) {
                $vencimento = $hoje->startOfMonth()->addMonthsNoOverflow($mes)->day($dia);

                $user->contas()->create([
                    'tipo' => $tipo,
                    'descricao' => $descricao,
                    'categoria_id' => $categorias[$categoria],
                    'valor' => $valor,
                    'vencimento' => $vencimento,
                    'pago_em' => $vencimento->lessThan($hoje) ? $vencimento : null,
                ]);
            }

            $user->contas()->create([
                'tipo' => TipoConta::Pagar,
                'descricao' => 'Fatura do cartão',
                'categoria_id' => $categorias['Outros'],
                'valor' => 640.80,
                'vencimento' => $hoje->subDays(2),
                'pago_em' => null,
            ]);

            return $user;
        });
    }

    /**
     * Apaga as contas de visitante que passaram do prazo. Categorias e contas
     * vão junto pelo banco; os tokens são apagados aqui.
     */
    public static function apagarAntigas(): int
    {
        $antigas = User::query()
            ->where('demonstracao', true)
            ->where('created_at', '<', now()->subHours(self::HORAS_DE_VIDA));

        $ids = $antigas->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        PersonalAccessToken::query()
            ->where('tokenable_type', (new User)->getMorphClass())
            ->whereIn('tokenable_id', $ids)
            ->delete();

        return User::query()->whereIn('id', $ids)->delete();
    }
}
