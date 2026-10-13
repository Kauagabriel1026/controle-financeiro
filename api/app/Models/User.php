<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ObjetivoFinanceiro;
use App\Enums\Tema;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'demonstracao' => 'boolean',
            'renda_mensal' => 'decimal:2',
            'meta_economia' => 'decimal:2',
            'dia_pagamento' => 'integer',
            'objetivo' => ObjetivoFinanceiro::class,
            'tema' => Tema::class,
        ];
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public function contas(): HasMany
    {
        return $this->hasMany(Conta::class);
    }

    /**
     * O que a API devolve sobre o usuário logado.
     *
     * @return array<string, mixed>
     */
    public function dadosParaApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'demonstracao' => (bool) $this->demonstracao,
            'renda_mensal' => $this->renda_mensal,
            'dia_pagamento' => $this->dia_pagamento,
            'objetivo' => $this->objetivo?->value,
            'meta_economia' => $this->meta_economia,
            'tema' => $this->tema?->value ?? 'sistema',
        ];
    }
}
