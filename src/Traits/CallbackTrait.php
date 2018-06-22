<?php

namespace Upaid\SmsVerification\Traits;

trait CallbackTrait
{
    /**
     * @param string|object $callback
     * @param array ...$parameter
     * @return mixed
     */
    public function executeCallback($callback, ...$parameter)
    {
        $object = is_object($callback) ? $callback : null;
        $object = (is_string($callback) && class_exists($callback)) ? new $callback : $object;

        if (!is_object($object)) {
            throw new \RuntimeException('Unsupported callback type');
        }

        return call_user_func($object, ...$parameter);
    }

}
