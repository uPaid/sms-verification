<?php

use Upaid\SmsVerification\Components\StatusMapper;
use Upaid\SmsVerification\Services\MessageSending\DummyMessageSender;

class DummyMessageSenderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\MessageSending\DummyMessageSender::send()
     */
    public function it_executes_log_callback()
    {
        $config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $config->shouldReceive('get')->with('sms_verification.log_message.supported_method')->andReturn('message template');
        $config->shouldReceive('get')->with('sms_verification.callbacks.log')->andReturn(new \stdClass());
        $statusMapper = Mockery::mock(StatusMapper::class);
        $statusMapper->shouldReceive('map')->once()->with(StatusMapper::SMS_CODE_SENT)->andReturn(StatusMapper::SMS_CODE_SENT);

        $sender = $this->getMockBuilder(DummyMessageSender::class)
            ->setMethods(['replacePlaceholders', 'executeCallback'])
            ->setConstructorArgs([$config, $statusMapper])
            ->getMock();
        $sender->expects($this->once())->method('replacePlaceholders')->willReturn('message to log');
        $sender->expects($this->once())->method('executeCallback')->willReturn('message to log');

        $this->assertEquals('SMS_CODE_SENT', $sender->send('message to log', 'PHONE'));
    }
}
