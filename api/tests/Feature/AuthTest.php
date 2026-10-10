<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_cria_o_usuario_e_devolve_um_token(): void
    {
        $response = $this->postJson('/api/registrar', [
            'name' => 'Maria Teste',
            'email' => 'maria@example.com',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
        ]);

        $response->assertCreated()
            ->assertJsonPath('usuario.email', 'maria@example.com')
            ->assertJsonStructure(['usuario' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
    }

    public function test_registrar_recusa_email_ja_cadastrado(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        $this->postJson('/api/registrar', [
            'name' => 'Outra Maria',
            'email' => 'maria@example.com',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_devolve_um_token_com_credenciais_corretas(): void
    {
        User::factory()->create([
            'email' => 'maria@example.com',
            'password' => 'senha-segura',
        ]);

        $this->postJson('/api/login', [
            'email' => 'maria@example.com',
            'password' => 'senha-segura',
        ])->assertOk()->assertJsonStructure(['usuario', 'token']);
    }

    public function test_login_recusa_senha_errada(): void
    {
        User::factory()->create([
            'email' => 'maria@example.com',
            'password' => 'senha-segura',
        ]);

        $this->postJson('/api/login', [
            'email' => 'maria@example.com',
            'password' => 'senha-errada',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_rotas_protegidas_exigem_login(): void
    {
        $this->getJson('/api/eu')->assertUnauthorized();
        $this->getJson('/api/contas')->assertUnauthorized();
        $this->getJson('/api/categorias')->assertUnauthorized();
        $this->getJson('/api/fluxo-caixa')->assertUnauthorized();
    }

    public function test_eu_devolve_o_usuario_logado(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/eu')->assertOk()->assertJsonPath('email', $user->email);
    }

    public function test_logout_apaga_o_token_usado(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
