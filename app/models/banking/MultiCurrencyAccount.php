<?php

namespace app\models\banking;

use app\models\banking\exceptions\UnsupportedCurrencyException;
use Yii;

class MultiCurrencyAccount
{
    /** @var array<CurrencyEnum, Money> */
    private array $balances = [];
    /** @var array<CurrencyEnum> */
    private array $supportedCurrencies = [];
    private CurrencyEnum $baseCurrency;

    public function addCurrency(CurrencyEnum $currency): void
    {
        if (!in_array($currency, $this->supportedCurrencies, true)) {
            $this->supportedCurrencies[] = $currency;
            $this->balances[$currency->value] = new Money(0, $currency);
        }
        if (!isset($this->baseCurrency)) {
            $this->baseCurrency = $currency;
        }
    }

    public function removeCurrency(CurrencyEnum $currency): void
    {
        if (!in_array($currency, $this->supportedCurrencies, true)) {
            throw new UnsupportedCurrencyException("Currency {$currency->value} is not supported.");
        }
        if ($currency === $this->baseCurrency) {
            throw new \InvalidArgumentException('Cannot remove base currency.');
        }

        // Конвертируем баланс в основную валюту
        $balance = $this->balances[$currency->value];
        if ($balance->getAmount() > 0) {
            $converted = $balance->convertTo($this->baseCurrency);
            $this->balances[$this->baseCurrency->value] = $this->balances[$this->baseCurrency->value]->add($converted);
        }

        // Удаляем валюту
        unset($this->balances[$currency->value]);
        $this->supportedCurrencies = array_filter(
            $this->supportedCurrencies,
            fn($c) => $c !== $currency
        );
    }

    public function setBaseCurrency(CurrencyEnum $currency): void
    {
        if (!in_array($currency, $this->supportedCurrencies, true)) {
            throw new UnsupportedCurrencyException("Currency {$currency->value} is not supported.");
        }
        $this->baseCurrency = $currency;
    }

    /** @return array<CurrencyEnum> */
    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    public function deposit(Money $money): void
    {
        $currency = $money->getCurrency();
        if (!in_array($currency, $this->supportedCurrencies, true)) {
            throw new UnsupportedCurrencyException("Currency {$currency->value} is not supported.");
        }
        $this->balances[$currency->value] = $this->balances[$currency->value]->add($money);
    }

    public function withdraw(Money $money): Money
    {
        $currency = $money->getCurrency();
        if (!in_array($currency, $this->supportedCurrencies, true)) {
            throw new UnsupportedCurrencyException("Currency {$currency->value} is not supported.");
        }
        $this->balances[$currency->value] = $this->balances[$currency->value]->subtract($money);
        return $money;
    }

    public function getBalance(?CurrencyEnum $currency = null): Money
    {
        $targetCurrency = $currency ?? $this->baseCurrency;
        $total = new Money(0, $targetCurrency);

        foreach ($this->balances as $balance) {
            $converted = $balance->convertTo($targetCurrency);
            $total = $total->add($converted);
        }

        return $total;
    }
}