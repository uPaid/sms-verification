<?php

namespace Upaid\SmsVerification\Traits;

trait ReplacePlaceholderTrait
{
    public function replacePlaceholders(string $template, array $replace): string
    {
        $preparedReplace = [];
        array_walk($replace, function($value, $key) use (&$preparedReplace) {
            $preparedReplace['{{'.$key.'}}'] = $value;
        });

        $processed = strtr($template, $preparedReplace);
        // check if status placeholders was successfully replaced
        if (preg_match('/{{.*}}/', $processed, $matches)) {
            throw new \RuntimeException('Error while replacing placeholders: ' . implode(', ', $matches));
        }

        return $processed;
    }
}
