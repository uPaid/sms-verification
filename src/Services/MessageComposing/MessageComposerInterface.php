<?php

namespace Upaid\SmsVerification\Services\MessageComposing;

interface MessageComposerInterface
{
    public function compose(string $action, string $code, array $params = []): string;
}
