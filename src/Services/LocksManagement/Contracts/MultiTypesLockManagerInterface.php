<?php

namespace Upaid\SmsVerification\Services\LocksManagement\Contracts;

interface MultiTypesLockManagerInterface
{
    public function getLocks(string $id): array;
}
