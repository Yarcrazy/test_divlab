<?php

namespace app\models\banking;

use Yii;

class Money
{
    private float $amount;
    private CurrencyEnum $currency;

    public function __construct(float $amount, CurrencyEnum $currency)
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative.');
        }
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): CurrencyEnum
    {
        return $this->currency;
    }

    public function add(self $money): self
    {
        if ($this->currency !== $money->currency) {
            throw new \InvalidArgumentException('Currencies must match.');
        }
        return new self($this->amount + $money->amount, $this->currency);
    }

    public function subtract(self $money): self
    {
        if ($this->currency !== $money->currency) {
            throw new \InvalidArgumentException('Currencies must match.');
        }
        if ($this->amount < $money->amount) {
            throw new exceptions\InsufficientFundsException('Insufficient funds.');
        }
        return new self($this->amount - $money->amount, $this->currency);
    }

    public function convertTo(CurrencyEnum $targetCurrency): self
    {
        if ($this->currency === $targetCurrency) {
            return new self($this->amount, $targetCurrency);
        }

        /** @var ExchangeRateManager $exchangeRateManager */
        $exchangeRateManager = Yii::$app->get('exchangeRateManager');
        $rate = $exchangeRateManager->getRate($this->currency, $targetCurrency);
        return new self(round($this->amount * $rate, 2), $targetCurrency);
    }
}