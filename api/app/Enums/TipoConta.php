<?php

namespace App\Enums;

enum TipoConta: string
{
    case Pagar = 'pagar';
    case Receber = 'receber';
}
