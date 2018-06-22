<?php

namespace Upaid\SmsVerification\Components\Callbacks;

use Illuminate\Contracts\Translation\Translator;

class MessageComposer
{
    const CODE = 'code';
    const DATETIME = 'datetime';

    /**
     * @param array $config
     * @param string $action
     * @param string $code
     * @param array $translationPlaceholders
     * @return string
     */
    public function __invoke(array $config, string $action, string $code, array $translationPlaceholders = []): string
    {
        $translationPlaceholders[self::CODE] = $code;
        $translationPlaceholders[self::DATETIME] = date('Y-m-d');

        return array_key_exists($action, $config)
            ? $this->translate($config[$action], $translationPlaceholders)
            : $code;
    }

    protected function translate(string $key, array $translationPlaceholders): string
    {
        $translator = $this->makeTranslator();

        $translation = $translator->trans($key, $translationPlaceholders);

        // in case only a default translation is provided use $code instead
        if ($translation == $key) {
            $translation = $translationPlaceholders[self::CODE];
        }

        return $translation;
    }

    protected function makeTranslator()
    {
        return app(Translator::class);
    }
}
