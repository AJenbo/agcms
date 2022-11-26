<?php

namespace App\Enums;

enum InvoiceAction: string
{
    case Save = 'save';
    case Lock = 'lock';
    case Email = 'email';
    case Giro = 'giro';
    case Cash = 'cash';
    case Cancel = 'cancel';
}
