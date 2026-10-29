<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\Config\ConfigAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\Config;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\ConfigTranslation;

final class ConfigChannelValuesFormTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testNoChannelFieldWhenNoChannelExists(): void
    {
        $this->createLocales();
        $config = $this->createConfig('text');

        $form = $this->createForm($config);

        self::assertSame([], $this->channelFieldNames($form, 'text', 'fr_FR'));
        self::assertTrue($form->get('translations_text')->get('fr_FR')->has('value'));
    }

    public function testOneFieldPerEligibleChannelInEachLocale(): void
    {
        $this->createLocales();
        $fr = $this->createChannel('FR_WEB');
        $en = $this->createChannel('EN_WEB');
        $this->createChannel('OTHER');
        $config = $this->createConfig('text');
        $config->addChannel($fr);
        $config->addChannel($en);
        $this->entityManager->flush();

        $form = $this->createForm($config);

        $names = [
            ...$this->channelFieldNames($form, 'text', 'fr_FR'),
            ...$this->channelFieldNames($form, 'text', 'en_US'),
        ];
        sort($names);
        self::assertSame(['channel-EN_WEB', 'channel-EN_WEB', 'channel-FR_WEB', 'channel-FR_WEB'], $names);
    }

    public function testAllChannelsOfferedWhenConfigHasNone(): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $this->createChannel('EN_WEB');
        $config = $this->createConfig('text');

        $form = $this->createForm($config);

        $names = $this->channelFieldNames($form, 'text', 'fr_FR');
        sort($names);
        self::assertSame(['channel-EN_WEB', 'channel-FR_WEB'], $names);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function typesWithSpecificFields(): iterable
    {
        yield 'wysiwyg' => ['wysiwyg'];
        yield 'image' => ['image'];
        yield 'json' => ['json'];
    }

    /**
     * @dataProvider typesWithSpecificFields
     */
    public function testFieldUsesSameTypeAsDefaultValue(string $type): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $config = $this->createConfig($type);

        $form = $this->createForm($config);

        $entry = $form->get('translations_' . $type)->get('fr_FR');
        self::assertSame(
            $entry->get('value')->getConfig()->getType()->getInnerType()::class,
            $entry->get('channel-FR_WEB')->getConfig()->getType()->getInnerType()::class,
        );
    }

    public function testBooleanChannelFieldOffersInheritYesNo(): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $config = $this->createConfig('boolean', '1');

        $form = $this->createForm($config);

        $field = $form->get('translations_boolean')->get('fr_FR')->get('channel-FR_WEB');
        self::assertInstanceOf(ChoiceType::class, $field->getConfig()->getType()->getInnerType());
        self::assertSame(['1', '0'], array_values($field->getConfig()->getOption('choices')));
    }

    public function testSubmitStoresOverrideAndKeepsDefault(): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $config = $this->createConfig('text', 'default');

        $form = $this->createForm($config);
        $form->submit($this->payload('text', ['value' => 'default', 'channel-FR_WEB' => 'surcharge fr']), false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $translation = $this->reloadTranslation();
        self::assertSame('default', $translation->getValue());
        self::assertSame('surcharge fr', $translation->getChannelValue('FR_WEB'));
    }

    public function testEmptyChannelFieldRemovesKey(): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $config = $this->createConfig('text', 'default', ['FR_WEB' => 'ancienne']);

        $form = $this->createForm($config);
        $form->submit($this->payload('text', ['value' => 'default', 'channel-FR_WEB' => '']), false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $translation = $this->reloadTranslation();
        self::assertSame('default', $translation->getValue());
        self::assertSame([], $translation->getChannelValues());
    }

    public function testUnknownKeysArePreserved(): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $config = $this->createConfig('text', 'default', ['FR_WEB' => 'ancienne', 'DELETED_CHANNEL' => 'orpheline']);

        $form = $this->createForm($config);
        $form->submit($this->payload('text', ['value' => 'default', 'channel-FR_WEB' => 'nouvelle']), false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $translation = $this->reloadTranslation();
        self::assertSame(
            ['FR_WEB' => 'nouvelle', 'DELETED_CHANNEL' => 'orpheline'],
            $translation->getChannelValues(),
        );
    }

    public function testExistingConfigWithoutOverrideUnchanged(): void
    {
        $this->createLocales();
        $config = $this->createConfig('text', 'default');

        $form = $this->createForm($config);
        $form->submit($this->payload('text', ['value' => 'modifiée']), false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $translation = $this->reloadTranslation();
        self::assertSame('modifiée', $translation->getValue());
        self::assertSame([], $translation->getChannelValues());
    }

    public function testChannelCodeCollidingWithAPropertyNameIsStored(): void
    {
        $this->createLocales();
        $this->createChannel('values');
        $this->createConfig('text', 'default');

        $form = $this->createForm($this->reloadConfig());
        $form->submit($this->payload('text', ['value' => 'default', 'channel-values' => 'surcharge']), false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        self::assertSame('surcharge', $this->reloadTranslation()->getChannelValue('values'));
    }

    public function testBooleanOverrideCanBeFalseWhileDefaultIsTrue(): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $this->createConfig('boolean', '1');

        $form = $this->createForm($this->reloadConfig());
        $form->submit($this->payload('boolean', ['value' => '1', 'channel-FR_WEB' => '0']), false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $translation = $this->reloadTranslation();
        self::assertSame('1', $translation->getValue());
        self::assertSame('0', $translation->getChannelValue('FR_WEB'));
    }

    public function testBooleanOverrideLeftOnInheritIsNotStored(): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $this->createConfig('boolean', '1', ['FR_WEB' => '0']);

        $form = $this->createForm($this->reloadConfig());
        $form->submit($this->payload('boolean', ['value' => '1', 'channel-FR_WEB' => '']), false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        self::assertSame([], $this->reloadTranslation()->getChannelValues());
    }

    public function testDateOverrideIsStoredAsFormattedString(): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $this->createConfig('date');

        $form = $this->createForm($this->reloadConfig());
        $form->submit($this->payload('date', ['channel-FR_WEB' => '2026-10-29']), false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        self::assertSame('2026-10-29', $this->reloadTranslation()->getChannelValue('FR_WEB'));
    }

    public function testCreatingAConfigStoresOverrides(): void
    {
        $this->createLocales();
        $this->createChannel('FR_WEB');
        $config = new Config();

        $form = $this->createForm($config);
        $form->submit($this->payload('text', ['value' => 'default', 'channel-FR_WEB' => 'surcharge fr']), false);
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        $this->entityManager->persist($config);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $translation = $this->reloadTranslation();
        self::assertSame('default', $translation->getValue());
        self::assertSame('surcharge fr', $translation->getChannelValue('FR_WEB'));
    }

    /**
     * @param array<string, string|array<string, string>> $localeData
     *
     * @return array<string, mixed>
     */
    private function payload(string $type, array $localeData): array
    {
        return [
            'key' => 'site_title',
            'name' => 'Site title',
            'description' => '',
            'type' => $type,
            'channels' => [],
            'translations_' . $type => ['fr_FR' => $localeData],
        ];
    }

    private function createForm(Config $config): FormInterface
    {
        $formFactory = self::getContainer()->get('form.factory');
        self::assertInstanceOf(FormFactoryInterface::class, $formFactory);

        return $formFactory->create(ConfigAdmin::class, $config, ['data_class' => Config::class, 'csrf_protection' => false]);
    }

    /**
     * @return list<string>
     */
    private function channelFieldNames(FormInterface $form, string $type, string $locale): array
    {
        $names = [];
        foreach ($form->get('translations_' . $type)->get($locale) as $name => $child) {
            if (str_starts_with((string) $name, 'channel-')) {
                $names[] = (string) $name;
            }
        }

        return $names;
    }

    /**
     * @param array<string, string> $channelValues
     */
    private function createConfig(string $type, ?string $value = null, array $channelValues = []): Config
    {
        $config = new Config();
        $config->setKey('site_title');
        $config->setName('Site title');
        $config->setType($type);
        $config->setCurrentLocale('fr_FR');
        $config->setFallbackLocale('fr_FR');
        /** @var ConfigTranslation $translation */
        $translation = $config->getTranslation('fr_FR');
        $translation->setValue($value);
        $translation->setChannelValues($channelValues);
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $config;
    }

    private function reloadConfig(): Config
    {
        $this->entityManager->clear();
        $config = $this->entityManager->getRepository(Config::class)->findOneBy(['key' => 'site_title']);
        self::assertInstanceOf(Config::class, $config);

        return $config;
    }

    private function reloadTranslation(): ConfigTranslation
    {
        $translation = $this->entityManager->getRepository(ConfigTranslation::class)->findOneBy(['locale' => 'fr_FR']);
        self::assertInstanceOf(ConfigTranslation::class, $translation);

        return $translation;
    }

    private function createLocales(): void
    {
        foreach (['en_US', 'fr_FR'] as $code) {
            $locale = new Locale();
            $locale->setCode($code);
            $this->entityManager->persist($locale);
        }
        $currency = new Currency();
        $currency->setCode('EUR');
        $this->entityManager->persist($currency);
        $this->entityManager->flush();
    }

    private function createChannel(string $code): Channel
    {
        $channel = new Channel();
        $channel->setCode($code);
        $channel->setName($code);
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setDefaultLocale($this->entityManager->getRepository(Locale::class)->findOneBy(['code' => 'fr_FR']));
        $channel->setBaseCurrency($this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'EUR']));
        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        return $channel;
    }
}
