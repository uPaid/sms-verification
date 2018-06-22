<?php

namespace Upaid\SmsVerification\Managers;

use Upaid\SmsVerification\Contracts\SmsManagerInterface;

class UnlimitedResendManager extends BaseManager implements SmsManagerInterface
{

    public function sendSmsAgain(string $action, string $phone, array $messageTranslationPlaceholders = []): string
    {
        /**
         * this method:
         * - resets checks counter
         * - can be used unlimited times (so we neither increment sendSmsAgain counter nor check limit)
         */

        $this->initSmsStorage($action, $phone);
        $this->smsStorage->resetChecksCounter();

        return $this->doSendSmsCode($action, $phone, $messageTranslationPlaceholders);
    }

    /******************************************************************************************************************/

    protected function handleReachingChecksLimit(string $action, string $phone): string
    {
        $this->smsStorage->resetChecksCounter();

        // send again because it's UnlimitedResend strategy
        return $this->doSendSmsCode($action, $phone);
    }
}
