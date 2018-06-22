<?php

use Illuminate\Contracts\Config\Repository;
use Upaid\SmsVerification\Components\StatusMapper;
use Upaid\SmsVerification\Managers\UnlimitedResendManager;
use Upaid\SmsVerification\Services\CacheManagement\SmsStorage;
use Upaid\SmsVerification\Services\MessageSending\MessageSenderInterface;
use Upaid\SmsVerification\Services\MessageComposing\MessageComposerInterface;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\CodeGeneratorInterface;

class UnlimitedResendManagerTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\UnlimitedResendManager::sendSmsAgain()
     */
    public function send_sms_again_method_resets_checks_counter_and_sends_sms()
    {
        $this->smsStorage->shouldReceive('setContext')->once()->with('example');
        $this->smsStorage->shouldReceive('setIdentifier')->once()->with('PHONE');
        $this->smsStorage->shouldReceive('resetChecksCounter')->once();

        $this->defineExpectationsForDoSendSmsCode();

        $this->assertEquals('SMS_CODE_SENT', $this->getManager()->sendSmsAgain('example', 'PHONE'));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Managers\UnlimitedResendManager::checkSmsCode()
     */
    public function check_sms_code_method_resets_checks_counter_and_sends_another_sms_code_on_handling_reaching_failed_checks_limit()
    {
        $this->defineExpectationsForCheckSmsCodeMethod();

        $this->smsStorage->shouldReceive('resetChecksCounter')->once();

        $this->defineExpectationsForDoSendSmsCode('SMS_CODE_SENT|3');

        $this->assertEquals('SMS_CODE_SENT|3', $this->getManager()->checkSmsCode('example','PHONE',467322, 'token'));
    }

    /******************************************************************************************************************/

    protected function defineExpectationsForCheckSmsCodeMethod()
    {
        $checksCounter = 2;
        $this->smsStorage->shouldReceive('setContext')->once()->with('example');
        $this->smsStorage->shouldReceive('setIdentifier')->once()->with('PHONE');

        $this->smsStorage->shouldReceive('getChecksCounter')->once()->andReturn($checksCounter);
        $this->smsStorage->shouldReceive('getSmsCode')->once()->andReturn(1234);
        $this->smsStorage->shouldReceive('incrementChecksCounter')->once();
    }

    protected function defineExpectationsForDoSendSmsCode($senderResponse = 'SMS_CODE_SENT')
    {
        $this->codeGenerator->shouldReceive('generateCode')->once()->with(6)->andReturn(123456);
        $this->smsStorage->shouldReceive('saveSmsCode')->once()->with(123456);

        $this->messageComposer->shouldReceive('compose')->once()->with('example', '123456', [])->andReturn('Your code is 123456');
        $this->messageSender->shouldReceive('send')->once()->with('Your code is 123456', 'PHONE')->andReturn($senderResponse);
    }

    protected function getManager()
    {
        return new UnlimitedResendManager($this->smsStorage, $this->codeGenerator, $this->messageComposer, $this->messageSender, $this->statusMapper, $this->config);
    }

}
