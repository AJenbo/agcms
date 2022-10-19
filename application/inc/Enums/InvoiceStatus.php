<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case New = 'new';
    case Locked = 'locked';
    case Accepted = 'accepted';
    case Cash = 'cash';
    case Giro = 'giro';
    case PbsOk = 'pbsok';
    case Rejected = 'rejected';
    case Canceled = 'canceled';
}
