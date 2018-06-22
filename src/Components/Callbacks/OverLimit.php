<?php

namespace Upaid\SmsVerification\Components\Callbacks;

class OverLimit
{
    public function __invoke(string $action, string $phone)
    {
        // handle over limit
        // this method should be implemented in project
    }

}
