<?php

namespace Tests\Feature;

use App\Enums\TipoConta;
use App\Models\Categoria;
use App\Models\Conta;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelosTest extends TestCase
{
    use RefreshDatabase;

    public function test_conta_pertence_a_um_usuario_e_a_uma_categoria(): void
    {
        $user = User::factory()->create();
        $categoria = Categoria::factory()->for($user)->create(['nome' => 'Aluguel']);

        $conta = Conta::factory()->for($user)->for($categoria)->create([
            'tipo' => TipoConta::Pagar,
            'valor' => 1500,
            'vencimento' => '2026-11-10',
        ]);

        $conta->refresh();

        $this->assertTrue($conta->user->is($user));
        $this->assertTrue($conta->categoria->is($categoria));
        $this->assertSame(TipoConta::Pagar, $conta->tipo);
        $this->assertSame('1500.00', $conta->valor);
        $this->assertSame('2026-11-10', $conta->vencimento->toDateString());
        $this->assertNull($conta->pago_em);
        $this->assertCount(1, $user->contas);
    }

    public function test_usuario_nao_pode_ter_duas_categorias_com_o_mesmo_nome(): void
    {
        $user = User::factory()->create();
        Categoria::factory()->for($user)->create(['nome' => 'Mercado']);

        $this->expectException(QueryException::class);

        Categoria::factory()->for($user)->create(['nome' => 'Mercado']);
    }

    public function test_excluir_a_categoria_mantem_a_conta_sem_categoria(): void
    {
        $user = User::factory()->create();
        $categoria = Categoria::factory()->for($user)->create();
        $conta = Conta::factory()->for($user)->for($categoria)->create();

        $categoria->delete();

        $this->assertNull($conta->refresh()->categoria_id);
    }
}
