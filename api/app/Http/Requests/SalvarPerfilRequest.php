<?php

namespace App\Http\Requests;

use App\Enums\ObjetivoFinanceiro;
use App\Enums\Tema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Cada parte da tela de perfil salva só os próprios campos, por isso todos
     * são "sometimes": o que não vier no pedido continua como está.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'renda_mensal' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999'],
            'dia_pagamento' => ['sometimes', 'nullable', 'integer', 'between:1,31'],
            'objetivo' => ['sometimes', 'nullable', Rule::enum(ObjetivoFinanceiro::class)],
            'meta_economia' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999'],
            'tema' => ['sometimes', 'required', Rule::enum(Tema::class)],
        ];
    }
}
