<?php

namespace Upaid\SmsVerification\Components\Callbacks;

use Upaid\SmsVerification\Components\StatusMapper;
use Illuminate\Contracts\Config\Repository as Config;
use Upaid\SmsVerification\Contracts\SmsManagerInterface;
use Upaid\SmsVerification\Managers\LimitedResendManager;
use Upaid\SmsVerification\Services\CacheManagement\SmsStorage;
use Upaid\SmsVerification\Services\MessageSending\MessageSenderInterface;
use Upaid\SmsVerification\Services\MessageComposing\MessageComposerInterface;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\CodeGeneratorInterface;

class CreateLimitedResendManager
{
    /**
     * @return LimitedResendManager
     */
    public function __invoke(): SmsManagerInterface
    {
        return new LimitedResendManager(
            app(SmsStorage::class),
            app(CodeGeneratorInterface::class),
            app(MessageComposerInterface::class),
            app(MessageSenderInterface::class),
            app(StatusMapper::class),
            app(Config::class)
        );
    }
}
