<?php

use Upaid\SmsVerification\Components\Callbacks\MessageComposer;

class MessageComposerTest extends \PHPUnit\Framework\TestCase
{
    protected $config;
    protected $composerCallback;

    public function setUp()
    {
        parent::setUp();
        $this->config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $this->composerCallback = Mockery::mock(MessageComposer::class);
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Components\Callbacks\MessageComposer::__invoke()
     */
    public function it_returns_code_if_key_is_not_supported()
    {
        $this->assertEquals('1234', (new MessageComposer)->__invoke([], 'unsupported', '1234'));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Components\Callbacks\MessageComposer::__invoke()
     */
    public function it_returns_code_if_key_is_supported_but_there_exists_only_a_default_translation()
    {
        $translator  = Mockery::mock(Illuminate\Contracts\Translation\Translator::class);
        $translator->shouldReceive('trans')->once()->with('sms.message.example', [
            'code' => '1234',
            'datetime' => date('Y-m-d')
        ])->andReturn('sms.message.example');

        $callback = $this->getMockBuilder(MessageComposer::class)->setMethods(['makeTranslator'])->getMock();
        $callback->expects($this->once())->method('makeTranslator')->willReturn($translator);

        $this->assertEquals('1234', $callback->__invoke(['example' => 'sms.message.example'], 'example', '1234'));
    }

}
