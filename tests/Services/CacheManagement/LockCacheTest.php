<?php

use Upaid\SmsVerification\Services\CacheManagement\LockCache;

class LockCacheTest extends \PHPUnit\Framework\TestCase
{
    /*----------------------------------------------------------------------------------------------------------------*/
    /*--------- we do not test here if keys are generated properly, that's covered in contextual cache tests ---------*/
    /*----------------------------------------------------------------------------------------------------------------*/

    protected $config;
    protected $cache;

    public function setUp()
    {
        parent::setUp();

        $this->config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $this->config->shouldReceive('get')->with('sms_verification.lockLifeTime')->once()->andReturn(15);

        $this->cache = Mockery::mock(Illuminate\Contracts\Cache\Repository::class);
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\LockCache::lock()
     */
    public function lock_method_puts_lock_in_cache()
    {
        $this->cache->shouldReceive('put')->once();

        $lockCache = new LockCache($this->cache, $this->config);
        $lockCache->setContext('user action');
        $lockCache->setIdentifier('user phone number');
        $lockCache->lock();
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\LockCache::exists()
     */
    public function exists_method_checks_if_lock_is_set_in_cache()
    {
        $this->cache->shouldReceive('has')->once();

        $lockCache = new LockCache($this->cache, $this->config);
        $lockCache->setContext('user action');
        $lockCache->setIdentifier('user phone number');
        $lockCache->exists();
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\LockCache::unlock()
     */
    public function unlock_method_removes_lock_from_cache()
    {
        $this->cache->shouldReceive('forget')->once();

        $lockCache = new LockCache($this->cache, $this->config);
        $lockCache->setContext('user action');
        $lockCache->setIdentifier('user phone number');
        $lockCache->unlock();
    }

}
