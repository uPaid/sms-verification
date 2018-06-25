<?php

namespace Upaid\SmsVerification\Services\LocksManagement\LockManagers;

use Upaid\SmsVerification\Components\StatusMapper;
use Illuminate\Contracts\Config\Repository as Config;
use Upaid\SmsVerification\Services\CacheManagement\LockStorage;
use Upaid\SmsVerification\Services\LocksManagement\Contracts\LockManagerInterface;
use Upaid\SmsVerification\Services\LocksManagement\Exceptions\UnsupportedLockTypeException;
use Upaid\SmsVerification\Services\LocksManagement\Contracts\MultiTypesLockManagerInterface;

class MultiTypeLockManager extends BaseCacheLockManager implements LockManagerInterface, MultiTypesLockManagerInterface
{
    /**
     * @var array
     */
    protected $supportedLockTypes = [];

    public function __construct(LockStorage $cache, StatusMapper $statusMapper, Config $config)
    {
        parent::__construct($cache, $statusMapper, $config);

        $this->supportedLockTypes = $config->get('sms_verification.multi_lock_types');
    }

    public function setLock(string $action, string $id): string
    {
        $this->validateLockType($action);

        return parent::setLock($action, $id);
    }

    public function getLocks(string $id): array
    {
        $locks = [];
        $this->cache->setIdentifier($id);
        foreach ($this->supportedLockTypes as $type => $label) {
            $this->cache->setContext($type);
            $locks[$label] = $this->cache->exists();
        }

        return $locks;
    }

    /******************************************************************************************************************/

    /**
     * @param string $lockType
     * @throws \InvalidArgumentException
     * @throws UnsupportedLockTypeException
     */
    protected function validateLockType(string $lockType): void
    {
        if (!$lockType) {
            throw new \InvalidArgumentException('Lock type must be specified');
        }

        if (!in_array($lockType, array_keys($this->supportedLockTypes))) {
            throw new UnsupportedLockTypeException('This lock type is not supported: ' . $lockType);
        }
    }

}