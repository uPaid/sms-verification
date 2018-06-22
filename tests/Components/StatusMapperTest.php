<?php

use Upaid\SmsVerification\Components\StatusMapper;

class StatusMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @covers \Upaid\SmsVerification\Components\StatusMapper::map
     */
    public function map_success()
    {
        $config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $config->shouldReceive('get')->with('sms_verification.status_placeholders')->once()->andReturn([]);
        $config->shouldReceive('get')->with('sms_verification.status_map')->once()->andReturn([
            StatusMapper::SMS_CODE_SENT => 'SMS_CODE_SENT_TEST',
            StatusMapper::CODE_IS_VALID => 'CODE_IS_VALID_TEST2',
        ]);

        $mapper = new StatusMapper($config);

        $validStatus = $mapper->map(StatusMapper::CODE_IS_VALID);
        $this->assertEquals($validStatus, 'CODE_IS_VALID_TEST2');

        $sentStatus = $mapper->map(StatusMapper::SMS_CODE_SENT);
        $this->assertEquals($sentStatus, 'SMS_CODE_SENT_TEST');

        $apiStatus = $mapper->map(StatusMapper::SMS_API_RESPONSE_OK);
        $this->assertEquals($apiStatus, 'SMS_API_RESPONSE_OK');
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Components\StatusMapper::map
     */
    public function map_fail()
    {
        $notDefinedConstant = 'not_defined_constant';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Constant StatusMapper::' . $notDefinedConstant .' is not defined.');

        $config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $config->shouldReceive('get')->with('sms_verification.status_placeholders')->once()->andReturn([]);
        $config->shouldReceive('get')->with('sms_verification.status_map')->once()->andReturn([
            StatusMapper::SMS_CODE_SENT => 'SMS_CODE_SENT_TEST',
        ]);

        (new StatusMapper($config))->map($notDefinedConstant);
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Components\StatusMapper::map
     */
    public function replace_success()
    {
        $config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $config->shouldReceive('get')->with('sms_verification.status_placeholders')->once()->andReturn([
            StatusMapper::SMS_CODE_SENT => 'SMS_CODE_SENT_TEST|{{{test_key}}}|({{test_key2}})',
        ]);
        $config->shouldReceive('get')->with('sms_verification.status_map')->once()->andReturn([]);

        $mapper = new StatusMapper($config);

        $replacedStatus = $mapper->map(StatusMapper::SMS_CODE_SENT, ['test_key' => 1313, 'test_key2' => 'eee']);
        $this->assertEquals($replacedStatus, 'SMS_CODE_SENT_TEST|{1313}|(eee)');
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Components\StatusMapper::map
     */
    public function replace_fail()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error while replacing placeholders: {{key2}}');

        $config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $config->shouldReceive('get')->with('sms_verification.status_placeholders')->once()->andReturn([
            StatusMapper::SMS_CODE_SENT => 'SMS_CODE_SENT_TEST|{{test_key}}|{{key2}}',
        ]);
        $config->shouldReceive('get')->with('sms_verification.status_map')->once()->andReturn([]);

        (new StatusMapper($config))->map(StatusMapper::SMS_CODE_SENT, ['test_key' => 1313]);
    }
}
