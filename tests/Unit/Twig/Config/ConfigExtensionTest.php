<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Twig\Config;

use Adeliom\SyliusHappyCMSPlugin\Twig\Config\ConfigExtension;
use Adeliom\SyliusHappyCMSPlugin\Twig\Config\ConfigRuntime;
use PHPUnit\Framework\TestCase;

final class ConfigExtensionTest extends TestCase
{
    public function testTheFunctionIsServedByTheLazyRuntime(): void
    {
        $functions = (new ConfigExtension())->getFunctions();

        self::assertCount(1, $functions);
        self::assertSame('happy_cms_config', $functions[0]->getName());
        self::assertSame([ConfigRuntime::class, 'getValue'], $functions[0]->getCallable());
    }
}
