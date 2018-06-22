<?php

use Upaid\SmsVerification\Components\StatusMapper;
use Upaid\SmsVerification\Services\CacheManagement\LockStorage;
use Upaid\SmsVerification\Services\LocksManagement\LockManagers\MultiTypeLockManager;
use Upaid\SmsVerification\Services\LocksManagement\Exceptions\UnsupportedLockTypeException;

class MultiTypeLockManagerTest extends \PHPUnit\Framework\TestCase
{
    protected $lockStorage;
    protected $config;
    protected $statusMapper;

    public function setUp()
    {
        parent::setUp();
        $this->lockStorage = Mockery::mock(LockStorage::class);
        $this->config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $this->config->shouldReceive('get')->with('sms_verification.lockLifeTime')->once()->andReturn(15);
        $this->config->shouldReceive('get')->with('sms_verification.multiLockTypes')->once()->andReturn([
            \Upaid\SmsVerification\Components\Actions::ACTION_EXAMPLE => 'example',
            \Upaid\SmsVerification\Components\Actions::ACTION_EXAMPLE_2 => 'example_2'
        ]);
        $this->statusMapper = Mockery::mock(StatusMapper::class);
    }

    /************************************************** setLock tests *************************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\LocksManagement\LockManagers\MultiTypeLockManager::setLock()
     */
    public function it_puts_lock_in_storage_if_config_says_so()
    {
        $this->lockStorage->shouldReceive('setLifetime')->with('60');
        $this->lockStorage->shouldReceive('setContext')->with('example');
        $this->lockStorage->shouldReceive('setIdentifier')->with('48777777777');
        $this->lockStorage->shouldReceive('lock');
        $this->lockStorage->shouldReceive('setLifetime')->once()->with(15);

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::TOO_MANY_SMS_ATTEMPTS)->andReturn(StatusMapper::TOO_MANY_SMS_ATTEMPTS);

        $lockManager = new MultiTypeLockManager($this->lockStorage, $this->statusMapper, $this->config);
        $lockManager->setLock('example', '48777777777');
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\LocksManagement\LockManagers\MultiTypeLockManager::setLock()
     */
    public function it_does_not_put_lock_in_storage_if_config_does_not_enable_it()
    {
        $this->lockStorage->shouldReceive('setLifetime')->with('0');
        $this->lockStorage->shouldNotReceive('lock')->once();
        $this->lockStorage->shouldReceive('setLifetime')->once()->with(15);
        $this->lockStorage->shouldReceive('setContext')->with('example');
        $this->lockStorage->shouldReceive('setIdentifier')->with('48777777777');

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::TOO_MANY_SMS_ATTEMPTS)->andReturn(StatusMapper::TOO_MANY_SMS_ATTEMPTS);

        $lockManager = new MultiTypeLockManager($this->lockStorage, $this->statusMapper, $this->config);
        $lockManager->setLock('example', '48777777777');
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\LocksManagement\LockManagers\MultiTypeLockManager::setLock()
     */
    public function it_prevents_setting_unsupported_lock_type()
    {
        $this->expectException(UnsupportedLockTypeException::class);

        $lockManager = new MultiTypeLockManager($this->lockStorage, $this->statusMapper, $this->config);
        $lockManager->setLock('unsupported_type', '48777777777');
    }

    /************************************************* isLocked tests *************************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\LocksManagement\LockManagers\MultiTypeLockManager::isLocked()
     * @dataProvider getLockStatuses
     * @param bool $lockStatus
     */
    public function it_properly_fetches_lock_status_from_storage(bool $lockStatus)
    {
        $this->lockStorage->shouldReceive('setLifetime')->with('60');
        $this->lockStorage->shouldReceive('setContext')->with('example');
        $this->lockStorage->shouldReceive('setIdentifier')->with('48777777777');
        $this->lockStorage->shouldReceive('exists')->andReturn($lockStatus);

        $lockManager = new MultiTypeLockManager($this->lockStorage, $this->statusMapper, $this->config);
        $this->assertEquals($lockStatus, $lockManager->isLocked('example', '48777777777'));
    }

    /************************************************* getLocks tests *************************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\LocksManagement\LockManagers\MultiTypeLockManager::getLocks()
     * @dataProvider getLockStatusSets
     * @param bool $testLockStatus
     * @param bool $test2LockStatus
     */
    public function it_properly_returns_an_array_of_locks($testLockStatus, $test2LockStatus)
    {
        $this->lockStorage->shouldReceive('setLifetime')->once()->with(15);
        $this->lockStorage->shouldReceive('setIdentifier')->once()->with('48777777777');

        $this->lockStorage->shouldReceive('setContext')->once()->with('example');
        $this->lockStorage->shouldReceive('setContext')->once()->with('example_2');
        $this->lockStorage->shouldReceive('exists')->andReturn($testLockStatus, $test2LockStatus);

        $lockManager = new MultiTypeLockManager($this->lockStorage, $this->statusMapper, $this->config);
        $locks = $lockManager->getLocks('48777777777');
        $this->assertEquals([
            'example' => $testLockStatus,
            'example_2' => $test2LockStatus
        ], $locks);
    }

    /******************************************************************************************************************/

    /**
     * get lock statuses
     */
    public function getLockStatuses()
    {
        return [[true], [false]];
    }

    /**
     * get locks combinations
     */
    public function getLockStatusSets()
    {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false]
        ];
    }
}
