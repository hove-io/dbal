<?php

namespace Doctrine\DBAL\Tests\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function base64_encode;
use function fopen;
use function stream_get_contents;

class BinaryTest extends TestCase
{
    /** @var AbstractPlatform&MockObject */
    protected AbstractPlatform $platform;

    protected BinaryType $type;

    protected function setUp(): void
    {
        $this->platform = $this->createMock(AbstractPlatform::class);
        $this->type     = new BinaryType();
    }

    public function testReturnsBindingType(): void
    {
        self::assertSame(ParameterType::BINARY, $this->type->getBindingType());
    }

    public function testReturnsName(): void
    {
        self::assertSame(Types::BINARY, $this->type->getName());
    }

    public function testReturnsSQLDeclaration(): void
    {
        $this->platform->expects(self::once())
            ->method('getBinaryTypeDeclarationSQL')
            ->willReturn('TEST_BINARY');

        self::assertSame('TEST_BINARY', $this->type->getSQLDeclaration([], $this->platform));
    }

    public function testBinaryNullConvertsToPHPValue(): void
    {
        self::assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    public function testBinaryStringConvertsToPHPValue(): void
    {
        $databaseValue = 'binary string';
        $phpValue      = $this->type->convertToPHPValue($databaseValue, $this->platform);

        self::assertIsResource($phpValue);
        self::assertEquals($databaseValue, stream_get_contents($phpValue));
    }

    public function testBinaryResourceConvertsToPHPValue(): void
    {
        $databaseValue = fopen('data://text/plain;base64,' . base64_encode('binary string'), 'r');
        $phpValue      = $this->type->convertToPHPValue($databaseValue, $this->platform);

        self::assertSame($databaseValue, $phpValue);
    }

    /**
     * @param mixed $value
     *
     * @dataProvider getInvalidDatabaseValues
     */
    public function testThrowsConversionExceptionOnInvalidDatabaseValue($value): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue($value, $this->platform);
    }

    /**
     * @return mixed[][]
     */
    public static function getInvalidDatabaseValues(): iterable
    {
        return [
            [false],
            [true],
            [0],
            [1],
            [-1],
            [0.0],
            [1.1],
            [-1.1],
        ];
    }
}
