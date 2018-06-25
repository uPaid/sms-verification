<?php

use Illuminate\Contracts\Config\Repository;
use Upaid\SmsVerification\Components\StatusMapper;
use Upaid\SmsVerification\Managers\LockOnLimitManager;
use Upaid\SmsVerification\Services\CacheManagement\SmsStorage;
use Upaid\SmsVerification\Exceptions\SmsCodeVerificationException;
use Upaid\SmsVerification\Services\MessageSending\MessageSenderInterface;
use Upaid\SmsVerification\Services\MessageComposing\MessageComposerInterface;
use Upaid\SmsVerification\Services\LocksManagement\Contracts\LockManagerInterface;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\CodeGeneratorInterface;

class LockOnLimitManagerTest extends \PHPUnit\Framework\TestCase
{
    protected $config;
    protected $statusMapper;
    protected $smsStorage;
    protected $codeGenerator;
    protected $messageComposer;
    protected $messageSender;

    public function setUp()
    {
        parent::setUp();

        $this->smsStorage = Mockery::mock(SmsStorage::class);
        $this->codeGenerator = Mockery::mock(CodeGeneratorInterface::class);
        $this->messageComposer = Mockery::mock(MessageComposerInterface::class);
        $this->messageSender = Mockery::mock(MessageSenderInterface::class);
        $this->config = Mockery::mock(Repository::class);
        $this->statusMapper = Mockery::mock(StatusMapper::class);

        $this->config->shouldReceive('get')->with('sms_verification.checks_limit')->once()->andReturn(3);
        $this->config->shouldReceive('get')->with('sms_verification.sms_code_length')->once()->andReturn(4);
        $this->config->shouldReceive('get')->with('sms_verification.actions')->once()->andReturn([\Upaid\SmsVerification\Components\Actions::ACTION_EXAMPLE]);
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\LockOnLimitManager::sendSmsCode()
     */
    public function send_sms_code_method_uses_lock_manager_to_check_if_sms_can_be_sent()
    {
        $lockManager = Mockery::mock(LockManagerInterface::class);
        $lockManager->shouldReceive('isLocked')->once()->with('example', 'PHONE')->andReturn(true);

        $lockManagerCallback = Mockery::mock(\Upaid\SmsVerification\Components\Callbacks\CreateLockManager::class);
        $lockManagerCallback->shouldReceive('__invoke')->once()->andReturn($lockManager);

        $this->config->shouldReceive('get')->with('sms_verification.callbacks.lock_manager')->once()->andReturn($lockManagerCallback);

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::TOO_MANY_SMS_ATTEMPTS)->andReturn(StatusMapper::TOO_MANY_SMS_ATTEMPTS);

        $this->assertEquals('TOO_MANY_SMS_ATTEMPTS', $this->getManager()->sendSmsCode('example', 'PHONE'));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\LockOnLimitManager::checkSmsCode()
     */
    public function check_sms_code_method_uses_lock_manager_to_check_if_lock_exists_before_verifying_code()
    {
        $lockManager = Mockery::mock(LockManagerInterface::class);
        $lockManager->shouldReceive('isLocked')->once()->with('example', 'PHONE')->andReturn(true);

        $lockManagerCallback = Mockery::mock(\Upaid\SmsVerification\Components\Callbacks\CreateLockManager::class);
        $lockManagerCallback->shouldReceive('__invoke')->once()->andReturn($lockManager);
        $this->config->shouldReceive('get')->with('sms_verification.callbacks.lock_manager')->once()->andReturn($lockManagerCallback);

        $this->smsStorage->shouldNotReceive('setContext');

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::TOO_MANY_SMS_ATTEMPTS)->andReturn(StatusMapper::TOO_MANY_SMS_ATTEMPTS);

        $this->assertEquals('TOO_MANY_SMS_ATTEMPTS', $this->getManager()->checkSmsCode('example', 'PHONE', 1234));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\LockOnLimitManager::checkSmsCode()
     */
    public function check_sms_code_method_uses_lock_manager_to_handle_reaching_checks_counter()
    {
        $this->smsStorage->shouldReceive('setContext')->once()->with('example');
        $this->smsStorage->shouldReceive('setIdentifier')->once()->with('PHONE');
        $this->smsStorage->shouldReceive('getChecksCounter')->once()->andReturn(2);
        $this->smsStorage->shouldReceive('getSmsCode')->once()->andReturn(1234);
        $this->smsStorage->shouldReceive('incrementChecksCounter')->once();

        $this->smsStorage->shouldReceive('resetChecksCounter')->once();
        $lockManager = Mockery::mock(LockManagerInterface::class);
        $lockManager->shouldReceive('isLocked')->once()->with('example', 'PHONE')->andReturn(false);
        $lockManager->shouldReceive('setLock')->once()->with('example', 'PHONE')->andReturn('TOO_MANY_SMS_ATTEMPTS');

        $lockManagerCallback = Mockery::mock(\Upaid\SmsVerification\Components\Callbacks\CreateLockManager::class);
        $lockManagerCallback->shouldReceive('__invoke')->once()->andReturn($lockManager);
        $this->config->shouldReceive('get')->with('sms_verification.callbacks.lock_manager')->once()->andReturn($lockManagerCallback);


        $this->assertEquals('TOO_MANY_SMS_ATTEMPTS', $this->getManager()->checkSmsCode('example', 'PHONE',4673));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\LockOnLimitManager::sendSmsAgain()
     */
    public function send_sms_again_in_not_available()
    {
        $this->expectException(SmsCodeVerificationException::class);
        $this->expectExceptionMessage('sendSmsAgain method is not supported in LockOnLimitManager');

        $this->getManager()->sendSmsAgain('example', 'PHONE');
    }

    /******************************************************************************************************************/

    protected function getManager()
    {
        return new LockOnLimitManager($this->smsStorage, $this->codeGenerator, $this->messageComposer, $this->messageSender, $this->statusMapper, $this->config);
    }
}
