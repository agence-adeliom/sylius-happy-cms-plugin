<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Asset;

use Adeliom\SyliusHappyCMSPlugin\Asset\AssetHappyCMSPackage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetHappyCMSPackageTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function vueMediaManagerAssets(): iterable
    {
        yield 'script' => ['media-form.js'];
        yield 'stylesheet' => ['media-form.css'];
    }

    #[DataProvider('vueMediaManagerAssets')]
    public function testTheVueMediaManagerAssetsComeFromTheManifestByDefault(string $asset): void
    {
        $manifest = $this->manifest();

        self::assertSame($manifest[$asset], (new AssetHappyCMSPackage())->getUrl($asset));
        self::assertSame($manifest[$asset], (new AssetHappyCMSPackage(false))->getUrl($asset));
    }

    public function testTheVueMediaManagerAssetsAreEmptyFilesWithEasyMediaManager(): void
    {
        $package = new AssetHappyCMSPackage(true);

        self::assertSame('/bundles/syliushappycmsplugin/easy-media/empty.js', $package->getUrl('media-form.js'));
        self::assertSame('/bundles/syliushappycmsplugin/easy-media/empty.css', $package->getUrl('media-form.css'));
        self::assertSame('', $package->getVersion('media-form.js'));

        foreach (AssetHappyCMSPackage::VUE_MEDIA_MANAGER_ASSETS as $emptyAsset) {
            $file = __DIR__ . '/../../../Resources/public/' . $emptyAsset;
            self::assertFileExists($file);
            self::assertSame('', file_get_contents($file));
        }
    }

    public function testTheOtherAssetsAreUnchangedWithEasyMediaManager(): void
    {
        $manifest = $this->manifest();

        self::assertSame($manifest['page-builder.js'], (new AssetHappyCMSPackage(true))->getUrl('page-builder.js'));
        self::assertSame($manifest['tiny-mce.js'], (new AssetHappyCMSPackage(true))->getUrl('tiny-mce.js'));
    }

    /**
     * @return array<string, string>
     */
    private function manifest(): array
    {
        /** @var array<string, string> $manifest */
        $manifest = json_decode((string) file_get_contents(__DIR__ . '/../../../Resources/public/manifest.json'), true, flags: \JSON_THROW_ON_ERROR);

        return $manifest;
    }
}
