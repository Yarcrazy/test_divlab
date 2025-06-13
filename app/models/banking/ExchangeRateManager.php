<?php

namespace app\models\banking;

use app\models\banking\exceptions\InvalidExchangeRateException;
use yii\base\Component;

class ExchangeRateManager extends Component
{
    private array $rates = [
        CurrencyEnum::EUR->value => [CurrencyEnum::RUB->value => 80, CurrencyEnum::USD->value => 1],
        CurrencyEnum::USD->value => [CurrencyEnum::RUB->value => 70],
        CurrencyEnum::RUB->value => [],
    ];

    public function setRate(CurrencyEnum $from, CurrencyEnum $to, float $rate): void
    {
        if ($rate <= 0) {
            throw new InvalidExchangeRateException('Exchange rate must be positive.');
        }
        $this->rates[$from->value][$to->value] = $rate;
    }

    public function getRate(CurrencyEnum $from, CurrencyEnum $to): float
    {
        if ($from === $to) {
            return 1.0;
        }
        if (!isset($this->rates[$from->value][$to->value])) {
            throw new InvalidExchangeRateException("Exchange rate from {$from->value} to {$to->value} not set.");
        }
        return $this->rates[$from->value][$to->value];
    }
}