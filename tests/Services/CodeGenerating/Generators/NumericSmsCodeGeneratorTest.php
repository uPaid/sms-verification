<?php

use Upaid\SmsVerification\Services\CodeGenerating\Validators\CodeLengthValidator;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\NumericSmsCodeGenerator;

class NumericSmsCodeGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CodeGenerating\Generators\NumericSmsCodeGenerator::generateCode()
     */
    public function it_generates_real_code_properly_if_given_length_is_supported()
    {
        $generator = new NumericSmsCodeGenerator(new CodeLengthValidator());

        $this->assertTrue(strlen($generator->generateCode(4)) === 4);
        $this->assertTrue(strlen($generator->generateCode(6)) === 6);
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CodeGenerating\Generators\NumericSmsCodeGenerator::generateCode()
     */
    public function it_throws_an_exception_if_given_length_is_not_supported()
    {
        $generator = new NumericSmsCodeGenerator(new CodeLengthValidator());
        $this->expectException(\InvalidArgumentException::class);

        $generator->generateCode(2);
    }

}
