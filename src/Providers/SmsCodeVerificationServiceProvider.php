<?php

namespace Upaid\SmsVerification\Providers;

use Illuminate\Support\ServiceProvider;
use Upaid\SmsVerification\Traits\CallbackTrait;
use Upaid\SmsVerification\Contracts\SmsManagerInterface;
use Upaid\SmsVerification\Services\CacheManagement\SmsCache;
use Upaid\SmsVerification\Services\CacheManagement\LockCache;
use Upaid\SmsVerification\Services\CacheManagement\SmsStorage;
use Upaid\SmsVerification\Services\CacheManagement\LockStorage;
use Upaid\SmsVerification\Services\MessageSending\DummyMessageSender;
use Upaid\SmsVerification\Services\MessageSending\SmsApiMessageSender;
use Upaid\SmsVerification\Services\MessageComposing\TextMessageComposer;
use Upaid\SmsVerification\Services\MessageSending\MessageSenderInterface;
use Upaid\SmsVerification\Services\MessageComposing\MessageComposerInterface;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\DummySmsCodeGenerator;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\CodeGeneratorInterface;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\NumericSmsCodeGenerator;

class SmsCodeVerificationServiceProvider extends ServiceProvider
{
    use CallbackTrait;

    public function register()
    {
        $this->checkIfProjectConfigOverwritten();
        $this->mergeConfig();
        $this->bindDependencies();

        $this->app->singleton(SmsManagerInterface::class, function () {
            return $this->executeCallback(config('sms_verification.callbacks.manager'));
        });
    }

    public function boot()
    {
        if (function_exists('config_path')) {
            $this->publishes([$this->configPath() => config_path('sms_verification.php')], 'config');
        }
    }

    protected function bindDependencies()
    {
        $bindings = [
            SmsStorage::class => SmsCache::class,
            LockStorage::class => LockCache::class,
            MessageComposerInterface::class => TextMessageComposer::class,
        ];

        if ($this->shouldUseDummyServices()) {
            $bindings[CodeGeneratorInterface::class] = DummySmsCodeGenerator::class;
            $bindings[MessageSenderInterface::class] = DummyMessageSender::class;
        } else {
            $bindings[CodeGeneratorInterface::class] = NumericSmsCodeGenerator::class;
            $bindings[MessageSenderInterface::class] = SmsApiMessageSender::class;
        }

        foreach ($bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'sms_verification');
    }

    protected function configPath(): string
    {
        return __DIR__ . '/../../config/sms_verification.php';
    }

    protected function checkIfProjectConfigOverwritten()
    {
        if (empty($config = $this->app['config']->get('sms_verification', []))) {
            throw new \RuntimeException('Configuration should be overwritten in project config directory.');
        }
    }

    protected function shouldUseDummyServices(): bool
    {
        return $this->executeCallback(config('sms_verification.callbacks.dummy_services'), config('sms_verification.dummy_services_environments'));
    }

}
