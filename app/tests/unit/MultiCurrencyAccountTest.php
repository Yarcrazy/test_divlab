<?php

namespace tests\unit;

use app\models\banking\CurrencyEnum;
use app\models\banking\exceptions\InsufficientFundsException;
use app\models\banking\exceptions\UnsupportedCurrencyException;
use app\models\banking\ExchangeRateManager;
use app\models\banking\Money;
use app\models\banking\Bank;
use Codeception\Test\Unit;
use Yii;

class MultiCurrencyAccountTest extends Unit
{
    public function testScenario()
    {
        /** @var ExchangeRateManager $exchangeRateManager */
        $exchangeRateManager = Yii::$app->get('exchangeRateManager');

        // Шаг 1: Создание счета
        $bank = new Bank();
        $account = $bank->openAccount();
        $account->addCurrency(CurrencyEnum::RUB);
        $account->addCurrency(CurrencyEnum::EUR);
        $account->addCurrency(CurrencyEnum::USD);
        $account->setBaseCurrency(CurrencyEnum::RUB);
        $this->assertEquals([CurrencyEnum::RUB, CurrencyEnum::EUR, CurrencyEnum::USD], $account->getSupportedCurrencies());

        $account->deposit(new Money(1000, CurrencyEnum::RUB));
        $account->deposit(new Money(50, CurrencyEnum::EUR));
        $account->deposit(new Money(40, CurrencyEnum::USD));

        // Шаг 2: Проверка баланса
        $balanceRUB = $account->getBalance(CurrencyEnum::RUB);
        $this->assertEquals(7800, $balanceRUB->getAmount()); // 1000 + 50 * 80 + 40 * 70
        $exchangeRateManager->setRate(CurrencyEnum::RUB, CurrencyEnum::USD, 0.014);
        $balanceUSD = $account->getBalance(CurrencyEnum::USD);
        $this->assertEquals(104, $balanceUSD->getAmount()); // 1000 * 0.014 + 50 * 1 + 40

        // Шаг 3: Операции
        $account->deposit(new Money(1000, CurrencyEnum::RUB)); // 2000
        $account->deposit(new Money(50, CurrencyEnum::EUR)); // 100
        $account->withdraw(new Money(10, CurrencyEnum::USD)); // 30

        // Шаг 4: Изменение курсов
        $exchangeRateManager->setRate(CurrencyEnum::EUR, CurrencyEnum::RUB, 150);
        $exchangeRateManager->setRate(CurrencyEnum::USD, CurrencyEnum::RUB, 100);

        // Шаг 5: Баланс после изменения курсов
        $balanceRUB = $account->getBalance(CurrencyEnum::RUB);
        $this->assertEquals(20000, $balanceRUB->getAmount()); // 2000 + 100 * 150 + 30 * 100

        // Шаг 6: Смена основной валюты
        $account->setBaseCurrency(CurrencyEnum::EUR);
        $exchangeRateManager->setRate(CurrencyEnum::RUB, CurrencyEnum::EUR, 0.006);
        $exchangeRateManager->setRate(CurrencyEnum::USD, CurrencyEnum::EUR, 1);
        $balanceEUR = $account->getBalance();
        $this->assertEquals(142, $balanceEUR->getAmount()); // 2000 * 0.006 + 100 + 30 * 1

        // Шаг 7: Конвертация RUB в EUR
        $money = $account->withdraw(new Money(2000, CurrencyEnum::RUB)); // 0
        $converted = $money->convertTo(CurrencyEnum::EUR); // 2000 * 0.006
        $account->deposit($converted);
        $balanceEUR = $account->getBalance(); // 112 + 30
        $this->assertEquals(142, $balanceEUR->getAmount()); // 142

        // Шаг 8: Изменение курса EUR/RUB
        $exchangeRateManager->setRate(CurrencyEnum::EUR, CurrencyEnum::RUB, 120);

        // Шаг 9: Баланс не изменился
        $balanceEUR = $account->getBalance();
        $this->assertEquals(142, $balanceEUR->getAmount()); // 142

        // Шаг 10: Отключение валют
        $account->setBaseCurrency(CurrencyEnum::RUB);
        $account->removeCurrency(CurrencyEnum::EUR);
        $account->removeCurrency(CurrencyEnum::USD); // 112 * 120 + 30 * 1000 = 16440
        $this->assertEquals([CurrencyEnum::RUB], $account->getSupportedCurrencies());
        $balanceRUB = $account->getBalance();
        $this->assertEquals(16440, $balanceRUB->getAmount());
    }

