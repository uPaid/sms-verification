<?php

namespace Upaid\SmsVerification\Services\CodeGenerating\Validators;

class CodeLengthValidator
{
    /**
     * @param int $length
     * @throws \InvalidArgumentException
     */
    public function validate(int $length): void
    {
        if ($length < 4 || $length > 8) {
            throw new \InvalidArgumentException('Number of digits for SMS code is out of range');
        }
    }
}
