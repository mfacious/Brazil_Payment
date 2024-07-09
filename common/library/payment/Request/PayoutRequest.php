<?php

namespace app\common\library\payment\Request;

class PayoutRequest
{
    public string $orderNo;
    public string $amount;
    public string $username;
    public string $cardType;
    public string $pixAccount;
    public string $cpf;
    public string $walletId;
    public string $description;

    public function __construct($orderNo, $amount, $username, $pixAccount, $cpf)
    {
        $this->orderNo = $orderNo;
        $this->amount = $amount;
        $this->username = $username;
        $this->pixAccount = $pixAccount;
        $this->cpf = $cpf;
    }

    public function setExtraInfo($cardType = '', $walletId = '', $description = '')
    {
        if ($cardType) {
            $this->cardType = $cardType;
        }

        if ($walletId) {
            $this->walletId = $walletId;
        }

        if ($description) {
            $this->description = $description;
        }
        return $this;
    }
}