<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewRequestValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PreviewLabelsProvider implements PreviewLabelsProviderInterface
{
    private const DOMAIN = 'messages';

    /** @var \WeakMap<Request, array{locale: ?string, labels: ?PreviewLabels}> */
    private \WeakMap $resolved;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        private readonly SignedPreviewRequestValidatorInterface $validator,
    ) {
        $this->resolved = new \WeakMap();
    }

    public function labels(): PreviewLabels
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $this->buildLabels(null);
        }

        $this->resolve($request);

        return $this->resolved[$request]['labels'] ??= $this->buildLabels($this->resolved[$request]['locale']);
    }

    public function blockName(BlockTypeInterface|SharedBlockTypeInterface $block): string
    {
        $request = $this->requestStack->getMainRequest();
        $locale = null === $request ? null : $this->resolve($request);

        return $this->translator->trans($block->getName(), [], self::DOMAIN, $locale);
    }

    private function resolve(Request $request): ?string
    {
        if (!isset($this->resolved[$request])) {
            $context = $this->validator->validate($request);

            // The language of the person editing, else the one of the request. The request itself is left untouched.
            $this->resolved[$request] = ['locale' => $context->adminLocale ?? $request->getLocale(), 'labels' => null];
        }

        return $this->resolved[$request]['locale'];
    }

    private function buildLabels(?string $locale): PreviewLabels
    {
        $defaults = new PreviewLabels();

        return new PreviewLabels(
            $this->translate('sylius_happy_cms.page_builder.edit_block', $defaults->editBlock, $locale),
            $this->translate('sylius_happy_cms.page_builder.preview_overlay.unpublished_block', $defaults->unpublishedBlock, $locale),
            $this->translate('sylius_happy_cms.page_builder.preview_overlay.deleted_block', $defaults->deletedBlock, $locale),
            $this->translate('sylius_happy_cms.page_builder.preview_overlay.empty_block', $defaults->emptyBlock, $locale),
        );
    }

    private function translate(string $key, string $default, ?string $locale): string
    {
        $translated = $this->translator->trans($key, [], self::DOMAIN, $locale);

        // An untranslated key comes back as is: keep the English text instead of showing a key.
        return $translated === $key ? $default : $translated;
    }
}
