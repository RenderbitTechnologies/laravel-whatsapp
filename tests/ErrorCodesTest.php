<?php

namespace Renderbit\LaravelWhatsapp\Tests;

use Renderbit\LaravelWhatsapp\Constants\ErrorCodes;

use PHPUnit\Framework\Attributes\Test;

class ErrorCodesTest extends TestCase
{
    #[Test]
    public function it_returns_known_error_message()
    {
        $ref = new \ReflectionClass(ErrorCodes::class);
        $map = $ref->getConstant('MAP');

        foreach ($map as $code => $message) {
            $this->assertIsInt($code, 'Error code must be an integer');
            $this->assertIsString($message, 'Error message must be a string');
            $this->assertNotEmpty($message, 'Error message must not be empty');
        }
    }

    #[Test]
    public function it_has_all_expected_error_codes()
    {
        $map = ErrorCodes::MAP;

        $expectedCodes = [
            28694, 10001, 52992, 52995, 57089, 57090, 57091, 65280,
            65535, 28673, 28674, 28675, 28676, 28679, 28680, 28681,
            28682, 28683, 28684, 28692, 28695, 28696, 28697, 28698,
            28699, 28700, 28702, 28703, 28704, 28678, 2009, 2010,
            2011, 2012, 2022, 2026, 9988, 28685, 28686, 28687,
            28688, 28689, 28690, 28691, 28693, 28701, 38679, 38681,
            52993, 52994, 65536,
        ];

        foreach ($expectedCodes as $code) {
            $this->assertArrayHasKey($code, $map, "Error code $code should exist in MAP");
        }
    }

    #[Test]
    public function it_returns_null_for_unknown_error_code()
    {
        $this->assertNull(ErrorCodes::MAP[99999] ?? null);
    }
}
