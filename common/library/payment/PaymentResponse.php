<?php

namespace app\common\library\payment;

class PaymentResponse
{
    public string $successFlag;
    public bool $isSuccess;
    public string $respMessage;
    public string $platOrderNum;
    public string $payURL;
    public string $originJson;
}