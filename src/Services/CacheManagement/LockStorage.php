<?php

namespace Upaid\SmsVerification\Services\CacheManagement;

interface LockStorage
{
    public function lock(): void;

    public function exists(): bool;

    public function unlock(): void;
}
