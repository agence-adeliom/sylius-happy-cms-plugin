<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\PreviewUrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PreviewUrlExtension extends AbstractExtension
{
    public function __construct(
        private readonly PreviewUrlGeneratorInterface $previewUrlGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('happy_cms_signed_preview_url', $this->getSignedPreviewUrl(...)),
        ];
    }

    public function getSignedPreviewUrl(CmsRoutableInterface $entity, string $resource, string $locale): string
    {
        return $this->previewUrlGenerator->generate($entity, $resource, $locale);
    }
}
