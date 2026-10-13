<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaptchaTest extends TestCase
{
    use RefreshDatabase;

    private const CADASTRO = [
        'name' => 'Maria',
        'email' => 'maria@example.com',
        'password' => 'senha-segura',
        'password_confirmation' => 'senha-segura',
    ];

    private function ligarCaptcha(bool $aprovado = true): void
    {
        config(['services.turnstile.chave_secreta' => 'chave-de-teste']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => $aprovado])]);
    }

    public function test_sem_chave_configurada_o_captcha_nao_e_exigido(): void
    {
        Http::fake();

        $this->postJson('/api/registrar', self::CADASTRO)->assertCreated();

        Http::assertNothingSent();
    }

    public function test_com_captcha_ligado_pedido_sem_token_e_recusado(): void
    {
        $this->ligarCaptcha();

        $this->postJson('/api/registrar', self::CADASTRO)
            ->assertUnprocessable()
            ->assertJsonPath('errors.captcha.0', 'Confirme que você não é um robô.');

        $this->assertDatabaseCount('users', 0);
        Http::assertNothingSent();
    }

    public function test_token_aprovado_pela_cloudflare_libera_o_cadastro(): void
    {
        $this->ligarCaptcha();

        $this->postJson('/api/registrar', [...self::CADASTRO, 'captcha' => 'token-valido'])->assertCreated();

        Http::assertSent(fn (Request $pedido) => $pedido->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $pedido['secret'] === 'chave-de-teste'
            && $pedido['response'] === 'token-valido');
    }

    public function test_token_recusado_pela_cloudflare_bloqueia(): void
    {
        $this->ligarCaptcha(aprovado: false);

        $this->postJson('/api/registrar', [...self::CADASTRO, 'captcha' => 'token-falso'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('captcha');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_falha_ao_falar_com_a_cloudflare_bloqueia_com_mensagem_clara(): void
    {
        config(['services.turnstile.chave_secreta' => 'chave-de-teste']);
        Http::fake(['challenges.cloudflare.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fora do ar')]);

        $this->postJson('/api/registrar', [...self::CADASTRO, 'captcha' => 'token'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.captcha.0', 'Não foi possível confirmar a verificação de segurança. Tente de novo.');
    }

    public function test_login_demonstracao_e_esqueci_senha_tambem_exigem_captcha(): void
    {
        $this->ligarCaptcha();
        User::factory()->create(['email' => 'maria@example.com', 'password' => 'senha-segura']);

        $this->postJson('/api/login', ['email' => 'maria@example.com', 'password' => 'senha-segura'])->assertJsonValidationErrors('captcha');
        $this->postJson('/api/demonstracao')->assertJsonValidationErrors('captcha');
        $this->postJson('/api/esqueci-senha', ['email' => 'maria@example.com'])->assertJsonValidationErrors('captcha');

        $this->postJson('/api/login', ['email' => 'maria@example.com', 'password' => 'senha-segura', 'captcha' => 'ok'])->assertOk();
    }

    public function test_rotas_de_quem_ja_esta_logado_nao_pedem_captcha(): void
    {
        $this->ligarCaptcha();
        $token = User::factory()->create()->createToken('api')->plainTextToken;

        $this->withToken($token)->getJson('/api/eu')->assertOk();
    }
}
