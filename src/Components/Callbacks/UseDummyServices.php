<?php

namespace Upaid\SmsVerification\Components\Callbacks;

use Illuminate\Support\Facades\App;

class UseDummyServices
{
    /**
     * @param array $dummyServicesEnvironments
     * @param boolean $forceUserRealServices
     * @return bool
     */
    public function __invoke(array $dummyServicesEnvironments, boolean $forceUserRealServices): bool
    {
        if ($forceUserRealServices === true) {
            return false;
        }
        return in_array(App::environment(), $dummyServicesEnvironments);
    }
}
