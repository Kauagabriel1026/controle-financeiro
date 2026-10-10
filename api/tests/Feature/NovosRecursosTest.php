<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Conta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NovosRecursosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    // ---------- Contas recorrentes ----------

    public function test_repete_a_conta_nos_meses_seguintes_respeitando_o_fim_do_mes(): void
    {
        $this->postJson('/api/contas', [
            'tipo' => 'pagar',
            'descricao' => 'Aluguel',
            'valor' => 1500,
            'vencimento' => '2027-01-31',
            'repetir_meses' => 4,
        ])->assertCreated()
            ->assertJsonPath('criadas', 4)
            ->assertJsonPath('data.vencimento', '2027-01-31');

        $this->assertSame(
            ['2027-01-31', '2027-02-28', '2027-03-31', '2027-04-30'],
            $this->user->contas()->orderBy('vencimento')->get()->map(fn (Conta $conta) => $conta->vencimento->toDateString())->all(),
        );

        $this->assertSame(4, $this->user->contas()->where('descricao', 'Aluguel')->where('valor', 1500)->count());
    }

    public function test_sem_repeticao_cria_uma_unica_conta(): void
    {
        $this->postJson('/api/contas', [
            'tipo' => 'pagar',
            'descricao' => 'Mercado',
            'valor' => 300,
            'vencimento' => '2026-11-10',
        ])->assertCreated()->assertJsonPath('criadas', 1);

        $this->assertDatabaseCount('contas', 1);
    }

    public function test_recusa_repeticao_acima_de_24_meses_e_nao_cria_nada(): void
    {
        $this->postJson('/api/contas', [
            'tipo' => 'pagar',
            'descricao' => 'Aluguel',
            'valor' => 1500,
            'vencimento' => '2026-11-10',
            'repetir_meses' => 25,
        ])->assertUnprocessable()->assertJsonValidationErrors('repetir_meses');

        $this->assertDatabaseCount('contas', 0);
    }

    public function test_editar_uma_conta_nao_cria_repeticoes(): void
    {
        $conta = Conta::factory()->for($this->user)->create();

        $this->putJson("/api/contas/{$conta->id}", [
            'tipo' => 'pagar',
            'descricao' => 'Editada',
            'valor' => 100,
            'vencimento' => '2026-11-10',
            'repetir_meses' => 6,
        ])->assertOk();

        $this->assertDatabaseCount('contas', 1);
    }

    // ---------- Exportação em CSV ----------

    public function test_exporta_as_contas_do_usuario_em_csv_respeitando_os_filtros(): void
    {
        $moradia = Categoria::factory()->for($this->user)->create(['nome' => 'Moradia']);

        Conta::factory()->for($this->user)->for($moradia)->paga()->create([
            'tipo' => 'pagar',
            'descricao' => 'Aluguel; com ponto e vírgula',
            'valor' => 1500.10,
            'vencimento' => '2026-10-05',
            'pago_em' => '2026-10-04',
        ]);
        Conta::factory()->for($this->user)->create([
            'tipo' => 'receber',
            'descricao' => 'Salário',
            'valor' => 5200,
            'vencimento' => '2026-10-06',
        ]);
        Conta::factory()->for($this->user)->create(['descricao' => 'De novembro', 'vencimento' => '2026-11-06']);
        Conta::factory()->create(['descricao' => 'De outro usuário', 'vencimento' => '2026-10-07']);

        $response = $this->get('/api/contas/exportar?de=2026-10-01&ate=2026-10-31');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('contas.csv', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $linhas = explode("\n", trim($csv));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertCount(3, $linhas);
        $this->assertStringContainsString('Vencimento;Descrição;Categoria;Tipo;Valor;Situação;"Pago em"', $linhas[0]);
        $this->assertSame('05/10/2026;"Aluguel; com ponto e vírgula";Moradia;"A pagar";1500,10;Paga;04/10/2026', $linhas[1]);
        $this->assertStringStartsWith('06/10/2026;Salário;;"A receber";5200,00;', $linhas[2]);
        $this->assertStringNotContainsString('De outro usuário', $csv);
        $this->assertStringNotContainsString('De novembro', $csv);
    }

    public function test_exportacao_neutraliza_texto_que_o_excel_executaria_como_formula(): void
    {
        Conta::factory()->for($this->user)->create(['descricao' => '=HYPERLINK("http://exemplo.com")']);

        $csv = $this->get('/api/contas/exportar')->streamedContent();

        $this->assertStringContainsString("\"'=HYPERLINK", $csv);
    }

    // ---------- Totais por categoria ----------

    public function test_soma_gastos_e_ganhos_do_periodo_por_categoria_do_maior_para_o_menor(): void
    {
        $moradia = Categoria::factory()->for($this->user)->create(['nome' => 'Moradia']);
        $lazer = Categoria::factory()->for($this->user)->create(['nome' => 'Lazer']);

        $conta = fn (array $dados) => Conta::factory()->for($this->user)->create(['tipo' => 'pagar', 'vencimento' => '2026-10-10', ...$dados]);

        $conta(['valor' => 1500.10, 'categoria_id' => $moradia->id]);
        $conta(['valor' => 99.90, 'categoria_id' => $moradia->id]);
        $conta(['valor' => 300, 'categoria_id' => $lazer->id]);
        $conta(['valor' => 100]);
        $conta(['valor' => 5000, 'tipo' => 'receber', 'categoria_id' => $lazer->id]);
        $conta(['valor' => 9999, 'vencimento' => '2026-11-10']);
        Conta::factory()->create(['tipo' => 'pagar', 'valor' => 9999, 'vencimento' => '2026-10-10']);

        $this->getJson('/api/totais-por-categoria?de=2026-10-01&ate=2026-10-31')
            ->assertOk()
            ->assertExactJson([
                'periodo' => ['de' => '2026-10-01', 'ate' => '2026-10-31'],
                'gastos' => [
                    'total' => '2000.00',
                    'categorias' => [
                        ['categoria' => 'Moradia', 'total' => '1600.00', 'percentual' => 80],
                        ['categoria' => 'Lazer', 'total' => '300.00', 'percentual' => 15],
                        ['categoria' => 'Sem categoria', 'total' => '100.00', 'percentual' => 5],
                    ],
                ],
                'ganhos' => [
                    'total' => '5000.00',
                    'categorias' => [
                        ['categoria' => 'Lazer', 'total' => '5000.00', 'percentual' => 100],
                    ],
                ],
            ]);
    }

    public function test_totais_por_categoria_sem_contas_devolve_listas_vazias(): void
    {
        $this->travelTo('2026-10-10');

        $this->getJson('/api/totais-por-categoria')
            ->assertOk()
            ->assertExactJson([
                'periodo' => ['de' => '2026-10-01', 'ate' => '2026-10-31'],
                'gastos' => ['total' => '0.00', 'categorias' => []],
                'ganhos' => ['total' => '0.00', 'categorias' => []],
            ]);
    }
}
