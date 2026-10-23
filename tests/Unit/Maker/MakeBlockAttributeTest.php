<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Maker;

use Adeliom\SyliusHappyCMSPlugin\Maker\Block\MakeBlock;
use Adeliom\SyliusHappyCMSPlugin\Maker\SharedBlock\MakeSharedBlock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * The makers generate #[AsBlock] / #[AsSharedBlock] only in the "attribute mode" of the project
 * ("sylius_easy_crud.attributes.enabled: true"), the metadata methods otherwise.
 */
final class MakeBlockAttributeTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<AbstractMaker>, string, string}>
     */
    public static function makers(): iterable
    {
        yield 'content block' => [MakeBlock::class, 'AsBlock', 'AbstractBlock'];
        yield 'shared block' => [MakeSharedBlock::class, 'AsSharedBlock', 'AbstractSharedBlockType'];
    }

    /**
     * @param class-string<MakeBlock|MakeSharedBlock> $makerClass
     */
    #[DataProvider('makers')]
    public function testTheAttributeIsGeneratedInAttributeMode(string $makerClass, string $attribute, string $parent): void
    {
        $code = $this->generate(new $makerClass(new ParameterBag(['sylius_easy_crud.attributes.enabled' => true])));

        self::assertStringContainsString(sprintf("#[%s(name: 'HeroType', template: '", $attribute), $code);
        self::assertStringContainsString('class HeroType extends ' . $parent, $code);
        self::assertStringNotContainsString('function getName()', $code);
        $this->assertValidPhp($code);
    }

    /**
     * @param class-string<MakeBlock|MakeSharedBlock> $makerClass
     */
    #[DataProvider('makers')]
    public function testTheMethodsAreGeneratedWhenTheAttributeModeIsDisabled(string $makerClass, string $attribute, string $parent): void
    {
        foreach ([
            new $makerClass(new ParameterBag(['sylius_easy_crud.attributes.enabled' => false])),
            new $makerClass(new ParameterBag()),
            new $makerClass(),
        ] as $maker) {
            $code = $this->generate($maker);

            self::assertStringNotContainsString('#[' . $attribute, $code);
            self::assertStringContainsString('class HeroType extends ' . $parent, $code);
            self::assertStringContainsString('function getName()', $code);
            self::assertStringContainsString('function getFrontEndTemplatePath()', $code);
            $this->assertValidPhp($code);
        }
    }

    private function generate(MakeBlock|MakeSharedBlock $maker): string
    {
        $code = null;
        $generator = $this->createMock(Generator::class);
        $generator->method('createClassNameDetails')->willReturn(new ClassNameDetails('App\\Blocks\\HeroType', 'App\\Blocks\\', 'Type'));
        $generator->method('generateClass')->willReturnCallback(
            static function (string $className, string $templateName, array $variables) use (&$code): string {
                $variables += ['namespace' => 'App\\Blocks', 'class_name' => 'HeroType'];
                extract($variables);
                ob_start();
                include $templateName;
                $code = (string) ob_get_clean();

                return 'src/Blocks/HeroType.php';
            },
        );

        $input = new ArrayInput(['block-type' => 'Hero'], new InputDefinition([new InputArgument('block-type')]));
        $maker->generate($input, new ConsoleStyle($input, new BufferedOutput()), $generator);

        self::assertIsString($code);

        return $code;
    }

    private function assertValidPhp(string $code): void
    {
        $file = tempnam(sys_get_temp_dir(), 'happy-cms-block');
        self::assertIsString($file);
        file_put_contents($file, $code);
        exec(sprintf('%s -l %s 2>&1', escapeshellarg(\PHP_BINARY), escapeshellarg($file)), $output, $status);
        unlink($file);

        self::assertSame(0, $status, implode("\n", $output) . "\n" . $code);
    }
}
