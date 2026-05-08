<?php

declare(strict_types=1);

namespace App\Enums;

enum TrashedFilter: string
{
    case With = 'with';
    case Only = 'only';
}
