<?php

namespace Upaid\SmsVerification\Services\CodeGenerating\Generators;

interface CodeGeneratorInterface
{
    public function generateCode(int $length): string;
}
