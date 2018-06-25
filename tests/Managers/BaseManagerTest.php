<?php

use Illuminate\Contracts\Config\Repository;
use Upaid\SmsVerification\Managers\BaseManager;
use Upaid\SmsVerification\Components\StatusMapper;
use Upaid\SmsVerification\Services\CacheManagement\SmsStorage;
use Upaid\SmsVerification\Services\MessageSending\MessageSenderInterface;
use Upaid\SmsVerification\Services\MessageComposing\MessageComposerInterface;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\CodeGeneratorInterface;

class BaseManagerTest extends \PHPUnit\Framework\TestCase
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

        $this->smsStorage->shouldReceive('setContext')->once()->with('example');
        $this->smsStorage->shouldReceive('setIdentifier')->once()->with('PHONE');

        $this->config->shouldReceive('get')->with('sms_verification.checks_limit')->once()->andReturn(3);
        $this->config->shouldReceive('get')->with('sms_verification.sms_code_length')->once()->andReturn(4);
        $this->config->shouldReceive('get')->with('sms_verification.actions')->once()->andReturn([\Upaid\SmsVerification\Components\Actions::ACTION_EXAMPLE]);
    }

    /************************************************ sendSmsCode tests ***********************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\BaseManager::sendSmsCode()
     */
    public function send_sms_code_method_does_not_send_sms_and_returns_TOO_MANY_SMS_ATTEMPTS_if_limit_has_been_reached()
    {
        $this->smsStorage->shouldReceive('getChecksCounter')->once()->andReturn(3);
        $this->statusMapper->shouldReceive('map')->with(StatusMapper::TOO_MANY_SMS_ATTEMPTS)->once()->andReturn(StatusMapper::TOO_MANY_SMS_ATTEMPTS);
        $this->assertEquals('TOO_MANY_SMS_ATTEMPTS', $this->getManager()->sendSmsCode('example', 'PHONE'));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\BaseManager::sendSmsCode()
     */
    public function send_sms_code_method_generates_and_sends_sms_if_limit_of_failed_attempts_is_not_reached_yet()
    {
        $this->smsStorage->shouldReceive('getChecksCounter')->once()->andReturn(0);
        $this->codeGenerator->shouldReceive('generateCode')->once()->with(4)->andReturn(1234);
        $this->smsStorage->shouldReceive('saveSmsCode')->once()->with(1234);

        $this->messageComposer->shouldReceive('compose')->once()->with('example', '1234', ['param' => 'aaa'])->andReturn('Your code is 1234');
        $this->messageSender->shouldReceive('send')->once()->with('Your code is 1234', 'PHONE')->andReturn('SMS_CODE_SENT');

        $this->assertEquals('SMS_CODE_SENT', $this->getManager()->sendSmsCode('example', 'PHONE', ['param' => 'aaa']));
    }

    /*********************************************** checkSmsCode tests ***********************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\BaseManager::checkSmsCode()
     */
    public function check_sms_code_method_returns_TOO_MANY_SMS_ATTEMPTS_if_limit_has_been_exceeded()
    {
        $this->smsStorage->shouldReceive('getChecksCounter')->once()->andReturn(3);
        $this->statusMapper->shouldReceive('map')->with(StatusMapper::TOO_MANY_SMS_ATTEMPTS)->once()->andReturn(StatusMapper::TOO_MANY_SMS_ATTEMPTS);
        $this->assertEquals('TOO_MANY_SMS_ATTEMPTS', $this->getManager()->checkSmsCode('example', 'PHONE', 1234));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\BaseManager::checkSmsCode()
     */
    public function check_sms_code_method_resets_checks_counter_and_returns_true_if_we_are_below_checks_limit_and_code_is_valid()
    {
        $this->smsStorage->shouldReceive('getChecksCounter')->once()->andReturn(0);
        $this->smsStorage->shouldReceive('getSmsCode')->once()->andReturn(1234);
        $this->smsStorage->shouldReceive('resetChecksCounter')->once();

        $this->statusMapper->shouldReceive('map')->with(StatusMapper::CODE_IS_VALID)->once()->andReturn(StatusMapper::CODE_IS_VALID);

        $this->assertEquals(StatusMapper::CODE_IS_VALID, $this->getManager()->checkSmsCode('example', 'PHONE', 1234));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\BaseManager::checkSmsCode()
     */
    public function check_sms_code_method_delegates_handling_reaching_limit_to_child_class()
    {
        $this->smsStorage->shouldReceive('getChecksCounter')->once()->andReturn(2);
        $this->smsStorage->shouldReceive('getSmsCode')->once()->andReturn(1234);
        $this->smsStorage->shouldReceive('incrementChecksCounter')->once();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You have to implement this method in a child class');

        $this->getManager()->checkSmsCode('example', 'PHONE', 4673);
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\BaseManager::checkSmsCode()
     */
    public function check_sms_code_method_returns_false_if_limit_is_not_reached_yet_and_code_is_invalid()
    {
        $this->smsStorage->shouldReceive('getChecksCounter')->once()->andReturn(0);
        $this->smsStorage->shouldReceive('getSmsCode')->once()->andReturn(1234);
        $this->smsStorage->shouldReceive('incrementChecksCounter')->once();

        $this->statusMapper->shouldReceive('map')->with(StatusMapper::CODE_IS_INVALID)->once()->andReturn(StatusMapper::CODE_IS_INVALID);

        $this->assertEquals(StatusMapper::CODE_IS_INVALID, $this->getManager()->checkSmsCode('example', 'PHONE', 4673));
    }

    /**************************************** flushPendingSmsValidation tests *****************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\BaseManager::flushPendingSmsValidation()
     */
    public function flush_pending_sms_validation_method_removes_all_items_from_cache_for_all_actions()
    {
        $this->smsStorage->shouldReceive('setContext')->once(); // 'example' context has been already handled in constructor
        $this->smsStorage->shouldReceive('flushAll')->once();

        $this->getManager()->flushPendingSmsValidation('PHONE');
    }

    /******************************************************************************************************************/

    protected function getManager()
    {
        return $this->getMockForAbstractClass(BaseManager::class, [
            $this->smsStorage, $this->codeGenerator, $this->messageComposer, $this->messageSender, $this->statusMapper, $this->config
        ]);
    }

}
