<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Phone;

interface PhoneVerificationSender
{
    public function send(string $phone, string $code, string $channel): void;
}
