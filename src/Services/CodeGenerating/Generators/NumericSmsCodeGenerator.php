<?php

namespace Upaid\SmsVerification\Services\CodeGenerating\Generators;

use Upaid\SmsVerification\Services\CodeGenerating\Validators\CodeLengthValidator;

class NumericSmsCodeGenerator implements CodeGeneratorInterface
{
    /**
     * @var \Upaid\SmsVerification\Services\CodeGenerating\Validators\CodeLengthValidator
     */
    protected $validator;

    public function __construct(CodeLengthValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param int $length
     * @return string
     * @throws \InvalidArgumentException
    */
    public function generateCode(int $length): string
    {
        $this->validator->validate($length);

        $code = (string) mt_rand(1, 9); // we don't want 0 at beginning
        for ($i = 1; $i < $length; $i++) { // we already have the first digit, so iterate $length - 1 times
            $code .= (string) mt_rand(0, 9);
        }

        return (string) $code;
    }
}
