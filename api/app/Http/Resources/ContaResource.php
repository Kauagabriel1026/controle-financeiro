<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo->value,
            'descricao' => $this->descricao,
            'valor' => $this->valor,
            'vencimento' => $this->vencimento->toDateString(),
            'pago_em' => $this->pago_em?->toDateString(),
            'status' => $this->status->value,
            'categoria' => $this->categoria
                ? ['id' => $this->categoria->id, 'nome' => $this->categoria->nome]
                : null,
        ];
    }
}
