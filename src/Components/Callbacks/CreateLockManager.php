<?php

namespace Upaid\SmsVerification\Components\Callbacks;

use Upaid\SmsVerification\Services\LocksManagement\LockManagers\NullLockManager;
use Upaid\SmsVerification\Services\LocksManagement\Contracts\LockManagerInterface;

class CreateLockManager
{
    public function __invoke(string $action): LockManagerInterface
    {
        // this method should be implemented in project
        switch ($action) {
            default:
                return app(NullLockManager::class);
        }
    }

}
