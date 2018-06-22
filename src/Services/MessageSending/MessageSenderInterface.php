<?php

namespace Upaid\SmsVerification\Services\MessageSending;

interface MessageSenderInterface
{
    public function send(string $message, string $phone): string;
}
