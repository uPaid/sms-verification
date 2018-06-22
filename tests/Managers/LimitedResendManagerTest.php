<?php

use Illuminate\Contracts\Config\Repository;
use Upaid\SmsVerification\Components\StatusMapper;
use Upaid\SmsVerification\Managers\LimitedResendManager;
use Upaid\SmsVerification\Services\CacheManagement\SmsStorage;
use Upaid\SmsVerification\Services\MessageSending\MessageSenderInterface;
use Upaid\SmsVerification\Services\MessageComposing\MessageComposerInterface;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\CodeGeneratorInterface;

class LimitedResendManagerTest extends \PHPUnit\Framework\TestCase
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

        $this->config->shouldReceive('get')->with('sms_verification.checksLimit')->once()->andReturn(3);
        $this->config->shouldReceive('get')->with('sms_verification.smsCodeLength')->once()->andReturn(6);
        $this->config->shouldReceive('get')->with('sms_verification.actions')->once()->andReturn([\Upaid\SmsVerification\Components\Actions::ACTION_EXAMPLE]);
    }

    /*********************************************** sendSmsAgain tests ***********************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\LimitedResendManager::sendSmsAgain()
     */
    public function send_sms_again_method_resets_checks_counter_and_sends_another_sms_code_when_send_sms_again_limit_is_not_reached()
    {
        $this->config->shouldReceive('get')->with('sms_verification.sendAgainLimit')->once()->andReturn(1);

        $this->smsStorage->shouldReceive('setContext')->once()->with('example');
        $this->smsStorage->shouldReceive('setIdentifier')->once()->with('PHONE');
        $this->smsStorage->shouldReceive('getSendSmsAgainCounter')->once()->andReturn(0);
        $this->smsStorage->shouldReceive('resetChecksCounter')->once();
        $this->smsStorage->shouldReceive('incrementSendSmsAgainCounter')->once();

        $this->defineExpectationsForDoSendSmsCode();

        $this->assertEquals('SMS_CODE_SENT|2', $this->getManager()->sendSmsAgain('example', 'PHONE'));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\LimitedResendManager::sendSmsAgain()
     */
    public function send_sms_again_method_invalidates_token_when_send_sms_again_limit_is_reached()
    {
        $this->smsStorage->shouldReceive('setContext')->once()->with('example');
        $this->smsStorage->shouldReceive('setIdentifier')->once()->with('PHONE');
        $this->smsStorage->shouldReceive('getSendSmsAgainCounter')->once()->andReturn(1);
        $this->config->shouldReceive('get')->once()->with('sms_verification.sendAgainLimit')->andReturn(1);
        $this->config->shouldReceive('get')->once()->with('sms_verification.callbacks.overLimit')->andReturn(\Upaid\SmsVerification\Components\Callbacks\OverLimit::class);

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::TOO_MANY_SMS_ATTEMPTS)->andReturn(StatusMapper::TOO_MANY_SMS_ATTEMPTS);

        $this->assertEquals('TOO_MANY_SMS_ATTEMPTS', $this->getManager()->sendSmsAgain('example', 'PHONE'));
    }

    /*********************************************** checkSmsCode tests ***********************************************/

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\LimitedResendManager::checkSmsCode()
     */
    public function check_sms_code_method_resets_checks_counter_and_sends_another_sms_code_on_handling_reaching_failed_checks_limit_when_send_sms_again_limit_is_not_reached()
    {
        $this->defineExpectationsForCheckSmsCodeMethod();

        $this->config->shouldReceive('get')->once()->with('sms_verification.sendAgainLimit')->andReturn(1);
        $this->smsStorage->shouldReceive('getSendSmsAgainCounter')->once()->andReturn(0);
        $this->smsStorage->shouldReceive('resetChecksCounter')->once();
        $this->smsStorage->shouldReceive('incrementSendSmsAgainCounter')->once();
        $this->defineExpectationsForDoSendSmsCode();

        $this->assertEquals('SMS_CODE_SENT|2', $this->getManager()->checkSmsCode('example', 'PHONE', 467322));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\LimitedResendManager::checkSmsCode()
     */
    public function check_sms_code_method_invalidates_token_on_handling_reaching_failed_checks_limit_when_send_sms_again_limit_is_reached_and_token_is_present_in_arguments()
    {
        $this->defineExpectationsForCheckSmsCodeMethod();
        $this->config->shouldReceive('get')->once()->with('sms_verification.sendAgainLimit')->andReturn(1);
        $this->config->shouldReceive('get')->once()->with('sms_verification.callbacks.overLimit')->andReturn(\Upaid\SmsVerification\Components\Callbacks\OverLimit::class);

        $this->smsStorage->shouldReceive('getSendSmsAgainCounter')->once()->andReturn(1);

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::TOO_MANY_SMS_ATTEMPTS)->andReturn(StatusMapper::TOO_MANY_SMS_ATTEMPTS);

        $this->assertEquals('TOO_MANY_SMS_ATTEMPTS', $this->getManager()->checkSmsCode('example', 'PHONE', 467322));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\LimitedResendManager::checkSmsCode()
     */
    public function check_sms_code_method_just_returns_TOO_MANY_SMS_ATTEMPTS_on_handling_reaching_failed_checks_limit_when_send_sms_again_limit_is_reached_and_token_is_not_present_in_arguments()
    {
        $this->defineExpectationsForCheckSmsCodeMethod();
        $this->config->shouldReceive('get')->once()->with('sms_verification.sendAgainLimit')->andReturn(1);
        $this->config->shouldReceive('get')->once()->with('sms_verification.callbacks.overLimit')->andReturn(\Upaid\SmsVerification\Components\Callbacks\OverLimit::class);

        $this->smsStorage->shouldReceive('getSendSmsAgainCounter')->once()->andReturn(1);

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::TOO_MANY_SMS_ATTEMPTS)->andReturn(StatusMapper::TOO_MANY_SMS_ATTEMPTS);

        $this->assertEquals('TOO_MANY_SMS_ATTEMPTS', $this->getManager()->checkSmsCode('example', 'PHONE', 467322));
    }

    /******************************************************************************************************************/

    protected function defineExpectationsForCheckSmsCodeMethod()
    {
        $this->smsStorage->shouldReceive('setContext')->once()->with('example');
        $this->smsStorage->shouldReceive('setIdentifier')->once()->with('PHONE');

        $this->smsStorage->shouldReceive('getChecksCounter')->once()->andReturn(2);
        $this->smsStorage->shouldReceive('getSmsCode')->once()->andReturn(1234);
        $this->smsStorage->shouldReceive('incrementChecksCounter')->once();
    }

    protected function defineExpectationsForDoSendSmsCode()
    {
        $this->codeGenerator->shouldReceive('generateCode')->once()->with(6)->andReturn(123456);
        $this->smsStorage->shouldReceive('saveSmsCode')->once()->with(123456);

        $this->messageComposer->shouldReceive('compose')->once()->with('example', '123456', [])->andReturn('Your code is 123456');
        $this->messageSender->shouldReceive('send')->once()->with('Your code is 123456', 'PHONE')->andReturn('SMS_CODE_SENT|2');
    }

    protected function getManager()
    {
        return new LimitedResendManager($this->smsStorage, $this->codeGenerator, $this->messageComposer, $this->messageSender, $this->statusMapper, $this->config);
    }

}
