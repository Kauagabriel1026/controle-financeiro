<?php

namespace Tests\Feature;

use App\Models\Conta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FluxoCaixaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    private function conta(string $tipo, float $valor, string $vencimento, bool $paga = false): void
    {
        Conta::factory()->for($this->user)->create([
            'tipo' => $tipo,
            'valor' => $valor,
            'vencimento' => $vencimento,
            'pago_em' => $paga ? $vencimento : null,
        ]);
    }

    public function test_soma_por_mes_com_saldo_e_saldo_acumulado(): void
    {
        $this->conta('receber', 3000, '2026-10-05', paga: true);
        $this->conta('pagar', 1500.10, '2026-10-10', paga: true);
        $this->conta('pagar', 200.20, '2026-10-20');
        $this->conta('pagar', 4000, '2026-11-10');
        $this->conta('receber', 3000, '2026-12-05');
        $this->conta('receber', 999, '2027-01-05');

        $response = $this->getJson('/api/fluxo-caixa?de=2026-10-01&ate=2026-12-31');

        $response->assertOk()
            ->assertJsonPath('periodo', ['de' => '2026-10-01', 'ate' => '2026-12-31'])
            ->assertJsonCount(3, 'meses')
            ->assertJsonPath('meses.0', [
                'mes' => '2026-10',
                'receber' => '3000.00',
                'pagar' => '1700.30',
                'saldo' => '1299.70',
                'saldo_acumulado' => '1299.70',
                'recebido' => '3000.00',
                'pago' => '1500.10',
            ])
            ->assertJsonPath('meses.1.saldo', '-4000.00')
            ->assertJsonPath('meses.1.saldo_acumulado', '-2700.30')
            ->assertJsonPath('meses.2.saldo_acumulado', '299.70')
            ->assertJsonPath('total', [
                'receber' => '6000.00',
                'pagar' => '5700.30',
                'saldo' => '299.70',
                'recebido' => '3000.00',
                'pago' => '1500.10',
            ]);
    }

    public function test_mes_sem_contas_aparece_zerado(): void
    {
        $this->conta('pagar', 100, '2026-10-10');
        $this->conta('pagar', 100, '2026-12-10');

        $this->getJson('/api/fluxo-caixa?de=2026-10-01&ate=2026-12-31')
            ->assertOk()
            ->assertJsonPath('meses.1.mes', '2026-11')
            ->assertJsonPath('meses.1.pagar', '0.00')
            ->assertJsonPath('meses.1.saldo_acumulado', '-100.00');
    }

    public function test_nao_soma_contas_de_outro_usuario(): void
    {
        Conta::factory()->create(['tipo' => 'receber', 'valor' => 5000, 'vencimento' => '2026-10-10']);

        $this->getJson('/api/fluxo-caixa?de=2026-10-01&ate=2026-10-31')
            ->assertOk()
            ->assertJsonPath('total.receber', '0.00');
    }

    public function test_sem_datas_usa_seis_meses_a_partir_do_mes_atual(): void
    {
        $this->travelTo('2026-10-09');

        $this->getJson('/api/fluxo-caixa')
            ->assertOk()
            ->assertJsonPath('periodo', ['de' => '2026-10-01', 'ate' => '2027-03-31'])
            ->assertJsonCount(6, 'meses');
    }

    public function test_recusa_periodo_invertido_ou_longo_demais(): void
    {
        $this->getJson('/api/fluxo-caixa?de=2026-12-01&ate=2026-10-01')->assertUnprocessable();
        $this->getJson('/api/fluxo-caixa?de=2020-01-01&ate=2026-12-31')->assertUnprocessable();
    }
}
