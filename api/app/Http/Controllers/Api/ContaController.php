<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusConta;
use App\Enums\TipoConta;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarContaRequest;
use App\Http\Resources\ContaResource;
use App\Models\Conta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ContaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'tipo' => ['nullable', Rule::enum(TipoConta::class)],
            'status' => ['nullable', Rule::enum(StatusConta::class)],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date'],
        ]);

        $contas = $request->user()->contas()
            ->with('categoria')
            ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->comStatus(StatusConta::from($status)))
            ->when($filtros['de'] ?? null, fn ($query, $de) => $query->whereDate('vencimento', '>=', $de))
            ->when($filtros['ate'] ?? null, fn ($query, $ate) => $query->whereDate('vencimento', '<=', $ate))
            ->orderBy('vencimento')
            ->orderBy('id')
            ->paginate(20);

        return ContaResource::collection($contas);
    }

    public function store(SalvarContaRequest $request): ContaResource
    {
        $conta = $request->user()->contas()->create($request->validated());

        return new ContaResource($conta);
    }

    public function show(Request $request, int $id): ContaResource
    {
        return new ContaResource($this->buscar($request, $id));
    }

    public function update(SalvarContaRequest $request, int $id): ContaResource
    {
        $conta = $this->buscar($request, $id);
        $conta->update($request->validated());

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
     * Busca a conta apenas entre as do usuário logado; a de outro usuário responde 404.
     */
    private function buscar(Request $request, int $id): Conta
    {
        return $request->user()->contas()->findOrFail($id);
    }
}
