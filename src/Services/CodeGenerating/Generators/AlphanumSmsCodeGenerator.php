<?php

namespace Upaid\SmsVerification\Services\CodeGenerating\Generators;

use Upaid\SmsVerification\Services\CodeGenerating\Validators\CodeLengthValidator;

class AlphanumSmsCodeGenerator implements CodeGeneratorInterface
{
    /**
     * @var \Upaid\SmsVerification\Services\CodeGenerating\Validators\CodeLengthValidator
     */
    protected $validator;

    /**
     * @var array
    */
    protected $chars = [];

    public function __construct(CodeLengthValidator $validator)
    {
        $this->validator = $validator;

        $this->chars = array_merge(range('a', 'z'), range('A', 'Z'), range('1', '9'));
    }

    public function generateCode(int $length): string
    {
        $this->validator->validate($length);

        $code = $this->getRandomAlphanumCharacter(false);
        for ($i = 1; $i < $length; $i++) { // we already have the first digit, so iterate $length - 1 times
            $code .= $this->getRandomAlphanumCharacter(true);
        }

        return (string) $code;
    }

    /******************************************************************************************************************/

    protected function getRandomAlphanumCharacter(bool $withZero = false): string
    {
        $chars = $this->chars;
        if ($withZero) {
            $chars[] = '0'; // value of $this->chars is copied to $chars, so we won't add it many times
        }

        return $chars[mt_rand(0, count($chars) - 1)];
    }
}
