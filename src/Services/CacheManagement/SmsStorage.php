<?php

namespace Upaid\SmsVerification\Services\CacheManagement;

interface SmsStorage
{
    public function getChecksCounter(): int;

    public function incrementChecksCounter(): int;

    public function resetChecksCounter(): void;

    public function getSmsCode(): string;

    public function saveSmsCode(string $smsCode): void;

    public function getSendSmsAgainCounter(): int;

    public function incrementSendSmsAgainCounter(): int;

    public function flushAll(): void;
}