    public function testAddAndRemoveCurrency()
    {
        $bank = new Bank();
        $account = $bank->openAccount();
        $account->addCurrency(CurrencyEnum::RUB);
        $account->addCurrency(CurrencyEnum::EUR);
        $this->assertEquals([CurrencyEnum::RUB, CurrencyEnum::EUR], $account->getSupportedCurrencies());

        $account->deposit(new Money(1000, CurrencyEnum::RUB));
        $account->removeCurrency(CurrencyEnum::EUR);
        $this->assertEquals([CurrencyEnum::RUB], $account->getSupportedCurrencies());

        // Проверяем исключение при удалении неподдерживаемой валюты
        $this->expectException(UnsupportedCurrencyException::class);
        $account->removeCurrency(CurrencyEnum::USD);

        // Проверяем исключение при удалении базовой валюты
        $account->setBaseCurrency(CurrencyEnum::RUB);
        $this->expectException(\InvalidArgumentException::class);
        $account->removeCurrency(CurrencyEnum::RUB);
    }

    public function testSetBaseCurrency()
    {
        /** @var ExchangeRateManager $exchangeRateManager */
        $exchangeRateManager = Yii::$app->get('exchangeRateManager');

        $bank = new Bank();
        $account = $bank->openAccount();
        $account->addCurrency(CurrencyEnum::RUB);
        $account->addCurrency(CurrencyEnum::EUR);
        $account->setBaseCurrency(CurrencyEnum::EUR);
        $exchangeRateManager->setRate(CurrencyEnum::RUB, CurrencyEnum::EUR, 0.006);
        $this->assertEquals(CurrencyEnum::EUR, $account->getBalance()->getCurrency());

        // Проверяем исключение для неподдерживаемой валюты
        $this->expectException(UnsupportedCurrencyException::class);
        $account->setBaseCurrency(CurrencyEnum::USD);
    }

    public function testDepositAndWithdraw()
    {
        $bank = new Bank();
        $account = $bank->openAccount();
        $account->addCurrency(CurrencyEnum::RUB);
        $account->addCurrency(CurrencyEnum::USD);

        $account->deposit(new Money(1000, CurrencyEnum::RUB));
        $account->deposit(new Money(50, CurrencyEnum::USD));

        $balanceRUB = $account->getBalance(CurrencyEnum::RUB);
        $this->assertEquals(4500, $balanceRUB->getAmount()); // 1000 + 50*70

        $account->withdraw(new Money(500, CurrencyEnum::RUB));
        $balanceRUB = $account->getBalance(CurrencyEnum::RUB);
        $this->assertEquals(4000, $balanceRUB->getAmount()); // 4500 - 500

        // Проверяем исключение для неподдерживаемой валюты
        $this->expectException(UnsupportedCurrencyException::class);
        $account->deposit(new Money(100, CurrencyEnum::EUR));

        // Проверяем исключение при недостатке средств
        $this->expectException(InsufficientFundsException::class);
        $account->withdraw(new Money(1000, CurrencyEnum::USD));
    }

    public function testBalanceConversion()
    {
        /** @var ExchangeRateManager $exchangeRateManager */
        $exchangeRateManager = Yii::$app->get('exchangeRateManager');

        $bank = new Bank();
        $account = $bank->openAccount();
        $account->addCurrency(CurrencyEnum::RUB);
        $account->addCurrency(CurrencyEnum::EUR);
        $account->addCurrency(CurrencyEnum::USD);
        $account->deposit(new Money(1000, CurrencyEnum::RUB));
        $account->deposit(new Money(50, CurrencyEnum::EUR));
        $account->deposit(new Money(10, CurrencyEnum::USD));

        $balanceRUB = $account->getBalance(CurrencyEnum::RUB);
        $this->assertEquals(5700, $balanceRUB->getAmount()); // 1000 + 50 * 80 + 10 * 70

        $exchangeRateManager->setRate(CurrencyEnum::RUB, CurrencyEnum::EUR, 0.006);
        $exchangeRateManager->setRate(CurrencyEnum::USD, CurrencyEnum::EUR, 1);
        $balanceEUR = $account->getBalance(CurrencyEnum::EUR);
        $this->assertEquals(66, $balanceEUR->getAmount()); // 1000 * 0.006 + 50 + 10

        $exchangeRateManager->setRate(CurrencyEnum::RUB, CurrencyEnum::USD, 0.014);
        $balanceEUR = $account->getBalance(CurrencyEnum::USD);
        $this->assertEquals(74, $balanceEUR->getAmount()); // 1000 * 0.014 + 50 + 10
    }
}