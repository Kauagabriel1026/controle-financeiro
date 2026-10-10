<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use App\Support\CategoriasSugeridas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoriaApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_lista_apenas_as_categorias_do_usuario_em_ordem_alfabetica(): void
    {
        Categoria::factory()->for($this->user)->create(['nome' => 'Mercado']);
        Categoria::factory()->for($this->user)->create(['nome' => 'Aluguel']);
        Categoria::factory()->create(['nome' => 'De outro usuário']);

        $this->getJson('/api/categorias')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nome', 'Aluguel')
            ->assertJsonPath('data.1.nome', 'Mercado');
    }

    public function test_cria_uma_categoria(): void
    {
        $this->postJson('/api/categorias', ['nome' => 'Salário'])
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Salário');

        $this->assertDatabaseHas('categorias', [
            'user_id' => $this->user->id,
            'nome' => 'Salário',
        ]);
    }

    public function test_recusa_nome_repetido_para_o_mesmo_usuario(): void
    {
        Categoria::factory()->for($this->user)->create(['nome' => 'Mercado']);

        $this->postJson('/api/categorias', ['nome' => 'Mercado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nome');
    }

    public function test_usuarios_diferentes_podem_usar_o_mesmo_nome(): void
    {
        Categoria::factory()->create(['nome' => 'Mercado']);

        $this->postJson('/api/categorias', ['nome' => 'Mercado'])->assertCreated();
    }

    public function test_edita_uma_categoria_mantendo_o_proprio_nome(): void
    {
        $categoria = Categoria::factory()->for($this->user)->create(['nome' => 'Mercado']);

        $this->putJson("/api/categorias/{$categoria->id}", ['nome' => 'Mercado'])->assertOk();

        $this->putJson("/api/categorias/{$categoria->id}", ['nome' => 'Supermercado'])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Supermercado');
    }

    public function test_exclui_uma_categoria(): void
    {
        $categoria = Categoria::factory()->for($this->user)->create();

        $this->deleteJson("/api/categorias/{$categoria->id}")->assertNoContent();

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_nao_altera_nem_exclui_categoria_de_outro_usuario(): void
    {
        $alheia = Categoria::factory()->create(['nome' => 'Alheia']);

        $this->putJson("/api/categorias/{$alheia->id}", ['nome' => 'Minha'])->assertNotFound();
        $this->deleteJson("/api/categorias/{$alheia->id}")->assertNotFound();

        $this->assertDatabaseHas('categorias', ['id' => $alheia->id, 'nome' => 'Alheia']);
    }

    public function test_adiciona_as_sugeridas_que_faltam_sem_duplicar(): void
    {
        Categoria::factory()->for($this->user)->create(['nome' => 'Moradia']);
        Categoria::factory()->for($this->user)->create(['nome' => 'Minha categoria']);

        $this->postJson('/api/categorias/sugeridas')
            ->assertOk()
            ->assertJsonCount(count(CategoriasSugeridas::NOMES) + 1, 'data');

        // Chamar de novo não cria nada.
        $this->postJson('/api/categorias/sugeridas')
            ->assertJsonCount(count(CategoriasSugeridas::NOMES) + 1, 'data');

        $this->assertDatabaseCount('categorias', count(CategoriasSugeridas::NOMES) + 1);
    }
}
