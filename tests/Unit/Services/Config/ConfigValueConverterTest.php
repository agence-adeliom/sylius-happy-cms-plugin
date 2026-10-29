<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Config;

use Adeliom\SyliusHappyCMSPlugin\Enum\Config\ConfigTypeEnum;
use Adeliom\SyliusHappyCMSPlugin\Services\Config\ConfigValueConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigValueConverterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, mixed, mixed}>
     */
    public static function rawTypes(): iterable
    {
        yield 'code' => [ConfigTypeEnum::CODE, '<?php echo 1;', '<?php echo 1;'];
        yield 'email' => [ConfigTypeEnum::EMAIL, 'a@b.fr', 'a@b.fr'];
        yield 'number' => [ConfigTypeEnum::NUMBER, '12', '12'];
        yield 'json' => [ConfigTypeEnum::JSON, '{"a":"é\"<b>x</b>"}', '{"a":"é\"<b>x</b>"}'];
        yield 'text' => [ConfigTypeEnum::TEXT, 'hello', 'hello'];
        yield 'textarea' => [ConfigTypeEnum::TEXTAREA, "a\nb", "a\nb"];
        yield 'wysiwyg' => [ConfigTypeEnum::WYSIWYG, '<p class="x">é</p>', '<p class="x">é</p>'];
        yield 'image' => [ConfigTypeEnum::IMAGE, '42', '42'];
        yield 'null value' => [ConfigTypeEnum::TEXT, null, null];
    }

    #[DataProvider('rawTypes')]
    public function testReturnsRawValueForNonConvertedTypes(string $type, mixed $raw, mixed $expected): void
    {
        self::assertSame($expected, (new ConfigValueConverter())->convert($type, $raw));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function dateTypes(): iterable
    {
        yield 'date' => [ConfigTypeEnum::DATE, '2026-10-29', 'Y-m-d'];
        yield 'time' => [ConfigTypeEnum::TIME, '13:30:15', 'H:i:s'];
        yield 'datetime' => [ConfigTypeEnum::DATETIME, '2026-10-29 13:30:15', 'Y-m-d H:i:s'];
    }

    #[DataProvider('dateTypes')]
    public function testConvertsDateTypesToDateTime(string $type, string $raw, string $format): void
    {
        $converted = (new ConfigValueConverter())->convert($type, $raw);

        self::assertInstanceOf(\DateTime::class, $converted);
        self::assertSame($raw, $converted->format($format));
    }

    public function testEveryConfigTypeHasACase(): void
    {
        $covered = [ConfigTypeEnum::BOOLEAN];
        foreach ([...self::rawTypes(), ...self::dateTypes()] as $case) {
            $covered[] = $case[0];
        }

        $missing = array_diff(array_values(ConfigTypeEnum::toArray()), $covered);

        self::assertSame([], array_values($missing), 'ConfigTypeEnum gained a type without a converter test case.');
    }

    public function testConvertsBooleanToBool(): void
    {
        $converter = new ConfigValueConverter();

        self::assertTrue($converter->convert(ConfigTypeEnum::BOOLEAN, '1'));
        self::assertFalse($converter->convert(ConfigTypeEnum::BOOLEAN, '0'));
        self::assertFalse($converter->convert(ConfigTypeEnum::BOOLEAN, null));
    }

    #[DataProvider('dateTypes')]
    public function testInvalidDateReturnsNull(string $type): void
    {
        self::assertNull((new ConfigValueConverter())->convert($type, 'not a date at all'));
    }

    #[DataProvider('dateTypes')]
    public function testNonStringDateReturnsNull(string $type): void
    {
        self::assertNull((new ConfigValueConverter())->convert($type, null));
    }
}
