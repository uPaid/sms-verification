<?php

namespace Upaid\SmsVerification\Services\CacheManagement;

use Upaid\ContextualKeys\ContextualKeysTrait;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Upaid\ContextualKeys\Contracts\ContextualKeysInterface;

class SmsCache implements ContextualKeysInterface, SmsStorage
{
    use ContextualKeysTrait;

    /**
     * Minutes
     * @var int
     */
    protected $lifetime = 15;

    const CHECKS_COUNTER = 'checks_counter';
    const SMS_CODE = 'sms_code';
    const SEND_SMS_AGAIN_COUNTER = 'send_sms_again_counter';

    /**
     * @var array
    */
    protected $supportedKeys = [
        self::CHECKS_COUNTER,
        self::SMS_CODE,
        self::SEND_SMS_AGAIN_COUNTER,
    ];

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    public function __construct(Cache $cache, Config $config)
    {
        $this->cache = $cache;
        $this->lifetime = $config->get('sms_verification.cache_life_time');
    }

    /********************************************** Checks counter cache **********************************************/

    public function getChecksCounter(): int
    {
        return (int) $this->cache->get($this->generateFullKey(self::CHECKS_COUNTER), 0);
    }

    /**
     * @return int checks counter after incrementation
    */
    public function incrementChecksCounter(): int
    {
        $key = $this->generateFullKey(self::CHECKS_COUNTER);
        if ($this->cache->has($key)) {
            return $this->cache->increment($key);
        }

        $this->cache->put($key, 1, $this->lifetime);

        return 1;
    }

    public function resetChecksCounter(): void
    {
        $this->cache->forget($this->generateFullKey(self::CHECKS_COUNTER));
    }

    /************************************************* Sms code cache *************************************************/

    /**
     * @param string $smsCode
     */
    public function saveSmsCode(string $smsCode): void
    {
        $this->cache->put($this->generateFullKey(self::SMS_CODE), $smsCode, $this->lifetime);
    }

    public function getSmsCode(): string
    {
        return (string) $this->cache->get($this->generateFullKey(self::SMS_CODE), '');
    }

    /*************************************** SendSmsAgain method counter cache ****************************************/

    public function incrementSendSmsAgainCounter(): int
    {
        $key = $this->generateFullKey(self::SEND_SMS_AGAIN_COUNTER);
        if ($this->cache->has($key)) {
            return $this->cache->increment($key);
        }

        $this->cache->put($key, 1, $this->lifetime);

        return 1;
    }

    public function getSendSmsAgainCounter(): int
    {
        return (int) $this->cache->get($this->generateFullKey(self::SEND_SMS_AGAIN_COUNTER), 0);
    }

    /************************************************* Other methods **************************************************/

    public function flushAll(): void
    {
        foreach ($this->supportedKeys as $key) {
            $this->cache->forget($this->generateFullKey($key));
        }
    }
}
