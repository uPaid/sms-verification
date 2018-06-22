<?php

namespace Upaid\SmsVerification\Services\LocksManagement\Contracts;

interface LockManagerInterface
{
    public function setLock(string $action, string $identifier): string;

    public function isLocked(string $action, string $identifier): bool;
}
