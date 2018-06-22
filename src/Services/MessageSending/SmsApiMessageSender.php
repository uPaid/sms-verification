<?php

namespace Upaid\SmsVerification\Services\MessageSending;

use GuzzleHttp\Client;
use Upaid\SmsVerification\Traits\CallbackTrait;
use Upaid\SmsVerification\Components\StatusMapper;
use Illuminate\Contracts\Config\Repository as Config;
use Upaid\SmsVerification\Traits\ReplacePlaceholderTrait;

class SmsApiMessageSender implements MessageSenderInterface
{
    use CallbackTrait;
    use ReplacePlaceholderTrait;

    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_GET = 'GET';

    /**
     * @var \GuzzleHttp\Client
    */
    protected $guzzleClient;

    /**
     * @var StatusMapper
     */
    protected $statusMapper;

    /**
     * @var string
    */
    protected $appId;
    protected $smsApiUrl;
    protected $requestMethod;
    protected $supportedMethodLogMessage;
    protected $unsupportedMethodLogMessage;
    protected $locale;

    protected $logCallback;

    public function __construct(Config $config, Client $guzzleClient, StatusMapper $statusMapper, string $locale)
    {
        $this->guzzleClient = $guzzleClient;
        $this->statusMapper = $statusMapper;
        $this->locale = $locale;

        $this->appId = $config->get('sms_verification.api.app_id');
        $this->smsApiUrl = $config->get('sms_verification.api.api_url');
        $this->requestMethod = $config->get('sms_verification.api.request_method');
        $this->supportedMethodLogMessage = $config->get('sms_verification.log_message.supported_method');
        $this->unsupportedMethodLogMessage = $config->get('sms_verification.log_message.unsupported_method');

        $this->logCallback = $config->get('sms_verification.callbacks.log');

        if (!$this->smsApiUrl) {
            throw new \BadFunctionCallException('You have to define SMS API access data in config file');
        }
    }

    public function send(string $message, string $phone, string $requestMethod = null): string
    {
        if ($requestMethod) {
            $this->requestMethod = $requestMethod;
        }

        if ($this->shouldSendByPostMethod()) {
            return $this->sendByPostMethod($message, $phone);
        }

        if ($this->shouldSendByGetMethod()) {
            return $this->sendByGetMethod($message, $phone);
        }

        $this->log($this->unsupportedMethodLogMessage, $phone, $message);

        return $this->statusMapper->map(StatusMapper::UNABLE_TO_SEND_SMS);
    }

    /******************************************************************************************************************/

    protected function shouldSendByPostMethod(): bool
    {
        return $this->requestMethod === self::REQUEST_METHOD_POST;
    }

    protected function sendByPostMethod(string $message, string $phone): string
    {
        $response = $this->guzzleClient->request('POST', $this->smsApiUrl, [
            'json' => [
                'appId' => $this->appId,
                'receiver' => $phone,
                'content' => $message
            ],
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'accept-language' => $this->locale,
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        $sent = $result['status'] === $this->statusMapper->map(StatusMapper::SMS_API_RESPONSE_SUCCESS);

        $this->log($this->supportedMethodLogMessage, $phone, $message,$sent ? 'true' : 'false');

        $countParameter = isset($result['count']) ? ['count' => $result['count']] : [];

        return $sent
            ? $this->statusMapper->map(StatusMapper::SMS_CODE_SENT, $countParameter)
            : $this->statusMapper->map(StatusMapper::UNABLE_TO_SEND_SMS);
    }

    protected function shouldSendByGetMethod(): bool
    {
        return $this->requestMethod === self::REQUEST_METHOD_GET;
    }

    protected function sendByGetMethod(string $message, string $phone): string
    {
        $response = $this->guzzleClient->request('GET', $this->smsApiUrl, [
            'query' => [
                'number' => $phone,
                'message' => $message,
                'appId' => $this->appId
            ]
        ]);

        $sent = $response->getBody() == $this->statusMapper->map(StatusMapper::SMS_API_RESPONSE_OK);

        $this->log($this->supportedMethodLogMessage, $phone, $message, $sent ? 'true' : 'false');

        return $sent
            ? $this->statusMapper->map(StatusMapper::SMS_CODE_SENT)
            : $this->statusMapper->map(StatusMapper::UNABLE_TO_SEND_SMS);
    }

    protected function log(string $template, string $phone, string $content, string $status = null)
    {
        $processed = $this->replacePlaceholders($template, ['phone' => $phone, 'status' => $status, 'message' => $content]);
        $this->executeCallback($this->logCallback, $processed, ['method' => __FUNCTION__, 'class' => __CLASS__]);
    }
}
