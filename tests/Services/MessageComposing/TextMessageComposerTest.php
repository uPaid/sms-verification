<?php

use Upaid\SmsVerification\Components\Callbacks\MessageComposer;
use Upaid\SmsVerification\Services\MessageComposing\TextMessageComposer;

class TextMessageComposerTest extends \PHPUnit\Framework\TestCase
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
     * @covers \Upaid\SmsVerification\Services\MessageComposing\TextMessageComposer::compose()
     */
    public function it_fetches_translation_for_supported_key()
    {
        $this->composerCallback->shouldReceive('__invoke')->once()->andReturn('some translation');
        $this->config->shouldReceive('get')->with('sms_verification.callbacks.messageComposer')->once()->andReturn($this->composerCallback);
        $this->config->shouldReceive('get')->with('sms_verification.translations')->once()->andReturn([]);

        $composer = new TextMessageComposer($this->config);

        $this->assertEquals('some translation', $composer->compose('example', '1234'));
    }

}
