<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Support;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\Page;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\PageContentBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\PageTranslation;

/**
 * Builds the smallest catalog a page preview needs: one channel (fr_FR, en_US) on "localhost", published pages and an admin.
 */
final class PreviewPageFactory
{
    private ?ChannelInterface $channel = null;

    /**
     * @param non-empty-list<string> $localeCodes locales of the channel, the first one is its default locale
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityManagerInterface $entityManager,
        private readonly array $localeCodes = ['fr_FR', 'en_US'],
    ) {
    }

    public function channel(): ChannelInterface
    {
        if (null !== $this->channel) {
            return $this->channel;
        }

        /** @var CurrencyInterface $currency */
        $currency = $this->factory('sylius.factory.currency')->createNew();
        $currency->setCode('EUR');
        $this->entityManager->persist($currency);

        $locales = [];
        foreach ($this->localeCodes as $code) {
            /** @var LocaleInterface $locale */
            $locale = $this->factory('sylius.factory.locale')->createNew();
            $locale->setCode($code);
            $this->entityManager->persist($locale);
            $locales[] = $locale;
        }

        /** @var ChannelInterface $channel */
        $channel = $this->factory('sylius.factory.channel')->createNew();
        $channel->setCode('WEB');
        $channel->setName('Web');
        $channel->setHostname('localhost');
        $channel->setEnabled(true);
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setBaseCurrency($currency);
        $channel->addCurrency($currency);
        $channel->setDefaultLocale($locales[0]);
        foreach ($locales as $locale) {
            $channel->addLocale($locale);
        }
        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        return $this->channel = $channel;
    }

    public function page(string $slug, string $locale = 'fr_FR'): Page
    {
        $page = new Page();
        $page->setChannel($this->channel());
        $page->setPublishState(ThreeStateStatusEnum::PUBLISHED);
        $page->setCurrentLocale($locale);
        $page->setFallbackLocale($locale);

        $translation = new PageTranslation();
        $translation->setLocale($locale);
        $translation->setName(ucfirst($slug));
        $translation->setSlug($slug);
        $page->addTranslation($translation);

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        return $page;
    }

    /**
     * A published page with one content block of the given block type (class name), published in the preview.
     */
    public function pageWithBlock(string $slug, string $locale, string $blockType): Page
    {
        $page = $this->page($slug, $locale);

        $block = new PageContentBlock();
        $block->setType($blockType);
        $block->setLocale($locale);
        $block->setPosition(0);
        $block->setPreviewPosition(0);
        $block->setDraftData([]);
        $block->setPublishedData([]);
        $block->setPreviewPublishState(ThreeStateStatusEnum::PUBLISHED);
        $block->setContentOwner($page);
        $page->addContentBlock($block);

        $this->entityManager->persist($block);
        $this->entityManager->flush();

        return $page;
    }

    /**
     * @param list<string> $roles
     */
    public function admin(array $roles = ['ROLE_ADMINISTRATION_ACCESS'], string $localeCode = 'fr_FR'): AdminUserInterface
    {
        /** @var AdminUserInterface $admin */
        $admin = $this->factory('sylius.factory.admin_user')->createNew();
        $admin->setUsername('builder');
        $admin->setEmail('builder@example.test');
        $admin->setPlainPassword('builder');
        $admin->setEnabled(true);
        $admin->setLocaleCode($localeCode);
        foreach ($roles as $role) {
            $admin->addRole($role);
        }

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        return $admin;
    }

    /**
     * @return FactoryInterface<object>
     */
    private function factory(string $id): FactoryInterface
    {
        $factory = $this->container->get($id);
        \assert($factory instanceof FactoryInterface);

        return $factory;
    }
}
