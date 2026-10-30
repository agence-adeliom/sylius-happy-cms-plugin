<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Config;

use Adeliom\SyliusHappyCMSPlugin\Entity\Config\ConfigTranslationInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Context\LocaleNotFoundException;
use Sylius\Resource\Translation\Provider\TranslationLocaleProviderInterface;

/**
 * Lit la valeur d'une config : surcharge du channel pour la locale, sinon valeur par défaut.
 *
 * La relation `channels` de la config n'est volontairement pas consultée (pas de filtrage front).
 */
final class ConfigValueResolver
{
    public function __construct(
        private readonly ConfigRepositoryInterface $configRepository,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
        private readonly TranslationLocaleProviderInterface $translationLocaleProvider,
        private readonly ConfigValueConverter $converter,
    ) {
    }

    public function resolve(string $key, ChannelInterface|string|null $channel = null, ?string $locale = null): mixed
    {
        $config = $this->configRepository->getByKey($key);
        $type = $config?->getType();
        if (null === $config || null === $type) {
            return null;
        }

        $translation = $this->findTranslation($config->getTranslations()->toArray(), $locale ?? $this->getCurrentLocale());
        if (null === $translation) {
            return null;
        }

        $channelCode = $this->getChannelCode($channel);
        $rawValue = (null !== $channelCode ? $translation->getChannelValue($channelCode) : null) ?? $translation->getValue();

        return $this->converter->convert($type, $rawValue);
    }

    /**
     * Cherche la traduction de la locale puis celle de la locale par défaut, sans jamais en créer
     * (`getTranslation()` de Sylius ajoute une traduction vide à la config si elle manque).
     *
     * @param array<array-key, mixed> $translations
     */
    private function findTranslation(array $translations, string $locale): ?ConfigTranslationInterface
    {
        foreach ([$locale, $this->translationLocaleProvider->getDefaultLocaleCode()] as $candidate) {
            foreach ($translations as $translation) {
                if ($translation instanceof ConfigTranslationInterface && $translation->getLocale() === $candidate) {
                    return $translation;
                }
            }
        }

        return null;
    }

    private function getCurrentLocale(): string
    {
        try {
            return $this->localeContext->getLocaleCode();
        } catch (LocaleNotFoundException) {
            return $this->translationLocaleProvider->getDefaultLocaleCode();
        }
    }

    private function getChannelCode(ChannelInterface|string|null $channel): ?string
    {
        if ($channel instanceof ChannelInterface) {
            return $channel->getCode();
        }

        if (is_string($channel)) {
            return $channel;
        }

        try {
            return $this->channelContext->getChannel()->getCode();
        } catch (ChannelNotFoundException) {
            return null;
        }
    }
}
