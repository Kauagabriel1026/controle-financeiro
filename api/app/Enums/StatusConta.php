<?php

namespace App\Enums;

enum StatusConta: string
{
    case Pendente = 'pendente';
    case Paga = 'paga';
    case Vencida = 'vencida';
}
