<?php

namespace Upaid\SmsVerification\Components\Callbacks;

use Illuminate\Support\Facades\App;

class UseDummyServices
{
    /**
     * @param array $dummyServicesEnvironments
     * @return bool
     */
    public function __invoke(array $dummyServicesEnvironments): bool
    {
        return in_array(App::environment(), $dummyServicesEnvironments);
    }
}
