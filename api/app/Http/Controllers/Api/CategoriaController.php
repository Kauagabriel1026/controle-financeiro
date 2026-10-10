<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalvarCategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Support\CategoriasSugeridas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoriaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return CategoriaResource::collection(
            $request->user()->categorias()->orderBy('nome')->get()
        );
    }

    public function store(SalvarCategoriaRequest $request): CategoriaResource
    {
        $categoria = $request->user()->categorias()->create($request->validated());

        return new CategoriaResource($categoria);
    }

    /**
     * Adiciona as categorias sugeridas que faltam e devolve a lista completa.
     */
    public function sugeridas(Request $request): AnonymousResourceCollection
    {
        CategoriasSugeridas::adicionarPara($request->user());

        return $this->index($request);
    }

    public function update(SalvarCategoriaRequest $request, int $id): CategoriaResource
    {
        $categoria = $request->user()->categorias()->findOrFail($id);
        $categoria->update($request->validated());

        return new CategoriaResource($categoria);
    }

    public function destroy(Request $request, int $id): Response
    {
        $request->user()->categorias()->findOrFail($id)->delete();

        return response()->noContent();
    }
}
