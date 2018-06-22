<?php

use Upaid\SmsVerification\Services\CodeGenerating\Validators\CodeLengthValidator;
use Upaid\SmsVerification\Services\CodeGenerating\Generators\DummySmsCodeGenerator;

class DummySmsCodeGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CodeGenerating\Generators\DummySmsCodeGenerator::generateCode()
     */
    public function it_generates_fake_code_properly_if_given_length_is_supported()
    {
        $generator = new DummySmsCodeGenerator(new CodeLengthValidator());

        $this->assertSame('1111', $generator->generateCode(4));
        $this->assertSame('111111', $generator->generateCode(6));
    }

    /**
     * @test
     * @covers \Upaid\SmsVerification\Services\CodeGenerating\Generators\DummySmsCodeGenerator::generateCode()
     */
    public function it_throws_an_exception_if_given_length_is_not_supported()
    {
        $generator = new DummySmsCodeGenerator(new CodeLengthValidator());
        $this->expectException(\InvalidArgumentException::class);

        $generator->generateCode(2);
    }
}
