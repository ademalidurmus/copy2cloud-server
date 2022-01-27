<?php

declare(strict_types=1);

namespace Copy2Cloud\Tests\Base\Utilities;

use Copy2Cloud\Base\Enums\StrCharacters;
use Copy2Cloud\Base\Enums\StrTypes;
use Copy2Cloud\Base\Utilities\Json;
use Copy2Cloud\Base\Utilities\Str;
use PHPUnit\Framework\TestCase;

final class StrTest extends TestCase
{
    public function testGenerateRandomStrDefaultLength()
    {
        $string = Str::generateRandomStr();
        $this->assertEquals(40, strlen($string));
    }

    public function testGenerateRandomStrCustomLength()
    {
        $string = Str::generateRandomStr(5);
        $this->assertEquals(5, strlen($string));
    }

    public function testGenerateRandomStrUppercaseNumber()
    {
        $string = Str::generateRandomStr(5, StrTypes::number, StrCharacters::uppercase);
        $this->assertTrue(is_numeric($string));
    }

    public function testGenerateRandomStrUppercaseString()
    {
        $string = Str::generateRandomStr(5, StrTypes::string, StrCharacters::uppercase);
        $this->assertTrue(ctype_upper($string));
    }

    public function testGenerateRandomStrLowercaseNumeric()
    {
        $string = Str::generateRandomStr(5, StrTypes::number, StrCharacters::lowercase);
        $this->assertTrue(is_numeric($string));
    }

    public function testGenerateRandomStrLowercaseString()
    {
        $string = Str::generateRandomStr(5, StrTypes::string, StrCharacters::lowercase);
        $this->assertTrue(ctype_lower($string));
    }
}