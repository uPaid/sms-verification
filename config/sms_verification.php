<?php

return [
    'api' => [
        'app_id' => null,
        'api_url' => null,
        'request_method' => null,
    ],
    'log_message' => [
        'supported_method' => 'phone: {{phone}}, sent status: {{status}}, content: {{message}}',
        'unsupported_method' => 'phone: {{phone}}, sent status: Unsupported method, content: {{message}}',
    ],
    'status_map' => [
        'UNABLE_TO_SEND_SMS' => 'UNABLE_TO_SEND_SMS',
        'SMS_CODE_SENT' => 'SMS_CODE_SENT',
        'TOO_MANY_SMS_ATTEMPTS' => 'TOO_MANY_SMS_ATTEMPTS',
        'TOO_MANY_SMS_ATTEMPTS_SENDING_AGAIN' => 'TOO_MANY_SMS_ATTEMPTS_SENDING_AGAIN',
        'VALIDATION_ERROR_TOO_MANY_SMS_ATTEMPTS' => 'VALIDATION_ERROR_TOO_MANY_SMS_ATTEMPTS',
        'SMS_API_RESPONSE_OK' => 'SMS_API_RESPONSE_OK',
        'SMS_API_RESPONSE_SUCCESS' => 'SMS_API_RESPONSE_SUCCESS',
        'CODE_IS_VALID' => 'CODE_IS_VALID',
        'CODE_IS_INVALID' => 'CODE_IS_INVALID',
    ],
    'status_placeholders' => [
        'SMS_CODE_SENT' => 'SMS_CODE_SENT|{{count}}'
    ],
    'multi_lock_types' => [
        // action => label
        \Upaid\SmsVerification\Components\Actions::ACTION_EXAMPLE => 'example'
    ],
    'cache_life_time' => 15,
    'lock_life_time' => 15,
    'sms_code_length' => 4,
    'checks_limit' => 3,
    'send_again_limit' => 1, // only for LimitedResendManager
    'actions' => [\Upaid\SmsVerification\Components\Actions::ACTION_EXAMPLE],
    // translations config
    'translations' => [
        // action => translation key
        \Upaid\SmsVerification\Components\Actions::ACTION_EXAMPLE => ''
    ],
    'dummy_services_environments' => ['alfa'],
    'callbacks' => [
        'dummy_services' => \Upaid\SmsVerification\Components\Callbacks\UseDummyServices::class,
        'manager' => \Upaid\SmsVerification\Components\Callbacks\CreateLockOnLimitManager::class,
        'log' => \Upaid\SmsVerification\Components\Callbacks\Log::class,
        'over_limit' => \Upaid\SmsVerification\Components\Callbacks\OverLimit::class,
        'message_composer' => \Upaid\SmsVerification\Components\Callbacks\MessageComposer::class,
        'lockManager' => \Upaid\SmsVerification\Components\Callbacks\CreateLockManager::class,
    ],
];
