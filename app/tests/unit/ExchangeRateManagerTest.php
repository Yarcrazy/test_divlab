<?php

namespace tests\unit;

use app\models\banking\CurrencyEnum;
use app\models\banking\ExchangeRateManager;
use app\models\banking\exceptions\InvalidExchangeRateException;
use Codeception\Test\Unit;
use Yii;

class ExchangeRateManagerTest extends Unit
{
    public function testSetAndGetRate()
    {
        /** @var ExchangeRateManager $exchangeRateManager */
        $exchangeRateManager = Yii::$app->get('exchangeRateManager');
        $exchangeRateManager->setRate(CurrencyEnum::EUR, CurrencyEnum::RUB, 150);

        $this->assertEquals(150, $exchangeRateManager->getRate(CurrencyEnum::EUR, CurrencyEnum::RUB));

        // Проверяем исключение для отрицательного курса
        $this->expectException(InvalidExchangeRateException::class);
        $exchangeRateManager->setRate(CurrencyEnum::USD, CurrencyEnum::RUB, -100);
    }

    public function testGetRateSameCurrency()
    {
        /** @var ExchangeRateManager $exchangeRateManager */
        $exchangeRateManager = Yii::$app->get('exchangeRateManager');
        $this->assertEquals(1.0, $exchangeRateManager->getRate(CurrencyEnum::RUB, CurrencyEnum::RUB));
    }

    public function testGetRateNotSet()
    {
        /** @var ExchangeRateManager $exchangeRateManager */
        $exchangeRateManager = Yii::$app->get('exchangeRateManager');
        $this->expectException(InvalidExchangeRateException::class);
        $exchangeRateManager->getRate(CurrencyEnum::RUB, CurrencyEnum::USD);
    }
}