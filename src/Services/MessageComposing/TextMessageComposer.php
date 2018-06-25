<?php

namespace Upaid\SmsVerification\Services\MessageComposing;

use Upaid\SmsVerification\Traits\CallbackTrait;
use Illuminate\Contracts\Config\Repository as Config;

class TextMessageComposer implements MessageComposerInterface
{
    use CallbackTrait;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function compose(string $action, string $code, array $translationPlaceholders = []): string
    {
        $callback = $this->config->get('sms_verification.callbacks.message_composer');
        $translationConfig = $this->config->get('sms_verification.translations');
        return $this->executeCallback($callback, $translationConfig, $action, $code, $translationPlaceholders);
    }

}
