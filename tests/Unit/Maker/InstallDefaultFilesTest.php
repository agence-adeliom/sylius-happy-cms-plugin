<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Maker;

use Adeliom\SyliusHappyCMSPlugin\Maker\InstallDefaultFiles;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class InstallDefaultFilesTest extends TestCase
{
    private string $projectDir;

    private string $previousDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/happy-cms-install-' . bin2hex(random_bytes(4));
        (new Filesystem())->mkdir($this->projectDir . '/config');

        $previousDir = getcwd();
        self::assertIsString($previousDir);
        $this->previousDir = $previousDir;
        chdir($this->projectDir);
    }

    protected function tearDown(): void
    {
        chdir($this->previousDir);
        (new Filesystem())->remove($this->projectDir);
    }

    public function testGeneratedRouteIsPrefixedWithTheSyliusAdminPath(): void
    {
        $maker = new InstallDefaultFiles(new ParameterBag());
        $io = new ConsoleStyle(new ArrayInput([]), new BufferedOutput());

        $maker->generateRoute('page', $io);

        $routes = Yaml::parseFile($this->projectDir . '/' . InstallDefaultFiles::YAML_ROUTES_FILE);

        self::assertIsArray($routes);
        self::assertArrayHasKey('sylius_happy_cms_page_admin', $routes);
        self::assertSame('/%sylius_admin.path_name%', $routes['sylius_happy_cms_page_admin']['prefix']);
    }
}
