<?php

namespace App\Http\Requests;

use App\Enums\TipoConta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarContaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A categoria é opcional, mas precisa pertencer ao usuário logado.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoConta::class)],
            'descricao' => ['required', 'string', 'max:120'],
            'valor' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'vencimento' => ['required', 'date'],
            'repetir_meses' => ['nullable', 'integer', 'min:1', 'max:24'],
            'categoria_id' => [
                'nullable',
                'integer',
                Rule::exists('categorias', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
