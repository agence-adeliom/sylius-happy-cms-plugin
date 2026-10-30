<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Config;

use Adeliom\SyliusHappyCMSPlugin\Repository\Config\ConfigRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Config\ConfigValueConverter;
use Adeliom\SyliusHappyCMSPlugin\Services\Config\ConfigValueResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Context\LocaleNotFoundException;
use Sylius\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\Config;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\ConfigTranslation;

final class ConfigValueResolverTest extends TestCase
{
    private ConfigRepositoryInterface&MockObject $repository;

    private ChannelContextInterface&MockObject $channelContext;

    private LocaleContextInterface&MockObject $localeContext;

    private ConfigValueResolver $resolver;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ConfigRepositoryInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->localeContext = $this->createMock(LocaleContextInterface::class);
        $this->localeContext->method('getLocaleCode')->willReturn('fr_FR');
        $localeProvider = $this->createMock(TranslationLocaleProviderInterface::class);
        $localeProvider->method('getDefaultLocaleCode')->willReturn('en_US');

        $this->resolver = new ConfigValueResolver(
            $this->repository,
            $this->channelContext,
            $this->localeContext,
            $localeProvider,
            new ConfigValueConverter(),
        );
    }

    public function testReturnsChannelOverride(): void
    {
        $this->givenConfig('text', ['fr_FR' => ['default fr', ['FR_WEB' => 'surcharge fr']]]);

        self::assertSame('surcharge fr', $this->resolver->resolve('key', 'FR_WEB'));
    }

    public function testFallsBackToDefaultWhenNoOverride(): void
    {
        $this->givenConfig('text', ['fr_FR' => ['default fr', ['FR_WEB' => 'surcharge fr']]]);

        self::assertSame('default fr', $this->resolver->resolve('key', 'EN_WEB'));
    }

    public function testUnknownChannelCodeReturnsDefault(): void
    {
        $this->givenConfig('text', ['fr_FR' => ['default fr', []]]);

        self::assertSame('default fr', $this->resolver->resolve('key', 'DOES_NOT_EXIST'));
    }

    public function testUnknownKeyReturnsNull(): void
    {
        $this->repository->method('getByKey')->willReturn(null);

        self::assertNull($this->resolver->resolve('missing', 'FR_WEB'));
    }

    public function testUsesCurrentChannelWhenNoneGiven(): void
    {
        $this->givenConfig('text', ['fr_FR' => ['default fr', ['FR_WEB' => 'surcharge fr']]]);
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getCode')->willReturn('FR_WEB');
        $this->channelContext->method('getChannel')->willReturn($channel);

        self::assertSame('surcharge fr', $this->resolver->resolve('key'));
    }

    public function testReturnsDefaultWhenNoCurrentChannel(): void
    {
        $this->givenConfig('text', ['fr_FR' => ['default fr', ['FR_WEB' => 'surcharge fr']]]);
        $this->channelContext->method('getChannel')->willThrowException(new ChannelNotFoundException());

        self::assertSame('default fr', $this->resolver->resolve('key'));
    }

    public function testAcceptsChannelObjectOrCode(): void
    {
        $this->givenConfig('text', ['fr_FR' => ['default fr', ['FR_WEB' => 'surcharge fr']]]);
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getCode')->willReturn('FR_WEB');

        self::assertSame('surcharge fr', $this->resolver->resolve('key', $channel));
        self::assertSame('surcharge fr', $this->resolver->resolve('key', 'FR_WEB'));
    }

    public function testOverrideAppliesEvenIfChannelDetachedFromConfig(): void
    {
        // la config n'a aucun channel rattaché mais porte une surcharge : la relation n'est pas consultée
        $config = $this->givenConfig('text', ['fr_FR' => ['default fr', ['OLD_CHANNEL' => 'surcharge orpheline']]]);
        self::assertTrue($config->isAvailableForAllChannels());

        self::assertSame('surcharge orpheline', $this->resolver->resolve('key', 'OLD_CHANNEL'));
    }

    public function testUsesGivenLocaleThenFallsBackToDefaultLocale(): void
    {
        $this->givenConfig('text', [
            'fr_FR' => ['default fr', ['FR_WEB' => 'surcharge fr']],
            'en_US' => ['default en', ['FR_WEB' => 'surcharge en']],
        ]);

        self::assertSame('surcharge en', $this->resolver->resolve('key', 'FR_WEB', 'en_US'));
        // locale sans traduction : repli sur la locale par défaut (en_US)
        self::assertSame('surcharge en', $this->resolver->resolve('key', 'FR_WEB', 'de_DE'));
    }

    public function testFallsBackToDefaultLocaleWhenNoCurrentLocale(): void
    {
        $this->localeContext = $this->createMock(LocaleContextInterface::class);
        $this->localeContext->method('getLocaleCode')->willThrowException(new LocaleNotFoundException());
        $this->givenConfig('text', ['en_US' => ['default en', []]]);

        self::assertSame('default en', $this->resolver->resolve('key', 'FR_WEB'));
    }

    public function testResolvingDoesNotCreateTranslations(): void
    {
        $config = $this->givenConfig('text', ['en_US' => ['default en', []]]);

        $this->resolver->resolve('key', 'FR_WEB', 'de_DE');

        self::assertCount(1, $config->getTranslations());
    }

    public function testTypedConversion(): void
    {
        $this->givenConfig('boolean', ['fr_FR' => ['0', ['FR_WEB' => '1']]]);
        self::assertTrue($this->resolver->resolve('key', 'FR_WEB'));
        self::assertFalse($this->resolver->resolve('key', 'EN_WEB'));
    }

    public function testDateConversion(): void
    {
        $this->givenConfig('date', ['fr_FR' => ['2026-01-01', ['FR_WEB' => '2026-10-29']]]);

        $date = $this->resolver->resolve('key', 'FR_WEB');

        self::assertInstanceOf(\DateTime::class, $date);
        self::assertSame('2026-10-29', $date->format('Y-m-d'));
    }

    public function testJsonAndHtmlReturnedUnchanged(): void
    {
        $json = '{"a":"é\"<b>x</b>"}';
        $html = '<p class="x">é « test » & co</p>';
        $this->givenConfig('json', ['fr_FR' => ['{}', ['FR_WEB' => $json]]]);
        self::assertSame($json, $this->resolver->resolve('key', 'FR_WEB'));

        $this->givenConfig('wysiwyg', ['fr_FR' => ['<p></p>', ['FR_WEB' => $html]]]);
        self::assertSame($html, $this->resolver->resolve('key', 'FR_WEB'));
    }

    /**
     * @param array<string, array{string, array<string, string>}> $translations locale => [default, overrides]
     */
    private function givenConfig(string $type, array $translations): Config
    {
        $config = new Config();
        $config->setKey('key');
        $config->setName('Key');
        $config->setType($type);
        foreach ($translations as $locale => [$default, $overrides]) {
            $translation = new ConfigTranslation();
            $translation->setLocale($locale);
            $translation->setValue($default);
            $translation->setChannelValues($overrides);
            $config->addTranslation($translation);
        }

        $repository = $this->createMock(ConfigRepositoryInterface::class);
        $repository->method('getByKey')->willReturn($config);
        $this->repository = $repository;
        $this->rebuildResolver();

        return $config;
    }

    private function rebuildResolver(): void
    {
        $localeProvider = $this->createMock(TranslationLocaleProviderInterface::class);
        $localeProvider->method('getDefaultLocaleCode')->willReturn('en_US');
        $this->resolver = new ConfigValueResolver($this->repository, $this->channelContext, $this->localeContext, $localeProvider, new ConfigValueConverter());
    }
}
