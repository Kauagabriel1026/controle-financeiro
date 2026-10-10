<?php

namespace App\Models;

use App\Enums\TipoConta;
use Database\Factories\ContaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conta extends Model
{
    /** @use HasFactory<ContaFactory> */
    use HasFactory;

    protected $table = 'contas';

    protected $fillable = [
        'categoria_id',
        'tipo',
        'descricao',
        'valor',
        'vencimento',
        'pago_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoConta::class,
            'valor' => 'decimal:2',
            'vencimento' => 'date',
            'pago_em' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
}
