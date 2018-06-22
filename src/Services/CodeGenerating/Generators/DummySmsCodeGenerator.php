<?php

namespace Upaid\SmsVerification\Services\CodeGenerating\Generators;

use Upaid\SmsVerification\Services\CodeGenerating\Validators\CodeLengthValidator;

class DummySmsCodeGenerator implements CodeGeneratorInterface
{
    /**
     * @var \Upaid\SmsVerification\Services\CodeGenerating\Validators\CodeLengthValidator
    */
    protected $validator;

    public function __construct(CodeLengthValidator $validator)
    {
        $this->validator = $validator;
    }

    public function generateCode(int $length): string
    {
        $this->validator->validate($length);

        return str_repeat('1', $length);
    }
}
