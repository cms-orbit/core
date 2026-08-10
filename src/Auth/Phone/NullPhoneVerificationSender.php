<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Phone;

class NullPhoneVerificationSender implements PhoneVerificationSender
{
    public function send(string $phone, string $code, string $channel): void
    {
        throw new \RuntimeException(__('No phone verification sender is configured. Install a companion package for phone login, or bind a sender implementation.'));
    }
}
