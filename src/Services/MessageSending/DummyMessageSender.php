<?php

namespace Upaid\SmsVerification\Services\MessageSending;

use Upaid\SmsVerification\Traits\CallbackTrait;
use Upaid\SmsVerification\Components\StatusMapper;
use Illuminate\Contracts\Config\Repository as Config;
use Upaid\SmsVerification\Traits\ReplacePlaceholderTrait;

/**
 * !!! Write message to log (execute log callback). This class does not send the message !!!
 */
class DummyMessageSender implements MessageSenderInterface
{
    use CallbackTrait;
    use ReplacePlaceholderTrait;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var StatusMapper
     */
    protected $statusMapper;

    public function __construct(Config $config, StatusMapper $statusMapper)
    {
        $this->config = $config;
        $this->statusMapper = $statusMapper;
    }

    public function send(string $message, string $phone): string
    {
        $template = $this->config->get('sms_verification.log_message.supported_method');
        $processed = $this->replacePlaceholders($template, ['phone' => $phone, 'status' => 'true', 'message' => $message]);

        $this->executeCallback($this->config->get('sms_verification.callbacks.log'), $processed, ['method' => __FUNCTION__, 'class' => __CLASS__]);

        return $this->statusMapper->map(StatusMapper::SMS_CODE_SENT);
    }

}
