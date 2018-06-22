<?php

use Upaid\SmsVerification\Services\CacheManagement\SmsCache;

class SmsCacheTest extends \PHPUnit\Framework\TestCase
{
    /*----------------------------------------------------------------------------------------------------------------*/
    /*--------- we do not test here if keys are generated properly, that's covered in contextual cache tests ---------*/
    /*----------------------------------------------------------------------------------------------------------------*/

    /********************************************** Checks counter tests **********************************************/

    protected $config;
    protected $cache;

    public function setUp()
    {
        parent::setUp();

        $this->config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $this->config->shouldReceive('get')->with('sms_verification.cacheLifeTime')->once()->andReturn(15);

        $this->cache = Mockery::mock(Illuminate\Contracts\Cache\Repository::class);
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache::getChecksCounter()
     */
    public function get_checks_counter_method_fetches_value_from_cache()
    {
        $this->cache->shouldReceive('get')->once()->andReturn(5);

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $this->assertSame(5, $smsCache->getChecksCounter());
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache::incrementChecksCounter()
     */
    public function increment_checks_counter_method_puts_a_new_value_in_cache_if_there_is_none_and_set_it_to_1()
    {
        $this->cache->shouldReceive('has')->once()->andReturn(false);
        $this->cache->shouldReceive('put')->once()->andReturn(1);

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $this->assertSame(1, $smsCache->incrementChecksCounter());
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache::incrementChecksCounter()
     */
    public function increment_checks_counter_method_increments_counter_in_cache_if_counter_exists()
    {
        $this->cache->shouldReceive('has')->once()->andReturn(true);
        $this->cache->shouldReceive('increment')->once()->andReturn(2);

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $this->assertSame(2, $smsCache->incrementChecksCounter());
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache:resetChecksCounter()
     */
    public function reset_checks_counter_method_removes_counter_from_cache()
    {
        $this->cache->shouldReceive('forget')->once();

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $smsCache->resetChecksCounter();
    }

    /************************************************* SMS code tests *************************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache::saveSmsCode()
     */
    public function save_sms_code_method_puts_code_in_cache()
    {
        $this->cache->shouldReceive('put')->once();

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $smsCache->saveSmsCode('1234');
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache::getSmsCode()
     */
    public function get_sms_code_method_fetches_code_from_cache()
    {
        $this->cache->shouldReceive('get')->once();

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $smsCache->getSmsCode();
    }

    /******************************************* sendSmsAgain counter tests *******************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache::getSendSmsAgainCounter()
     */
    public function get_sms_again_counter_method_fetches_counter_from_cache()
    {
        $this->cache->shouldReceive('get')->once()->andReturn(3);

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $this->assertSame(3, $smsCache->getSendSmsAgainCounter());
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache::incrementSendSmsAgainCounter()
     */
    public function increment_send_sms_again_counter_method_puts_a_new_value_in_cache_if_there_is_none_and_set_it_to_1()
    {
        $this->cache->shouldReceive('has')->once()->andReturn(false);
        $this->cache->shouldReceive('put')->once()->andReturn(1);

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $this->assertSame(1, $smsCache->incrementSendSmsAgainCounter());
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache::incrementSendSmsAgainCounter()
     */
    public function increment_send_sms_again_counter_method_increments_counter_in_cache_if_counter_exists()
    {
        $this->cache->shouldReceive('has')->once()->andReturn(true);
        $this->cache->shouldReceive('increment')->once()->andReturn(2);

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $this->assertSame(2, $smsCache->incrementSendSmsAgainCounter());
    }

    /************************************************* flushAll tests *************************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CacheManagement\SmsCache::flushAll()
     */
    public function flush_all_method_removes_all_supported_keys_from_cache()
    {
        $this->cache->shouldReceive('forget')->times(3);

        $smsCache = new SmsCache($this->cache, $this->config);
        $smsCache->setContext('user action');
        $smsCache->setIdentifier('user phone number');

        $smsCache->flushAll();
    }
}
