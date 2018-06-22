<?php

namespace Upaid\SmsVerification\Services\LocksManagement\LockManagers;

use Upaid\SmsVerification\Components\StatusMapper;
use Upaid\SmsVerification\Services\LocksManagement\Contracts\LockManagerInterface;

class NullLockManager implements LockManagerInterface
{
    /**
     * @var StatusMapper
     */
    protected $statusMapper;

    public function __construct(StatusMapper $statusMapper)
    {
        $this->statusMapper = $statusMapper;
    }

    public function setLock(string $action, string $identifier): string
    {
        return $this->statusMapper->map(StatusMapper::TOO_MANY_SMS_ATTEMPTS);
    }

    public function isLocked(string $action, string $identifier): bool
    {
        return false;
    }
}
