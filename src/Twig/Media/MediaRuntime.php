<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaHelper;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

class MediaRuntime implements RuntimeExtensionInterface
{
    public function __construct(protected MediaManager $manager, protected Environment $twig, protected FilterManager $filterManager, protected CacheManager $cacheManager)
    {
    }

    /**
     * @return array<string, mixed>|string|null
     */
    public function resolveMedia(int|string|MediaInterface $media): array|string|null
    {
        $media = $this->getMedia($media);
        if (!$media instanceof MediaInterface) {
            return null;
        }

        return $this->manager->getPath($media);
    }

    /**
     * @return array<string, mixed>|string|null
     */
    public function mediaMeta(int|string|MediaInterface $media, ?string $key = null, string|null $default = null): array|string|null
    {
        $media = $this->getMedia($media);
        if (!$media instanceof MediaInterface) {
            return null;
        }

        if ($key) {
            return $media->getMeta($key, $default);
        }

        return $media->getMetas();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function mediaInfos(int|string|MediaInterface $media): ?array
    {
        $media = $this->getMedia($media);
        if (!$media instanceof MediaInterface) {
            return null;
        }

        $path = $media->getPath();
        $time = $media->getLastModified();
        $metas = $media->getMetas();

        try {
            return [
                'id' => $media->getId(),
                'name' => $media->getName(),
                'type' => $media->getMime(),
                'size' => $media->getSize(),
                'path' => $this->path($media),
                'download_url' => $this->downloadUrl($media),
                'storage_path' => $path,
                'last_modified' => $time,
                'last_modified_formated' => $time ? $this->manager->getHelper()->getItemTime($time) : null,
                'metas' => $metas,
            ];
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            return null;
        }
    }

    public function fileIsType(int|string|MediaInterface $media, string $compare): ?bool
    {
        $media = $this->getMedia($media);
        if (!$media instanceof MediaInterface) {
            return null;
        }

        $type = $media->getMime();

        if (null === $type) {
            return null;
        }

        return $this->manager->getHelper()->fileIsType($type, $compare);
    }

    public function getMimeIcon(string $mime_type): string
    {
        return MediaHelper::mime2icon($mime_type);
    }

    private function getMedia(int|string|MediaInterface $media): ?MediaInterface
    {
        return $this->manager->getMedia($media);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function media(int|string|MediaInterface $media, string $format = 'reference', array $options = []): string
    {
        $media = $this->getMedia($media);
        $template = null;
        if (!$media instanceof MediaInterface) {
            return '';
        }

        if ($this->fileIsType($media, 'image')) {
            $options = $this->getImageHelperProperties($media, $format, $options);
            $template = '@SyliusHappyCMSPlugin/media/render/image.html.twig';
        }

        if ($this->fileIsType($media, 'oembed')) {
            $options = $this->getOembedHelperProperties($media, $format, $options);
            $template = '@SyliusHappyCMSPlugin/media/render/oembed.html.twig';
        }

        if ($this->fileIsType($media, 'video')) {
            $options = $this->getVideoHelperProperties($media, $format, $options);
            $template = '@SyliusHappyCMSPlugin/media/render/video.html.twig';
        }

        if (null === $template) {
            return '';
        }

        try {
            return $this->twig->render($template, [
                'media' => $media,
                'format' => $format,
                'options' => $options,
            ]);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            return '';
        }
    }

    /**
     * @param array<string, mixed>|string $format
     */
    public function path(int|string|MediaInterface $media, array|string $format = 'reference'): string|null
    {
        $media = $this->getMedia($media);
        if (!$media instanceof MediaInterface) {
            return '';
        }

        if ('reference' !== $format && $this->fileIsType($media, 'image')) {
            if (is_array($format)) {
                return $this->cacheManager->getRuntimePath($media->getPath(), $format);
            }

            return $this->cacheManager->getBrowserPath($media->getPath(), $format);
        }

        if ($this->fileIsType($media, 'oembed')) {
            return $media->getMeta('url');
        }

        return $this->manager->publicUrl($media);
    }

    /**
     * @return array<string, mixed>|string|null
     */
    public function downloadUrl(int|string|MediaInterface $media): array|string|null
    {
        $media = $this->getMedia($media);
        if (!$media instanceof MediaInterface) {
            return '';
        }

        return $this->manager->downloadUrl($media);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function getVideoHelperProperties(MediaInterface $media, string $format = 'reference', array $options = []): array
    {
        $params = [
            'url' => $this->path($media, $format),
            'sources' => [
                [
                    'src' => $this->path($media, $format),
                    'type' => $media->getMime(),
                ],
            ],
        ];

        return array_merge($params, $options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function getOembedHelperProperties(MediaInterface $media, string $format = 'reference', array $options = []): array
    {
        $params = [
            'title' => $media->getMeta('title', $media->getName()),
        ];
        $code = $media->getMeta('code');
        if ('reference' === $format && is_string($code)) {
            $params['code'] = $code;
        }

        return array_merge($params, ['attributes' => $options]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function getImageHelperProperties(MediaInterface $media, string $format = 'reference', array $options = []): array
    {
        if (isset($options['srcset'], $options['picture'])) {
            throw new \LogicException("The 'srcset' and 'picture' options must not be used simultaneously.");
        }

        $params = [
            'alt' => $media->getMeta('alt', $media->getName()),
            'title' => $media->getMeta('title', $media->getName()),
        ];

        /**
         * @var array{width: int|null, height: int|null, ratio: float|null}|null $box
         */
        $box = $media->getMeta('dimensions');

        if (!$box) {
            $box = ['width' => false, 'height' => false, 'ratio' => false];
        }

        $params += [
            'ratio' => $box['ratio'] ?: null,
        ];

        if ('reference' === $format) {
            $params += [
                'src' => $this->path($media, $format),
                'width' => $box['width'] ?: null,
                'height' => $box['height'] ?: null,
            ];

            return array_merge($params, $options);
        }

        $formats = $this->getFormat($format);

        if (isset($options['srcset']) || isset($options['picture'])) {
            $set = $options['srcset'] ?? $options['picture'];
            if (\is_array($set)) {
                $srcSetFormats = [];
                $pictureParams = [];
                foreach ($set as $key => $formatName) {
                    $settings = $this->getFormat($formatName);
                    /**
                     * @var array{
                     *     filters?: array{
                     *          thumbnail?: array{
                     *              size: array{0: int|null, 1: int|null}
                     *          }
                     *     }
                     * } $formatSettings
                     */
                    $formatSettings = $settings[$formatName] ?? [];
                    if (\is_string($key) || isset($options['picture'])) {
                        $src = $this->path($media, $formatName);
                        assert(isset($formatSettings['filters']['thumbnail']), 'Thumbnail filter is not defined for format ' . $formatName);
                        [$width, $height] = $formatSettings['filters']['thumbnail']['size'];
                        $mediaQuery = \is_string($key)
                            ? $key
                            : ($width ? sprintf('(max-width: %dpx)', $width) : null);

                        $pictureParams['source'][] = ['media' => $mediaQuery, 'srcset' => $src, 'width' => $width, 'ratio' => $width ? ($height / $width * 100) : null];
                    } else {
                        if (empty($settings)) {
                            throw new \RuntimeException(sprintf('The image format "%s" is not defined.
                                Is the format registered in your ``liip_imagine.filter_sets`` configuration?', $formatName));
                        }

                        $srcSetFormats += $settings;
                    }
                }

                unset($options['srcset'], $options['picture']);

                if (!empty($pictureParams)) {
                    $params['src'] = $this->path($media, isset($formats[$format]) ? $format : 'reference');
                    usort($pictureParams['source'], static fn ($a, $b) => ($a['width'] ?: 9_999_999) <=> ($b['width'] ?: 9_999_999));
                    if (isset($params['ratio'])) {
                        /** @phpstan-ignore-next-line */
                        $params['orientation'] = ($params['ratio'] && $params['ratio'] <= 100) ? 'landscape' : 'portrait';
                    }

                    $pictureParams['source'] = array_map(static function ($source) {
                        unset($source['width'], $source['ratio']);

                        return $source;
                    }, $pictureParams['source']);
                    $pictureParams['img'] = $params + $options;
                    $params = ['picture' => $pictureParams];
                } else {
                    foreach ($srcSetFormats as $formatName => $settings) {
                        /** @var string $formatName */
                        /**
                         * @var array{
                         *     filters?: array{
                         *          thumbnail?: array{
                         *              size: array{0: int|null, 1: int|null}
                         *          }
                         *     }
                         * } $settings
                         */
                        assert(isset($settings['filters']['thumbnail']), 'Thumbnail filter is not defined for format ' . $formatName);
                        [$width, $height] = $settings['filters']['thumbnail']['size'];
                        $srcSet[] = [
                            'width' => $width ?: null,
                            'set' => sprintf(
                                '%s %dw',
                                $this->path($media, $formatName),
                                $width,
                            ),
                        ];
                    }

                    // The reference format is not in the formats list
                    $srcSet[] = [
                        'width' => $box['width'] ?: null,
                        'set' => sprintf(
                            '%s %dw',
                            $this->path($media),
                            $box['width'] ?: null,
                        ),
                    ];

                    usort($srcSet, static fn ($a, $b) => ($a['width'] ?: 9_999_999) <=> ($b['width'] ?: 9_999_999));

                    $srcSet = array_map(static fn ($source) => $source['set'], $srcSet);

                    $params['srcset'] = implode(', ', $srcSet);
                    $params['src'] = $this->path($media);
                    $params['sizes'] = sprintf('(max-width: %1$dpx) 100vw, %1$dpx', $box['width'] ?: null);
                }
            }
        } elseif (count($formats) > 1) {
            $pictureParams = [];
            foreach ($formats as $formatName => $settings) {
                /** @var string $formatName */
                /**
                 * @var array{
                 *     filters?: array{
                 *          thumbnail?: array{
                 *              size: array{0: int|null, 1: int|null}
                 *          }
                 *     }
                 * } $settings
                 */
                assert(isset($settings['filters']['thumbnail']), 'Thumbnail filter is not defined for format ' . $formatName);
                $src = $this->path($media, $formatName);
                [$width, $height] = $settings['filters']['thumbnail']['size'];
                $mediaQuery = $width ? sprintf('(max-width: %dpx)', $width) : null;
                $pictureParams['source'][] = ['media' => $mediaQuery, 'srcset' => $src, 'width' => $width, 'ratio' => $width ? ($height / $width * 100) : null];
            }

            usort($pictureParams['source'], static fn ($a, $b) => ($a['width'] ?: 9_999_999) <=> ($b['width'] ?: 9_999_999));
            $pictureParams['source'] = array_map(static function ($source) {
                unset($source['width'], $source['ratio']);

                return $source;
            }, $pictureParams['source']);
            $pictureParams['img'] = $params + $options;
            $params = ['picture' => $pictureParams];
        } elseif (isset($formats[$format])) {
            /**
             * @var array{
             *     filters?: array{
             *          thumbnail?: array{
             *              size: array{0: int|null, 1: int|null}
             *          }
             *     }
             * } $formatSettings
             */
            $formatSettings = $formats[$format];
            if (isset($formatSettings['filters']['thumbnail'])) {
                [$width, $height] = $formatSettings['filters']['thumbnail']['size'];
                $params += [
                    'width' => $width ?: null,
                    'height' => $height ?: null,
                ];
            }
            $params += [
                'src' => $this->path($media, $format),
            ];
        }

        if (isset($params['ratio'])) {
            $params['orientation'] = $params['ratio'] <= 100 ? 'landscape' : 'portrait';
        }

        return array_merge($params, $options);
    }

    /**
     * @return array<int, mixed>
     */
    private function getFormat(string $format): array
    {
        return array_filter($this->filterManager->getFilterConfiguration()->all(), static fn ($config, $key) => 'default' === $key || str_starts_with((string) $key, $format), \ARRAY_FILTER_USE_BOTH);
    }
}
