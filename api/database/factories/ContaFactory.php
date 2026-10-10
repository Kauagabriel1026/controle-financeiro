<?php

namespace Database\Factories;

use App\Enums\TipoConta;
use App\Models\Conta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conta>
 */
class ContaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'categoria_id' => null,
            'tipo' => fake()->randomElement(TipoConta::cases()),
            'descricao' => fake()->sentence(3),
            'valor' => fake()->randomFloat(2, 10, 5000),
            'vencimento' => fake()->dateTimeBetween('-1 month', '+2 months'),
            'pago_em' => null,
        ];
    }

    public function paga(): static
    {
        return $this->state(fn () => ['pago_em' => now()]);
    }
}
