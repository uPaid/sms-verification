<?php

namespace Upaid\SmsVerification\Managers;

use Upaid\SmsVerification\Traits\CallbackTrait;
use Upaid\SmsVerification\Components\StatusMapper;
use Illuminate\Contracts\Config\Repository as Config;
use Upaid\SmsVerification\Contracts\SmsManagerInterface;
use Upaid\SmsVerification\Services\CacheManagement\SmsStorage;
use Upaid\SmsVerification\Exceptions\SmsCodeVerificationException;
use Upaid\SmsVerification\Services\MessageSending\MessageSenderInterface;
use Upaid\SmsVerification\Services\MessageComposing\MessageComposerInterface;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\CodeGeneratorInterface;

abstract class BaseManager implements SmsManagerInterface
{
    use CallbackTrait;

    protected $checksLimit = 3;

    /**
     * @var array
     */
    protected $supportedActions = [];

    /**
     * @var int
     */
    protected $smsCodeLength = 4;

    /**
     * @var \Upaid\SmsVerification\Services\CacheManagement\SmsStorage
     */
    protected $smsStorage;

    /**
     * @var \Upaid\SmsVerification\Services\CodeGenerating\Generators\CodeGeneratorInterface
     */
    protected $smsCodeGenerator;

    /**
     * @var \Upaid\SmsVerification\Services\MessageComposing\MessageComposerInterface
     */
    protected $messageComposer;

    /**
     * @var \Upaid\SmsVerification\Services\MessageSending\MessageSenderInterface
     */
    protected $messageSender;

    /**
     * @var StatusMapper
     */
    protected $statusMapper;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    public function __construct(SmsStorage $smsStorage, CodeGeneratorInterface $smsCodeGenerator,
                                MessageComposerInterface $messageComposer, MessageSenderInterface $messageSender,
                                StatusMapper $statusMapper, Config $config)
    {
        $this->smsStorage = $smsStorage;
        $this->smsCodeGenerator = $smsCodeGenerator;
        $this->messageComposer = $messageComposer;
        $this->messageSender = $messageSender;
        $this->statusMapper = $statusMapper;
        $this->config = $config;

        $this->checksLimit = $config->get('sms_verification.checks_limit');
        $this->smsCodeLength = $config->get('sms_verification.sms_code_length');
        $this->supportedActions = $config->get('sms_verification.actions');
    }

    public function sendSmsCode(string $action, string $phone, array $messageTranslationPlaceholders = []): string
    {
        /**
         * this method:
         * - sends sms code without resetting checks counter
         * - can be executed as many times as you please, provided that check counter is lower than limit
         */

        $this->initSmsStorage($action, $phone);

        if (!$this->isChecksCounterLessThanLimit($this->smsStorage->getChecksCounter())) {
            return $this->statusMapper->map(StatusMapper::TOO_MANY_SMS_ATTEMPTS);
        }

        return $this->doSendSmsCode($action, $phone, $messageTranslationPlaceholders);
    }

    public function checkSmsCode(string $action, string $phone, string $code): string
    {
        $this->initSmsStorage($action, $phone);
        $checksCounter = $this->smsStorage->getChecksCounter() + 1;

        // return TOO_MANY_SMS_ATTEMPTS status if threshold has been exceeded
        if ($this->isChecksLimitExceeded($checksCounter)) {
            return $this->statusMapper->map(StatusMapper::TOO_MANY_SMS_ATTEMPTS);
        }

        // check if code is valid and return true if so (so valid checks do not increase attempt counter)
        if ($this->isCodeValid($code)) {
            $this->smsStorage->resetChecksCounter();
            return $this->statusMapper->map(StatusMapper::CODE_IS_VALID);
        }

        // code is invalid, so handle this case by incrementing counter in storage,
        // handling reaching checks limit (if needed) and returning an appropriate response
        $this->smsStorage->incrementChecksCounter();
        if ($this->isChecksLimitReached($checksCounter)) {
            return $this->handleReachingChecksLimit($action, $phone);
        }

        return $this->statusMapper->map(StatusMapper::CODE_IS_INVALID);
    }

    public function flushPendingSmsValidation(string $id): void
    {
        $this->smsStorage->setIdentifier($id);
        foreach ($this->supportedActions as $action) {
            $this->smsStorage->setContext($action);
            $this->smsStorage->flushAll();
        }
    }

    /******************************************************************************************************************/

    protected function initSmsStorage(string $action, string $id): void
    {
        if (!in_array($action, $this->supportedActions)) {
            throw new SmsCodeVerificationException($action . ' action is not supported');
        }

        $this->smsStorage->setContext($action);
        $this->smsStorage->setIdentifier($id);
    }

    protected function doSendSmsCode(string $action, string $phone, array $messageTranslationPlaceholders = []): string
    {
        $code = $this->smsCodeGenerator->generateCode($this->smsCodeLength);
        $this->smsStorage->saveSmsCode($code);
        $message = $this->messageComposer->compose($action, $code, $messageTranslationPlaceholders);

        return $this->messageSender->send($message, $phone);
    }

    protected function isCodeValid(string $code) : bool
    {
        return $code === $this->smsStorage->getSmsCode();
    }

    protected function isChecksLimitExceeded(int $attempt): bool
    {
        return $attempt > $this->checksLimit;
    }

    protected function isChecksLimitReached(int $attempt): bool
    {
        return $attempt == $this->checksLimit;
    }

    protected function isChecksCounterLessThanLimit(int $attempt): bool
    {
        return $attempt < $this->checksLimit;
    }

    protected function handleReachingChecksLimit(string $action, string $phone): string
    {
        throw new \Exception('You have to implement this method in a child class');
    }
}
