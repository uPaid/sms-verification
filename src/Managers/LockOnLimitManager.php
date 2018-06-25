<?php

namespace Upaid\SmsVerification\Managers;

use Upaid\SmsVerification\Traits\CallbackTrait;
use Upaid\SmsVerification\Components\StatusMapper;
use Upaid\SmsVerification\Contracts\SmsManagerInterface;
use Upaid\SmsVerification\Exceptions\SmsCodeVerificationException;
use Upaid\SmsVerification\Services\LocksManagement\Contracts\LockManagerInterface;

class LockOnLimitManager extends BaseManager implements SmsManagerInterface
{
    use CallbackTrait;

    public function sendSmsCode(string $action, string $phone, array $messageTranslationPlaceholders = []): string
    {
        if ($this->makeLockManager($action)->isLocked($action, $phone)) {
            return $this->statusMapper->map(StatusMapper::TOO_MANY_SMS_ATTEMPTS);
        }

        return parent::sendSmsCode($action, $phone, $messageTranslationPlaceholders);
    }

    public function checkSmsCode(string $action, string $phone, string $code): string
    {
        if ($this->makeLockManager($action)->isLocked($action, $phone)) {
            return $this->statusMapper->map(StatusMapper::TOO_MANY_SMS_ATTEMPTS);
        }

        return parent::checkSmsCode($action, $phone, $code);
    }

    public function sendSmsAgain(string $action, string $phone, array $messageTranslationPlaceholders = []): string
    {
        throw new SmsCodeVerificationException('sendSmsAgain method is not supported in LockOnLimitManager');
    }

    /******************************************************************************************************************/

    protected function handleReachingChecksLimit(string $action, string $phone): string
    {
        $this->smsStorage->resetChecksCounter();
        return $this->makeLockManager($action)->setLock($action, $phone);
    }

    protected function makeLockManager(string $action): LockManagerInterface
    {
        return $this->executeCallback($this->config->get('sms_verification.callbacks.lock_manager'), $action, ['method' => __FUNCTION__, 'class' => __CLASS__]);
    }

}
