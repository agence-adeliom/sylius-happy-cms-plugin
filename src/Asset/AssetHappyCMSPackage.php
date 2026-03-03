<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Asset;

use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;

/**
 * This defines a Symfony Asset named package that groups all the assets provided
 * by EasyAdmin. This is needed because EasyAdmin uses asset versioning, so the
 * full absolute URLs of assets isn't known (the URL contain changing hashes).
 *
 * In practice this uses the same strategy (and even the same "manifest.json" file)
 * used by Webpack Encore. We do this because we want to keep EasyAdmin dependencies as
 * lean as possible, so we don't want to require Webpack Encore to use EasyAdmin.
 */
final class AssetHappyCMSPackage implements PackageInterface
{
    public const PACKAGE_NAME = 'sylius.happy.cms.assets.package';

    private PackageInterface $package;

    public function __construct()
    {
        $this->package = new PathPackage(
            '/bundles/syliushappycmsplugin',
            new JsonManifestVersionStrategy(__DIR__ . '/../../Resources/public/manifest.json'),
            null,
        );
    }

    public function getUrl(string $assetPath): string
    {
        return $this->package->getUrl($assetPath);
    }

    public function getVersion(string $assetPath): string
    {
        return $this->package->getVersion($assetPath);
    }
}
