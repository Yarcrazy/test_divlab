<?php

namespace app\models\banking;

class Bank
{
    public function openAccount(): MultiCurrencyAccount
    {
        return new MultiCurrencyAccount();
    }
}