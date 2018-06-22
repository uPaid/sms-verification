<?php

namespace Upaid\SmsVerification\Managers;

use Upaid\SmsVerification\Components\StatusMapper;
use Illuminate\Contracts\Config\Repository as Config;
use Upaid\SmsVerification\Contracts\SmsManagerInterface;
use Upaid\SmsVerification\Services\CacheManagement\SmsStorage;
use Upaid\SmsVerification\Services\MessageSending\MessageSenderInterface;
use Upaid\SmsVerification\Services\MessageComposing\MessageComposerInterface;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\CodeGeneratorInterface;

/**
 * Limited-specific flow:
 * if we're checking code for the first sms then send another sms (and remember sms number in storage!)
 * if we're checking code for the N's sms then run overLimit callback
 */
class LimitedResendManager extends BaseManager implements SmsManagerInterface
{
    protected $sendAgainLimit = 1;

    public function __construct(SmsStorage $smsStorage, CodeGeneratorInterface $smsCodeGenerator,
                                MessageComposerInterface $messageComposer, MessageSenderInterface $messageSender,
                                StatusMapper $statusMapper, Config $config)
    {
        parent::__construct($smsStorage, $smsCodeGenerator, $messageComposer, $messageSender, $statusMapper, $config);
        $this->sendAgainLimit = $config->get('sms_verification.sendAgainLimit');
    }

    public function sendSmsAgain(string $action, string $phone, array $messageTranslationPlaceholders = []): string
    {
        /**
         * this method:
         * - resets checks counter
         * - can be used only SEND_SMS_AGAIN_LIMIT times
         */

        $this->initSmsStorage($action, $phone);
        if ($this->isSendSmsAgainLimitReached()) {
            return $this->overLimit($action, $phone);
        }

        $this->smsStorage->resetChecksCounter();
        $this->smsStorage->incrementSendSmsAgainCounter();

        return $this->doSendSmsCode($action, $phone, $messageTranslationPlaceholders);
    }

    /******************************************************************************************************************/

    protected function isSendSmsAgainLimitReached(): bool
    {
        return $this->smsStorage->getSendSmsAgainCounter() === $this->sendAgainLimit;
    }

    protected function handleReachingChecksLimit(string $action, string $phone): string
    {
        if ($this->isSendSmsAgainLimitReached()) {
            return $this->overLimit($action, $phone);
        }

        $this->smsStorage->resetChecksCounter();
        $this->smsStorage->incrementSendSmsAgainCounter();

        return $this->doSendSmsCode($action, $phone);
    }

    protected function overLimit($action, $phone): string
    {
        $this->executeCallback($this->config->get('sms_verification.callbacks.overLimit'), $action, $phone);

        return $this->statusMapper->map(StatusMapper::TOO_MANY_SMS_ATTEMPTS);
    }
}
