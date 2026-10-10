<?php

// Mensagens de validação em português para as regras usadas pela API.

return [
    'before_or_equal' => 'O campo :attribute deve ser uma data igual ou anterior a hoje.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'enum' => 'O valor escolhido para :attribute é inválido.',
    'exists' => 'O valor escolhido para :attribute é inválido.',
    'gt' => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
    ],
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Já existe um cadastro com esse :attribute.',

    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'nome' => 'nome',
        'tipo' => 'tipo',
        'descricao' => 'descrição',
        'valor' => 'valor',
        'vencimento' => 'vencimento',
        'pago_em' => 'data de pagamento',
        'repetir_meses' => 'quantidade de meses',
        'categoria_id' => 'categoria',
        'de' => 'data inicial',
        'ate' => 'data final',
        'status' => 'situação',
    ],
];
