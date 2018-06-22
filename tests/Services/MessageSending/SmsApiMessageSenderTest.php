<?php

use GuzzleHttp\Client;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Upaid\SmsVerification\Components\StatusMapper;
use Upaid\SmsVerification\Services\MessageSending\SmsApiMessageSender;

class SmsApiMessageSenderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzleClient;

    protected $config;
    protected $statusMapper;

    public function setUp()
    {
        parent::setUp();
        $this->guzzleClient = Mockery::mock(Client::class);

        $this->config = Mockery::mock(Illuminate\Contracts\Config\Repository::class);
        $this->config->shouldReceive('get')->once()->with('sms_verification.api.app_id')->andReturn('some_app_id');
        $this->config->shouldReceive('get')->once()->with('sms_verification.api.api_url')->andReturn('sms_api_url');
        $this->config->shouldReceive('get')->once()->with('sms_verification.log_message.supported_method')->andReturn('phone: {{phone}}, sent status: {{status}}, content: {{message}}');
        $this->config->shouldReceive('get')->once()->with('sms_verification.log_message.unsupported_method')->andReturn('phone: {{phone}}, sent status: Unsupported method, content: {{message}}');
        $this->config->shouldReceive('get')->once()->with('sms_verification.callbacks.log')->andReturn(new \Upaid\SmsVerification\Components\Callbacks\Log());

        $this->statusMapper = Mockery::mock(StatusMapper::class);
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\MessageSending\SmsApiMessageSender::send()
     */
    public function it_uses_guzzle_to_make_POST_request_and_handles_successful_response_properly_when_config_holds_POST_method()
    {
        $this->config->shouldReceive('get')->once()->with('sms_verification.api.request_method')->andReturn('POST');

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::SMS_API_RESPONSE_SUCCESS)->andReturn('SUCCESS');
        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::SMS_CODE_SENT, ['count' => 2])->andReturn('SMS_CODE_SENT|2');

        $responseBody = Mockery::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')->once()->andReturn(json_encode(['status' => 'SUCCESS', 'count' => 2]));
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn($responseBody);

        $this->guzzleClient->shouldReceive('request')->once()->with('POST', 'sms_api_url', [
            'json' => [
                'appId' => 'some_app_id',
                'receiver' => 'PHONE',
                'content' => 'MESSAGE'
            ],
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'accept-language' => 'some_locale'
            ]
        ])->andReturn($response);

        $sender = new SmsApiMessageSender($this->config, $this->guzzleClient, $this->statusMapper, 'some_locale');

        $this->assertEquals('SMS_CODE_SENT|2', $sender->send('MESSAGE', 'PHONE'));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\MessageSending\SmsApiMessageSender::send()
     */
    public function it_handles_guzzle_unsuccessful_response_properly_when_config_holds_POST_method()
    {
        $this->config->shouldReceive('get')->once()->with('sms_verification.api.request_method')->andReturn('POST');

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::SMS_API_RESPONSE_SUCCESS)->andReturn('SUCCESS');
        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::UNABLE_TO_SEND_SMS)->andReturn(StatusMapper::UNABLE_TO_SEND_SMS);

        $responseBody = Mockery::mock(StreamInterface::class);
        $responseBody->shouldReceive('getContents')->once()->andReturn(json_encode(['status' => 'FAILURE']));
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn($responseBody);

        $this->guzzleClient->shouldReceive('request')->once()->andReturn($response);

        $sender = new SmsApiMessageSender($this->config, $this->guzzleClient, $this->statusMapper, 'some_locale');

        $this->assertEquals('UNABLE_TO_SEND_SMS', $sender->send('MESSAGE', 'PHONE'));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\MessageSending\SmsApiMessageSender::send()
     */
    public function it_uses_guzzle_to_make_GET_request_and_handles_successful_response_properly_when_config_holds_GET_method()
    {
        $this->config->shouldReceive('get')->once()->with('sms_verification.api.request_method')->andReturn('GET');

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::SMS_API_RESPONSE_OK)->andReturn('OK');
        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::SMS_CODE_SENT)->andReturn(StatusMapper::SMS_CODE_SENT);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn('OK');

        $this->guzzleClient->shouldReceive('request')->once()->with('GET', 'sms_api_url', [
            'query' => [
                'number' => 'PHONE',
                'message' => 'MESSAGE',
                'appId' => 'some_app_id',
            ],
        ])->andReturn($response);

        $sender = new SmsApiMessageSender($this->config, $this->guzzleClient, $this->statusMapper, 'some_locale');

        $this->assertEquals('SMS_CODE_SENT', $sender->send('MESSAGE', 'PHONE'));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\MessageSending\SmsApiMessageSender::send()
     */
    public function it_handles_guzzle_unsuccessful_response_properly_when_config_holds_GET_method()
    {
        $this->config->shouldReceive('get')->once()->with('sms_verification.api.request_method')->andReturn('GET');

        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::SMS_API_RESPONSE_OK)->andReturn('OK');
        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::UNABLE_TO_SEND_SMS)->andReturn(StatusMapper::UNABLE_TO_SEND_SMS);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn('NOT OK');

        $this->guzzleClient->shouldReceive('request')->once()->andReturn($response);

        $sender = new SmsApiMessageSender($this->config, $this->guzzleClient, $this->statusMapper, 'some_locale');

        $this->assertEquals('UNABLE_TO_SEND_SMS', $sender->send('MESSAGE', 'PHONE'));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\MessageSending\SmsApiMessageSender::send()
     */
    public function it_logs_failure_status_if_config_holds_unsupported_request_method()
    {
        $this->config->shouldReceive('get')->once()->with('sms_verification.api.request_method')->andReturn('PIGEON');
        $this->statusMapper->shouldReceive('map')->once()->with(StatusMapper::UNABLE_TO_SEND_SMS)->andReturn(StatusMapper::UNABLE_TO_SEND_SMS);
        $this->guzzleClient->shouldNotReceive('request');

        $sender = new SmsApiMessageSender($this->config, $this->guzzleClient, $this->statusMapper, 'some_locale');

        $this->assertEquals('UNABLE_TO_SEND_SMS', $sender->send('MESSAGE', 'PHONE'));
    }

}
