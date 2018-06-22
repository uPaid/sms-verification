<?php

namespace Upaid\SmsVerification\Contracts;

interface SmsManagerInterface
{
    public function sendSmsCode(string $action, string $phone, array $messageTranslationPlaceholders = []): string;

    public function checkSmsCode(string $action, string $phone, string $code): string;

    public function sendSmsAgain(string $action, string $phone, array $messageTranslationPlaceholders = []): string;

    public function flushPendingSmsValidation(string $id): void;
}
