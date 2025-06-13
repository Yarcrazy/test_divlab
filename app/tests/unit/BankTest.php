<?php

namespace tests\unit;

use app\models\banking\Bank;
use app\models\banking\MultiCurrencyAccount;
use Codeception\Test\Unit;

class BankTest extends Unit
{
    public function testOpenAccount()
    {
        $bank = new Bank();
        $account = $bank->openAccount();
        $this->assertInstanceOf(MultiCurrencyAccount::class, $account);
    }
}