<?php

use Upaid\SmsVerification\Services\CodeGenerating\Generators\AlphanumSmsCodeGenerator;
use Upaid\SmsVerification\Services\CodeGenerating\Validators\CodeLengthValidator;

class AlphanumSmsCodeGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CodeGenerating\Generators\AlphanumSmsCodeGenerator::generateCode()
     */
    public function if_length_is_supported_it_generates_code_that_does_not_start_with_zero()
    {
        $generator = new AlphanumSmsCodeGenerator(new CodeLengthValidator());

        $code = $generator->generateCode(6);

        $this->assertTrue(strlen($code) === 6);
        $this->assertStringStartsNotWith('0', $code);
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CodeGenerating\Generators\AlphanumSmsCodeGenerator::generateCode()
     */
    public function it_throws_an_exception_if_given_length_is_not_supported()
    {
        $generator = new AlphanumSmsCodeGenerator(new CodeLengthValidator());
        $this->expectException(\InvalidArgumentException::class);

        $generator->generateCode(2);
    }

}
