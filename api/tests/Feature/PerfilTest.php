<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.com', 'password' => 'senha-antiga']);
        $this->token = $this->user->createToken('api')->plainTextToken;
    }

    public function test_eu_traz_o_perfil_com_o_tema_padrao(): void
    {
        $this->withToken($this->token)->getJson('/api/eu')
            ->assertOk()
            ->assertJson([
                'name' => 'Maria',
                'renda_mensal' => null,
                'dia_pagamento' => null,
                'objetivo' => null,
                'meta_economia' => null,
                'tema' => 'sistema',
            ]);
    }

    public function test_salva_o_perfil_financeiro_sem_mexer_no_resto(): void
    {
        $this->withToken($this->token)->putJson('/api/perfil', [
            'renda_mensal' => 5200.5,
            'dia_pagamento' => 5,
            'objetivo' => 'montar_reserva',
            'meta_economia' => 800,
        ])->assertOk()
            ->assertJsonPath('name', 'Maria')
            ->assertJsonPath('renda_mensal', '5200.50')
            ->assertJsonPath('dia_pagamento', 5)
            ->assertJsonPath('objetivo', 'montar_reserva')
            ->assertJsonPath('meta_economia', '800.00');

        // Campos opcionais podem ser apagados.
        $this->withToken($this->token)->putJson('/api/perfil', ['renda_mensal' => null])
            ->assertOk()
            ->assertJsonPath('renda_mensal', null)
            ->assertJsonPath('objetivo', 'montar_reserva');
    }

    public function test_altera_nome_email_e_tema(): void
    {
        $this->withToken($this->token)->putJson('/api/perfil', [
            'name' => 'Maria Souza',
            'email' => 'maria.souza@example.com',
            'tema' => 'escuro',
        ])->assertOk()
            ->assertJsonPath('name', 'Maria Souza')
            ->assertJsonPath('email', 'maria.souza@example.com')
            ->assertJsonPath('tema', 'escuro');

        $this->withToken($this->token)->getJson('/api/eu')->assertJsonPath('tema', 'escuro');
    }

    public function test_recusa_dados_invalidos(): void
    {
        User::factory()->create(['email' => 'outra@example.com']);

        $this->withToken($this->token)->putJson('/api/perfil', [
            'name' => '',
            'email' => 'outra@example.com',
            'renda_mensal' => -10,
            'dia_pagamento' => 32,
            'objetivo' => 'ficar_rico',
            'tema' => 'roxo',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'renda_mensal', 'dia_pagamento', 'objetivo', 'tema']);
    }

    public function test_manter_o_proprio_email_nao_conta_como_repetido(): void
    {
        $this->withToken($this->token)->putJson('/api/perfil', ['email' => 'maria@example.com'])->assertOk();
    }

    public function test_perfil_exige_login(): void
    {
        $this->putJson('/api/perfil', ['name' => 'X'])->assertUnauthorized();
    }

    public function test_troca_a_senha_e_encerra_so_as_outras_sessoes(): void
    {
        $outroAparelho = $this->user->createToken('api')->plainTextToken;

        $this->withToken($this->token)->putJson('/api/perfil/senha', [
            'senha_atual' => 'senha-antiga',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertOk();

        $this->assertSame(1, $this->user->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->getJson('/api/eu')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($outroAparelho)->getJson('/api/eu')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => 'maria@example.com', 'password' => 'senha-nova-123'])->assertOk();
    }

    public function test_recusa_troca_com_senha_atual_errada(): void
    {
        $this->withToken($this->token)->putJson('/api/perfil/senha', [
            'senha_atual' => 'errada',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.senha_atual.0', 'A senha atual não confere.');
    }
}
