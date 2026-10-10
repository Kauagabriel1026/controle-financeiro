<?php

namespace Tests\Feature;

use App\Enums\StatusConta;
use App\Models\User;
use App\Support\ContaDeDemonstracao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemonstracaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_um_visitante_com_dados_de_exemplo_e_devolve_um_token(): void
    {
        $this->travelTo('2026-10-10 12:00:00');

        $response = $this->postJson('/api/demonstracao')
            ->assertCreated()
            ->assertJsonPath('usuario.name', 'Visitante')
            ->assertJsonPath('usuario.demonstracao', true)
            ->assertJsonStructure(['usuario' => ['id', 'name', 'email'], 'token']);

        $user = User::findOrFail($response->json('usuario.id'));

        $this->assertTrue($user->demonstracao);
        $this->assertSame(8, $user->categorias()->count());
        // 8 contas mensais em 7 meses, 4 avulsas e a fatura vencida.
        $this->assertSame(61, $user->contas()->count());
    }

    public function test_o_token_do_visitante_acessa_o_painel_com_os_dados_de_exemplo(): void
    {
        $this->travelTo('2026-10-10 12:00:00');

        $token = $this->postJson('/api/demonstracao')->json('token');

        // Outubro: salário de 5.200 e o site de 1.800 a receber; o que venceu
        // antes do dia 10 (salário e faculdade) já consta como pago.
        $this->withToken($token)
            ->getJson('/api/fluxo-caixa?de=2026-10-01&ate=2026-10-31')
            ->assertOk()
            ->assertJsonPath('meses.0.receber', '7000.00')
            ->assertJsonPath('meses.0.recebido', '5200.00')
            ->assertJsonPath('meses.0.pagar', '4232.95')
            ->assertJsonPath('meses.0.pago', '420.00');

        $this->withToken($token)
            ->getJson('/api/contas?status=vencida')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.descricao', 'Fatura do cartão');
    }

    public function test_nada_fica_vencido_alem_da_fatura_em_qualquer_dia_do_mes(): void
    {
        foreach (['2026-10-01', '2026-10-02', '2026-12-31', '2027-02-28', '2027-03-01'] as $dia) {
            $this->travelTo($dia.' 09:00:00');

            $user = ContaDeDemonstracao::criar();

            $this->assertSame(
                ['Fatura do cartão'],
                $user->contas()->comStatus(StatusConta::Vencida)->pluck('descricao')->all(),
                "Dia {$dia}",
            );
        }
    }

    public function test_cada_visitante_recebe_uma_conta_separada(): void
    {
        $primeiro = $this->postJson('/api/demonstracao')->json('usuario.id');
        $segundo = $this->postJson('/api/demonstracao')->json('usuario.id');

        $this->assertNotSame($primeiro, $segundo);
        $this->assertSame(2, User::where('demonstracao', true)->count());
    }

    public function test_apaga_visitantes_antigos_com_tudo_o_que_era_deles_e_preserva_os_demais(): void
    {
        $cadastrado = User::factory()->create(['created_at' => now()->subDays(30)]);
        $cadastrado->createToken('api');

        $antigo = $this->postJson('/api/demonstracao')->json('usuario.id');

        $this->travel(ContaDeDemonstracao::HORAS_DE_VIDA - 1)->hours();
        $recente = $this->postJson('/api/demonstracao')->json('usuario.id');

        $this->assertDatabaseHas('users', ['id' => $antigo]);

        $this->travel(2)->hours();
        $this->postJson('/api/demonstracao')->assertCreated();

        $this->assertDatabaseMissing('users', ['id' => $antigo]);
        $this->assertDatabaseMissing('contas', ['user_id' => $antigo]);
        $this->assertDatabaseMissing('categorias', ['user_id' => $antigo]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $antigo]);

        $this->assertDatabaseHas('users', ['id' => $recente]);
        $this->assertDatabaseHas('users', ['id' => $cadastrado->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $cadastrado->id]);
    }

    public function test_cadastro_comum_nao_e_marcado_como_demonstracao(): void
    {
        $this->postJson('/api/registrar', [
            'name' => 'Maria Teste',
            'email' => 'maria@example.com',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
            'demonstracao' => true,
        ])->assertCreated()->assertJsonPath('usuario.demonstracao', false);

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com', 'demonstracao' => false]);
    }
}
