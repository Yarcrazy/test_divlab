<?php

namespace app\models\banking;

enum CurrencyEnum: string
{
    case RUB = 'RUB';
    case USD = 'USD';
    case EUR = 'EUR';
}