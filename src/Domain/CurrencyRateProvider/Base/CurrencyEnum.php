<?php

declare(strict_types=1);

namespace App\Domain\CurrencyRateProvider\Base;

enum CurrencyEnum: string
{
    case AUD = 'AUD';
    case CAD = 'CAD';
    case CHF = 'CHF';
    case CNY = 'CNY';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case IRR = 'IRR';
    case JPY = 'JPY';
    case RUB = 'RUB';
    case USD = 'USD';
}
