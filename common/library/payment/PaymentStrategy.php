<?php

namespace app\common\library\payment;

use app\common\library\payment\Request\PayoutRequest;
use app\common\library\payment\Request\PayRequest;

interface PaymentStrategy
{
    public function pay(PayRequest $req): PaymentResponse;

    public function payout(PayoutRequest $req): PaymentResponse;
}