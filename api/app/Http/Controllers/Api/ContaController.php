<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusConta;
use App\Enums\TipoConta;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarContaRequest;
use App\Http\Resources\ContaResource;
use App\Models\Conta;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ContaResource::collection($this->consulta($request)->paginate(20));
    }

    /**
     * Baixa as contas dos filtros atuais em CSV, no formato que o Excel em
     * português abre direto: ponto e vírgula como separador e vírgula decimal.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $consulta = $this->consulta($request);

        return response()->streamDownload(function () use ($consulta) {
            $saida = fopen('php://output', 'w');

            // Marca de UTF-8, para o Excel mostrar os acentos corretamente.
            fwrite($saida, "\xEF\xBB\xBF");
            $this->escreverLinha($saida, ['Vencimento', 'Descrição', 'Categoria', 'Tipo', 'Valor', 'Situação', 'Pago em']);

            foreach ($consulta->lazy(500) as $conta) {
                $this->escreverLinha($saida, [
                    $conta->vencimento->format('d/m/Y'),
                    $conta->descricao,
                    $conta->categoria?->nome ?? '',
                    $conta->tipo === TipoConta::Pagar ? 'A pagar' : 'A receber',
                    number_format((float) $conta->valor, 2, ',', ''),
                    ucfirst($conta->status->value),
                    $conta->pago_em?->format('d/m/Y') ?? '',
                ]);
            }

            fclose($saida);
        }, 'contas.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Com `repetir_meses`, cria a mesma conta nos meses seguintes, mantendo o
     * dia do vencimento (ou o último dia do mês, quando o dia não existe).
     */
    public function store(SalvarContaRequest $request): ContaResource
    {
        $dados = $request->safe()->except('repetir_meses');
        $meses = (int) ($request->validated('repetir_meses') ?? 1);
        $primeiroVencimento = CarbonImmutable::parse($dados['vencimento']);

        $contas = DB::transaction(function () use ($request, $dados, $meses, $primeiroVencimento) {
            $criadas = [];

            for ($mes = 0; $mes < $meses; $mes++) {
                $criadas[] = $request->user()->contas()->create([
                    ...$dados,
                    'vencimento' => $primeiroVencimento->addMonthsNoOverflow($mes),
                ]);
            }

            return $criadas;
        });

        return (new ContaResource($contas[0]))->additional(['criadas' => $meses]);
    }

    public function show(Request $request, int $id): ContaResource
    {
        return new ContaResource($this->buscar($request, $id));
    }

    public function update(SalvarContaRequest $request, int $id): ContaResource
    {
        $conta = $this->buscar($request, $id);
        $conta->update($request->safe()->except('repetir_meses'));

        return new ContaResource($conta);
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->buscar($request, $id)->delete();

        return response()->noContent();
    }

    /**
     * Baixa de pagamento: registra a data em que a conta foi paga.
     */
    public function pagar(Request $request, int $id): ContaResource
    {
        $dados = $request->validate([
            'pago_em' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $conta = $this->buscar($request, $id);

        abort_if($conta->pago_em !== null, 422, 'Esta conta já está paga.');

        $conta->update(['pago_em' => $dados['pago_em'] ?? today()]);

        return new ContaResource($conta);
    }

    /**
     * Desfaz a baixa: a conta volta a ficar em aberto.
     */
    public function estornar(Request $request, int $id): ContaResource
    {
        $conta = $this->buscar($request, $id);

        abort_if($conta->pago_em === null, 422, 'Esta conta não está paga.');

        $conta->update(['pago_em' => null]);

        return new ContaResource($conta);
    }

    /**
     * Contas do usuário logado, com os filtros da requisição, por vencimento.
     * Usada pela listagem e pela exportação, para as duas mostrarem o mesmo.
     */
    private function consulta(Request $request): HasMany
    {
        $filtros = $request->validate([
            'tipo' => ['nullable', Rule::enum(TipoConta::class)],
            'status' => ['nullable', Rule::enum(StatusConta::class)],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date'],
        ]);

        return $request->user()->contas()
            ->with('categoria')
            ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->comStatus(StatusConta::from($status)))
            ->when($filtros['de'] ?? null, fn ($query, $de) => $query->whereDate('vencimento', '>=', $de))
            ->when($filtros['ate'] ?? null, fn ($query, $ate) => $query->whereDate('vencimento', '<=', $ate))
            ->orderBy('vencimento')
            ->orderBy('id');
    }

    /**
     * Escreve uma linha do CSV. Texto que começa com =, +, - ou @ ganha um
     * apóstrofo na frente, para o Excel não executá-lo como fórmula.
     *
     * @param  resource  $saida
     * @param  list<string>  $colunas
     */
    private function escreverLinha($saida, array $colunas): void
    {
        $seguras = array_map(
            fn (string $texto) => preg_match('/^[=+\-@\t\r]/', $texto) ? "'".$texto : $texto,
            $colunas,
        );

        fputcsv($saida, $seguras, ';', '"', '');
    }

    /**
     * Busca a conta apenas entre as do usuário logado; a de outro usuário responde 404.
     */
    private function buscar(Request $request, int $id): Conta
    {
        return $request->user()->contas()->findOrFail($id);
    }
}
