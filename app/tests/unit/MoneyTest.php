<?php

namespace tests\unit;

use app\models\banking\CurrencyEnum;
use app\models\banking\Money;
use app\models\banking\exceptions\InsufficientFundsException;
use Yii;
use Codeception\Test\Unit;

class MoneyTest extends Unit
{
    public function testConstruct()
    {
        $money = new Money(1000, CurrencyEnum::RUB);
        $this->assertEquals(1000, $money->getAmount());
        $this->assertEquals(CurrencyEnum::RUB, $money->getCurrency());

        // Проверяем исключение для отрицательной суммы
        $this->expectException(\InvalidArgumentException::class);
        new Money(-100, CurrencyEnum::RUB);
    }

    public function testAdd()
    {
        $money1 = new Money(1000, CurrencyEnum::RUB);
        $money2 = new Money(500, CurrencyEnum::RUB);
        $result = $money1->add($money2);
        $this->assertEquals(1500, $result->getAmount());
        $this->assertEquals(CurrencyEnum::RUB, $result->getCurrency());

        // Проверяем исключение для разных валют
        $this->expectException(\InvalidArgumentException::class);
        $money1->add(new Money(100, CurrencyEnum::USD));
    }

    public function testSubtract()
    {
        $money1 = new Money(1000, CurrencyEnum::RUB);
        $money2 = new Money(400, CurrencyEnum::RUB);
        $result = $money1->subtract($money2);
        $this->assertEquals(600, $result->getAmount());
        $this->assertEquals(CurrencyEnum::RUB, $result->getCurrency());

        // Проверяем исключение при недостатке средств
        $this->expectException(InsufficientFundsException::class);
        $money1->subtract(new Money(2000, CurrencyEnum::RUB));

        // Проверяем исключение для разных валют
        $this->expectException(\InvalidArgumentException::class);
        $money1->subtract(new Money(100, CurrencyEnum::USD));
    }

    public function testConvertTo()
    {
        $money = new Money(50, CurrencyEnum::EUR);
        $converted = $money->convertTo(CurrencyEnum::RUB);
        $this->assertEquals(4000, $converted->getAmount()); // 50 * 80
        $this->assertEquals(CurrencyEnum::RUB, $converted->getCurrency());

        // Конвертация в ту же валюту
        $same = $money->convertTo(CurrencyEnum::EUR);
        $this->assertEquals(50, $same->getAmount());
        $this->assertEquals(CurrencyEnum::EUR, $same->getCurrency());
    }
}