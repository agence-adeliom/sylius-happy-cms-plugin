<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Asset;

use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;

/**
 * This defines a Symfony Asset named package that groups all the assets provided
 * by EasyAdmin. This is needed because EasyAdmin uses asset versioning, so the
 * full absolute URLs of assets isn't known (the URL contain changing hashes).
 *
 * In practice this uses the same strategy (and even the same "manifest.json" file)
 * used by Webpack Encore. We do this because we want to keep EasyAdmin dependencies as
 * lean as possible, so we don't want to require Webpack Encore to use EasyAdmin.
 *
 * With easy-media-manager (`media.easy_media_manager.enabled`), the assets of the Vue media manager
 * (`media-form.js`, `media-form.css`) point to empty files: the fields and blocks that declare them through
 * `MediaType::configureAdminAssets()` no longer load the Vue application.
 */
final class AssetHappyCMSPackage implements PackageInterface
{
    public const PACKAGE_NAME = 'sylius.happy.cms.assets.package';

    /** Assets of the Vue media manager, replaced by an empty file of the same type with easy-media-manager. */
    public const VUE_MEDIA_MANAGER_ASSETS = [
        'media-form.js' => 'easy-media/empty.js',
        'media-form.css' => 'easy-media/empty.css',
    ];

    private const BASE_PATH = '/bundles/syliushappycmsplugin';

    private PackageInterface $package;

    private PackageInterface $emptyAssets;

    public function __construct(
        private readonly bool $easyMediaManager = false,
    ) {
        $this->package = new PathPackage(
            self::BASE_PATH,
            new JsonManifestVersionStrategy(__DIR__ . '/../../Resources/public/manifest.json'),
            null,
        );
        $this->emptyAssets = new PathPackage(self::BASE_PATH, new EmptyVersionStrategy());
    }

    public function getUrl(string $assetPath): string
    {
        $emptyAsset = $this->getEmptyAsset($assetPath);
        if (null !== $emptyAsset) {
            return $this->emptyAssets->getUrl($emptyAsset);
        }

        return $this->package->getUrl($assetPath);
    }

    public function getVersion(string $assetPath): string
    {
        $emptyAsset = $this->getEmptyAsset($assetPath);
        if (null !== $emptyAsset) {
            return $this->emptyAssets->getVersion($emptyAsset);
        }

        return $this->package->getVersion($assetPath);
    }

    private function getEmptyAsset(string $assetPath): ?string
    {
        if (!$this->easyMediaManager) {
            return null;
        }

        return self::VUE_MEDIA_MANAGER_ASSETS[ltrim($assetPath, '/')] ?? null;
    }
}
