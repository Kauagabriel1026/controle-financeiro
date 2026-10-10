<?php

namespace App\Models;

use App\Enums\StatusConta;
use App\Enums\TipoConta;
use Database\Factories\ContaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * Paga quando tem data de pagamento; vencida quando o vencimento já passou.
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): StatusConta => match (true) {
            $this->pago_em !== null => StatusConta::Paga,
            $this->vencimento->lt(today()) => StatusConta::Vencida,
            default => StatusConta::Pendente,
        });
    }

    public function scopeComStatus(Builder $query, StatusConta $status): void
    {
        match ($status) {
            StatusConta::Paga => $query->whereNotNull('pago_em'),
            StatusConta::Vencida => $query->whereNull('pago_em')->whereDate('vencimento', '<', today()),
            StatusConta::Pendente => $query->whereNull('pago_em')->whereDate('vencimento', '>=', today()),
        };
    }
}
