<?php

namespace app\common\library\payment;

use app\common\library\payment\Exception\BusinessException;
use app\common\library\payment\Strategy\CbpayStrategy;
use app\common\library\payment\Strategy\ToppayStrategy;
use app\common\library\payment\Strategy\WinpayStrategy;

class PaymentFactory
{
    const CHANNEL_WINPAY = 'WINPAY';
    const CHANNEL_TOPPAY = 'TOPPAY';
    const CHANNEL_CBPAY = 'CBPAY';

    public static function getInstance(PaymentConfig $payConfig)
    {
        switch($payConfig->channelCode){
            case self::CHANNEL_WINPAY:
                return new WinpayStrategy($payConfig);
            case self::CHANNEL_CBPAY:
                return new CbpayStrategy($payConfig);
            case self::CHANNEL_TOPPAY:
                return new ToppayStrategy($payConfig);
        }

        throw new BusinessException('Unexpected payment channel');
    }
}