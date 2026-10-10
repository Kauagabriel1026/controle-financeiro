<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Conta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContaApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_cria_uma_conta_a_pagar_com_categoria(): void
    {
        $categoria = Categoria::factory()->for($this->user)->create(['nome' => 'Aluguel']);
        $vencimento = now()->addDays(10)->toDateString();

        $response = $this->postJson('/api/contas', [
            'tipo' => 'pagar',
            'descricao' => 'Aluguel de novembro',
            'valor' => 1500.5,
            'vencimento' => $vencimento,
            'categoria_id' => $categoria->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.tipo', 'pagar')
            ->assertJsonPath('data.valor', '1500.50')
            ->assertJsonPath('data.vencimento', $vencimento)
            ->assertJsonPath('data.pago_em', null)
            ->assertJsonPath('data.status', 'pendente')
            ->assertJsonPath('data.categoria.nome', 'Aluguel');

        $this->assertDatabaseHas('contas', [
            'user_id' => $this->user->id,
            'descricao' => 'Aluguel de novembro',
        ]);
    }

    public function test_valida_os_campos_ao_criar(): void
    {
        $this->postJson('/api/contas', [
            'tipo' => 'emprestar',
            'descricao' => '',
            'valor' => 0,
            'vencimento' => 'amanhã',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo', 'descricao', 'valor', 'vencimento']);
    }

    public function test_recusa_categoria_de_outro_usuario(): void
    {
        $alheia = Categoria::factory()->create();

        $this->postJson('/api/contas', [
            'tipo' => 'pagar',
            'descricao' => 'Conta de luz',
            'valor' => 200,
            'vencimento' => now()->addDays(5)->toDateString(),
            'categoria_id' => $alheia->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('categoria_id');
    }

    public function test_lista_apenas_as_contas_do_usuario_por_vencimento(): void
    {
        Conta::factory()->for($this->user)->create([
            'descricao' => 'Segunda',
            'vencimento' => now()->addDays(20),
        ]);
        Conta::factory()->for($this->user)->create([
            'descricao' => 'Primeira',
            'vencimento' => now()->addDays(2),
        ]);
        Conta::factory()->create(['descricao' => 'De outro usuário']);

        $this->getJson('/api/contas')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.descricao', 'Primeira')
            ->assertJsonPath('data.1.descricao', 'Segunda')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_filtra_por_tipo_e_por_status(): void
    {
        Conta::factory()->for($this->user)->create([
            'tipo' => 'pagar',
            'descricao' => 'Vencida',
            'vencimento' => now()->subDays(3),
        ]);
        Conta::factory()->for($this->user)->create([
            'tipo' => 'pagar',
            'descricao' => 'Pendente',
            'vencimento' => now()->addDays(3),
        ]);
        Conta::factory()->for($this->user)->paga()->create([
            'tipo' => 'receber',
            'descricao' => 'Paga',
            'vencimento' => now()->subDays(3),
        ]);

        $this->getJson('/api/contas?status=vencida')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.descricao', 'Vencida')
            ->assertJsonPath('data.0.status', 'vencida');

        $this->getJson('/api/contas?status=pendente')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.descricao', 'Pendente');

        $this->getJson('/api/contas?status=paga')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.descricao', 'Paga');

        $this->getJson('/api/contas?tipo=receber')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.descricao', 'Paga');
    }

    public function test_filtra_por_periodo_de_vencimento(): void
    {
        Conta::factory()->for($this->user)->create(['descricao' => 'Outubro', 'vencimento' => '2026-10-20']);
        Conta::factory()->for($this->user)->create(['descricao' => 'Novembro', 'vencimento' => '2026-11-20']);
        Conta::factory()->for($this->user)->create(['descricao' => 'Dezembro', 'vencimento' => '2026-12-20']);

        $this->getJson('/api/contas?de=2026-11-01&ate=2026-11-30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.descricao', 'Novembro');
    }

    public function test_mostra_e_edita_uma_conta(): void
    {
        $conta = Conta::factory()->for($this->user)->create(['tipo' => 'pagar', 'descricao' => 'Internet']);

        $this->getJson("/api/contas/{$conta->id}")
            ->assertOk()
            ->assertJsonPath('data.descricao', 'Internet');

        $this->putJson("/api/contas/{$conta->id}", [
            'tipo' => 'pagar',
            'descricao' => 'Internet fibra',
            'valor' => 129.9,
            'vencimento' => '2026-11-05',
        ])->assertOk()
            ->assertJsonPath('data.descricao', 'Internet fibra')
            ->assertJsonPath('data.valor', '129.90');
    }

    public function test_exclui_uma_conta(): void
    {
        $conta = Conta::factory()->for($this->user)->create();

        $this->deleteJson("/api/contas/{$conta->id}")->assertNoContent();

        $this->assertDatabaseMissing('contas', ['id' => $conta->id]);
    }

    public function test_da_baixa_no_pagamento_e_estorna(): void
    {
        $conta = Conta::factory()->for($this->user)->create(['vencimento' => now()->addDays(3)]);

        $this->postJson("/api/contas/{$conta->id}/pagar")
            ->assertOk()
            ->assertJsonPath('data.status', 'paga')
            ->assertJsonPath('data.pago_em', today()->toDateString());

        $this->postJson("/api/contas/{$conta->id}/pagar")->assertUnprocessable();

        $this->postJson("/api/contas/{$conta->id}/estornar")
            ->assertOk()
            ->assertJsonPath('data.status', 'pendente')
            ->assertJsonPath('data.pago_em', null);

        $this->postJson("/api/contas/{$conta->id}/estornar")->assertUnprocessable();
    }

    public function test_baixa_aceita_data_informada_mas_nao_data_futura(): void
    {
        $conta = Conta::factory()->for($this->user)->create();
        $ontem = now()->subDay()->toDateString();

        $this->postJson("/api/contas/{$conta->id}/pagar", [
            'pago_em' => now()->addDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('pago_em');

        $this->postJson("/api/contas/{$conta->id}/pagar", ['pago_em' => $ontem])
            ->assertOk()
            ->assertJsonPath('data.pago_em', $ontem);
    }

    public function test_nao_acessa_conta_de_outro_usuario(): void
    {
        $alheia = Conta::factory()->create(['descricao' => 'Alheia']);

        $this->getJson("/api/contas/{$alheia->id}")->assertNotFound();
        $this->deleteJson("/api/contas/{$alheia->id}")->assertNotFound();
        $this->postJson("/api/contas/{$alheia->id}/pagar")->assertNotFound();

        $this->assertDatabaseHas('contas', ['id' => $alheia->id, 'pago_em' => null]);
    }

    public function test_conta_que_vence_hoje_continua_pendente_ate_o_fim_do_dia_no_brasil(): void
    {
        // 22h30 em Brasília já é o dia seguinte em UTC; o status segue o dia do Brasil.
        $this->travelTo(Carbon::parse('2026-10-09 22:30:00', 'America/Sao_Paulo'));

        $conta = Conta::factory()->for($this->user)->create(['vencimento' => '2026-10-09']);

        $this->getJson("/api/contas/{$conta->id}")->assertJsonPath('data.status', 'pendente');
        $this->getJson('/api/contas?status=vencida')->assertJsonCount(0, 'data');

        $this->postJson("/api/contas/{$conta->id}/pagar", ['pago_em' => '2026-10-09'])
            ->assertOk()
            ->assertJsonPath('data.pago_em', '2026-10-09');
    }

    public function test_conta_inexistente_responde_404_sem_expor_detalhes_internos(): void
    {
        $this->getJson('/api/contas/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Registro não encontrado.']);
    }
}
