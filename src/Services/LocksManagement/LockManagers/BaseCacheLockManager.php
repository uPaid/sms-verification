<?php

namespace Upaid\SmsVerification\Services\LocksManagement\LockManagers;

use Upaid\SmsVerification\Components\StatusMapper;
use Illuminate\Contracts\Config\Repository as Config;
use Upaid\SmsVerification\Services\CacheManagement\LockStorage;
use Upaid\SmsVerification\Services\LocksManagement\Contracts\LockManagerInterface;

abstract class BaseCacheLockManager implements LockManagerInterface
{
    /**
     * @var LockStorage
    */
    protected $cache;

    /**
     * @var StatusMapper
     */
    protected $statusMapper;

    /**
     * Minutes
     * @var integer
     */
    protected $lockTime = 0;

    public function __construct(LockStorage $cache, StatusMapper $statusMapper, Config $config)
    {
        $this->cache = $cache;
        $this->statusMapper = $statusMapper;

        $this->lockTime = $config->get('sms_verification.lockLifeTime');
    }

    public function setLock(string $action, string $id): string
    {
        if ($this->lockTime > 0) {
            $this->cache->setLifetime($this->lockTime);
            $this->cache->setContext($action);
            $this->cache->setIdentifier($id);
            $this->cache->lock();
        }

        return $this->getResponse();
    }

    public function isLocked(string $action, string $id): bool
    {
        $this->cache->setContext($action);
        $this->cache->setIdentifier($id);

        return $this->cache->exists();
    }

    public function setLockTime(int $minutes): void
    {
        $this->lockTime = $minutes;
    }

    /******************************************************************************************************************/

    protected function getResponse(): string
    {
        return $this->statusMapper->map(StatusMapper::TOO_MANY_SMS_ATTEMPTS);
    }
}
