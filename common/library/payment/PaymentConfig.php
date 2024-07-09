<?php

namespace app\common\library\payment;

class PaymentConfig
{
    public string $merchantName;
    public string $merchantCode;
    public string $merchantPrivateKey;
    public string $platPublicKey;
    public string $channelCode;
    public function __construct(string $merchantCode, string $merchantName, string $merchantPrivateKey, string $channelCode)
    {
        $this->merchantCode = $merchantCode;
        $this->merchantName = $merchantName;
        $this->merchantPrivateKey = $merchantPrivateKey;
        $this->channelCode = $channelCode;
    }

    public function setPlatPublicKey($platPublicKey)
    {
        $this->platPublicKey = $platPublicKey;
        return $this;
    }
}