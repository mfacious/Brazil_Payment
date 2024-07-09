<?php

namespace app\common\library\payment;

use app\common\library\payment\Request\PayoutRequest;
use app\common\library\payment\Request\PayRequest;

class PaymentContext
{
    private PaymentStrategy $strategy;

    public function __construct(PaymentStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function pay(PayRequest $order):PaymentResponse
    {
        return $this->strategy->pay($order);
    }

    public function payout(PayoutRequest $order):PaymentResponse
    {
        return $this->strategy->payout($order);
    }
}