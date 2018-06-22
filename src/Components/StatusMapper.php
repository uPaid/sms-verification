<?php

namespace Upaid\SmsVerification\Components;

use Illuminate\Contracts\Config\Repository as Config;
use Upaid\SmsVerification\Traits\ReplacePlaceholderTrait;

class StatusMapper
{
    use ReplacePlaceholderTrait;

    const SMS_CODE_SENT = 'SMS_CODE_SENT';
    const UNABLE_TO_SEND_SMS = 'UNABLE_TO_SEND_SMS';
    const TOO_MANY_SMS_ATTEMPTS = 'TOO_MANY_SMS_ATTEMPTS';
    const TOO_MANY_SMS_ATTEMPTS_SENDING_AGAIN = 'TOO_MANY_SMS_ATTEMPTS_SENDING_AGAIN';
    const VALIDATION_ERROR_TOO_MANY_SMS_ATTEMPTS = 'VALIDATION_ERROR_TOO_MANY_SMS_ATTEMPTS';

    const SMS_API_RESPONSE_OK = 'SMS_API_RESPONSE_OK';
    const SMS_API_RESPONSE_SUCCESS = 'SMS_API_RESPONSE_SUCCESS';

    const CODE_IS_VALID = 'CODE_IS_VALID';
    const CODE_IS_INVALID = 'CODE_IS_INVALID';

    /**
     * @var array
     */
    protected $map;

    /**
     * @var array
     */
    protected $placeholders;

    public function __construct(Config $config)
    {
        $this->map = $config->get('sms_verification.status_map') ?? [];
        $this->placeholders = $config->get('sms_verification.status_placeholders') ?? [];
    }

    public function map(string $status, $replace = []): string
    {
        if (!defined('self::' . $status)) {
            throw new \RuntimeException('Constant StatusMapper::' . $status .' is not defined.');
        }
        if (!empty($replace) && isset($this->placeholders[$status])) {
            return $this->replacePlaceholders($this->placeholders[$status], $replace);
        }
        return $this->map[$status] ?? $status;
    }
}
