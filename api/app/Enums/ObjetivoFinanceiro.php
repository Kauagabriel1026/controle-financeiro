<?php

namespace App\Enums;

enum ObjetivoFinanceiro: string
{
    case SairDasDividas = 'sair_das_dividas';
    case MontarReserva = 'montar_reserva';
    case ControlarGastos = 'controlar_gastos';
    case Investir = 'investir';
}
