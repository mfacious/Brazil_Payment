<?php

namespace app\common\library\payment\Request;

class PayRequest
{
    public string $orderNo;
    public string $amount;
    public string $username;
    public string $description;

    public function __construct($orderNo, $amount, $username, $description = '')
    {
        $this->orderNo = $orderNo;
        $this->amount = $amount;
        $this->username = $username;
        $this->description = $description ?: 'Default description';
    }
}