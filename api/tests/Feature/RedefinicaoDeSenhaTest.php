<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ContaDeDemonstracao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RedefinicaoDeSenhaTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAGEM = 'Se o e-mail estiver cadastrado, você vai receber um link para criar uma nova senha. Confira também a caixa de spam.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.brevo.chave' => 'chave-de-teste',
            'services.brevo.remetente' => 'kamel@example.com',
            'app.front_url' => 'https://kamel.example',
        ]);

        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'teste'], 201)]);
    }

    /** Pede o link e devolve o token que foi no e-mail. */
    private function pedirLink(string $email): string
    {
        $this->postJson('/api/esqueci-senha', ['email' => $email])->assertOk();

        $enviado = Http::recorded()->last()[0];
        preg_match('/token=([^&\s"]+)/', $enviado['textContent'], $partes);

        return urldecode($partes[1]);
    }

    public function test_envia_pelo_brevo_um_link_para_o_front_end(): void
    {
        User::factory()->create(['name' => 'Maria', 'email' => 'maria@example.com']);

        $this->postJson('/api/esqueci-senha', ['email' => 'maria@example.com'])
            ->assertOk()
            ->assertJsonPath('message', self::MENSAGEM);

        Http::assertSent(fn (Request $pedido) => $pedido->url() === 'https://api.brevo.com/v3/smtp/email'
            && $pedido->hasHeader('api-key', 'chave-de-teste')
            && $pedido['to'][0]['email'] === 'maria@example.com'
            && $pedido['sender']['email'] === 'kamel@example.com'
            && str_contains($pedido['htmlContent'], 'https://kamel.example/redefinir-senha?token=')
            && str_contains($pedido['textContent'], 'email=maria%40example.com'));
    }

    public function test_email_sem_cadastro_recebe_a_mesma_resposta_e_nada_e_enviado(): void
    {
        $this->postJson('/api/esqueci-senha', ['email' => 'ninguem@example.com'])
            ->assertOk()
            ->assertJsonPath('message', self::MENSAGEM);

        Http::assertNothingSent();
    }

    public function test_conta_de_visitante_nao_recebe_email(): void
    {
        $visitante = ContaDeDemonstracao::criar();

        $this->postJson('/api/esqueci-senha', ['email' => $visitante->email])->assertOk();

        Http::assertNothingSent();
    }

    public function test_sem_a_chave_do_brevo_nada_e_enviado_e_a_resposta_nao_muda(): void
    {
        config(['services.brevo.chave' => null]);
        User::factory()->create(['email' => 'maria@example.com']);

        $this->postJson('/api/esqueci-senha', ['email' => 'maria@example.com'])
            ->assertOk()
            ->assertJsonPath('message', self::MENSAGEM);

        Http::assertNothingSent();
    }

    public function test_falha_no_brevo_nao_muda_a_resposta(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'erro'], 500)]);
        User::factory()->create(['email' => 'maria@example.com']);

        $this->postJson('/api/esqueci-senha', ['email' => 'maria@example.com'])
            ->assertOk()
            ->assertJsonPath('message', self::MENSAGEM);
    }

    public function test_redefine_a_senha_e_encerra_as_sessoes_abertas(): void
    {
        $user = User::factory()->create(['email' => 'maria@example.com', 'password' => 'senha-antiga']);
        $user->createToken('api');

        $token = $this->pedirLink('maria@example.com');

        $this->postJson('/api/redefinir-senha', [
            'token' => $token,
            'email' => 'maria@example.com',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertOk()->assertJsonPath('message', 'Senha alterada. Entre com a nova senha.');

        $this->assertSame(0, $user->tokens()->count());

        $this->postJson('/api/login', ['email' => 'maria@example.com', 'password' => 'senha-antiga'])->assertUnprocessable();
        $this->postJson('/api/login', ['email' => 'maria@example.com', 'password' => 'senha-nova-123'])->assertOk();
    }

    public function test_o_link_so_pode_ser_usado_uma_vez(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);
        $token = $this->pedirLink('maria@example.com');
        $dados = ['token' => $token, 'email' => 'maria@example.com', 'password' => 'senha-nova-123', 'password_confirmation' => 'senha-nova-123'];

        $this->postJson('/api/redefinir-senha', $dados)->assertOk();
        $this->postJson('/api/redefinir-senha', $dados)
            ->assertUnprocessable()
            ->assertJsonPath('errors.token.0', 'Este link é inválido ou já expirou. Peça um novo.');
    }

    public function test_link_expira_em_60_minutos(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);
        $token = $this->pedirLink('maria@example.com');

        $this->travel(61)->minutes();

        $this->postJson('/api/redefinir-senha', [
            'token' => $token,
            'email' => 'maria@example.com',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertUnprocessable()->assertJsonValidationErrors('token');
    }

    public function test_token_de_um_usuario_nao_serve_para_outro(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);
        User::factory()->create(['email' => 'joao@example.com', 'password' => 'senha-do-joao']);
        $token = $this->pedirLink('maria@example.com');

        $this->postJson('/api/redefinir-senha', [
            'token' => $token,
            'email' => 'joao@example.com',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ])->assertUnprocessable()->assertJsonValidationErrors('token');

        $this->postJson('/api/login', ['email' => 'joao@example.com', 'password' => 'senha-do-joao'])->assertOk();
    }

    public function test_valida_a_nova_senha(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);
        $token = $this->pedirLink('maria@example.com');

        $this->postJson('/api/redefinir-senha', [
            'token' => $token,
            'email' => 'maria@example.com',
            'password' => 'curta',
            'password_confirmation' => 'outra',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }
}
