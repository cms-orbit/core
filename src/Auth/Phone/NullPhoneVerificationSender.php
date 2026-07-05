<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Phone;

class NullPhoneVerificationSender implements PhoneVerificationSender
{
    public function send(string $phone, string $code, string $channel): void
    {
        throw new \RuntimeException(__('휴대폰 인증 발송기가 설정되지 않았습니다. phone 로그인용 companion package를 설치하거나 sender 구현체를 바인딩해 주세요.'));
    }
}
