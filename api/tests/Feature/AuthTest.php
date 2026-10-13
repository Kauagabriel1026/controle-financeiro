<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CategoriasSugeridas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_usuario_novo_ja_comeca_com_as_categorias_sugeridas(): void
    {
        $this->postJson('/api/registrar', [
            'name' => 'Maria Teste',
            'email' => 'maria@example.com',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
        ])->assertCreated();

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            CategoriasSugeridas::NOMES,
            $user->categorias()->pluck('nome')->all(),
        );
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

    public function test_login_bloqueia_apos_dez_tentativas_em_um_minuto(): void
    {
        for ($tentativa = 1; $tentativa <= 10; $tentativa++) {
            $this->postJson('/api/login', [
                'email' => 'ninguem@example.com',
                'password' => 'senha-errada',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/login', [
            'email' => 'ninguem@example.com',
            'password' => 'senha-errada',
        ])->assertTooManyRequests();
    }

    public function test_login_com_email_inexistente_responde_como_senha_errada(): void
    {
        $this->postJson('/api/login', [
            'email' => 'ninguem@example.com',
            'password' => 'qualquer-senha',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'E-mail ou senha inválidos.');
    }

    public function test_senha_guardada_com_outro_custo_e_refeita_no_login(): void
    {
        $user = User::factory()->create(['email' => 'antiga@example.com']);

        // Gravado direto no banco: pelo model, o Laravel recusa um hash com
        // custo diferente do configurado.
        DB::table('users')->where('id', $user->id)->update([
            'password' => password_hash('senha-segura', PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $this->postJson('/api/login', [
            'email' => 'antiga@example.com',
            'password' => 'senha-segura',
        ])->assertOk();

        $novo = $user->fresh()->password;

        $this->assertSame((int) config('hashing.bcrypt.rounds'), password_get_info($novo)['options']['cost']);
        $this->assertTrue(password_verify('senha-segura', $novo));

        // Continua entrando com a mesma senha depois da troca do hash.
        $this->postJson('/api/login', [
            'email' => 'antiga@example.com',
            'password' => 'senha-segura',
        ])->assertOk();
    }
}
