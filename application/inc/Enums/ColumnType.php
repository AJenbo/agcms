<?php

namespace App\Enums;

enum ColumnType: int
{
    case String = 0;
    case Int = 1;
    case Price = 2;
    case SalesPrice = 3;
    case PreviousPrice = 4;
}
