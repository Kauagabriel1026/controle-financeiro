<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O nome é único por usuário; na edição, a própria categoria é ignorada.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:60',
                Rule::unique('categorias', 'nome')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->route('id')),
            ],
        ];
    }
}
