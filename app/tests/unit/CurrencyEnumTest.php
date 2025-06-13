<?php

namespace tests\unit;

use app\models\banking\CurrencyEnum;
use Codeception\Test\Unit;

class CurrencyEnumTest extends Unit
{
    public function testCurrencyEnumValues()
    {
        $cases = CurrencyEnum::cases();
        $this->assertTrue(in_array(CurrencyEnum::RUB, $cases, true));
        $this->assertTrue(in_array(CurrencyEnum::USD, $cases, true));
        $this->assertTrue(in_array(CurrencyEnum::EUR, $cases, true));
    }
}