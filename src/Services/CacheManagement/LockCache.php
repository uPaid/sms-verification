<?php

namespace Upaid\SmsVerification\Services\CacheManagement;

use Upaid\ContextualKeys\ContextualKeysTrait;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

class LockCache implements LockStorage
{
    use ContextualKeysTrait;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Minutes
     * @var int
     */
    protected $lifetime = 15;

    public function __construct(Cache $cache, Config $config)
    {
        $this->cache = $cache;
        $this->lifetime = $config->get('sms_verification.lock_life_time');
    }

    public function lock(): void
    {
        $this->cache->put($this->generateFullKey(), true, $this->lifetime);
    }

    public function exists(): bool
    {
        return (bool) $this->cache->has($this->generateFullKey());
    }

    public function unlock(): void
    {
        $this->cache->forget($this->generateFullKey());
    }

    public function setLifetime(int $minutes): void
    {
        $this->lifetime = $minutes;
    }
}
