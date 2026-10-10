<?php

namespace App\Support;

use App\Models\User;

class CategoriasSugeridas
{
    /** Categorias criadas para todo usuário novo. */
    public const NOMES = [
        'Alimentação',
        'Educação',
        'Lazer',
        'Moradia',
        'Salário',
        'Saúde',
        'Transporte',
        'Outros',
    ];

    /**
     * Cria as categorias sugeridas que o usuário ainda não tem.
     * Pode ser chamada mais de uma vez sem duplicar nada.
     */
    public static function adicionarPara(User $user): void
    {
        $existentes = $user->categorias()->pluck('nome')->all();

        foreach (array_diff(self::NOMES, $existentes) as $nome) {
            $user->categorias()->create(['nome' => $nome]);
        }
    }
}
