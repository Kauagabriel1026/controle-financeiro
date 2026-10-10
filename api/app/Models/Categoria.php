<?php

namespace App\Models;

use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = ['nome'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contas(): HasMany
    {
        return $this->hasMany(Conta::class);
    }
}
