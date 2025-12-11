<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Command\Starter;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Menu\MenuItemTranslationInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'happycms:starter:create-demo-menu',
    description: 'Create a menu for demo purposes',
)]
class CreateDemoMenuCommand extends AbstractContentCommand
{
    public function __construct(
        MediaManager $mediaManager,
        KernelInterface $kernel,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $manager,
        LoggerInterface $logger,
    ) {
        parent::__construct($mediaManager, $kernel, $parameterBag, $manager, $logger);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // get the classes configured via sylius.resources
        /** @var array<string, array{
         *  classes: array{
         *     model: class-string,
         *     controller: class-string,
         *     repository: class-string,
         *     form: class-string,
         *     factory: class-string,
         *  },
         *  translation: array{
         *   classes: array{
         *      model: class-string,
         *      controller: class-string,
         *      repository: class-string,
         *      form: class-string,
         *      factory: class-string,
         *   }
         *  }
         * }|null> $resources */
        $resources = $this->parameterBag->get('sylius.resources');

        if (!$resources) {
            $output->writeln('<error>Sylius resources configuration not found.</error>');

            return Command::FAILURE;
        }

        if (
            empty($resources['sylius_happy_cms.menu']) ||
            empty($resources['sylius_happy_cms.menu_item']) ||
            empty($resources['sylius.channel'])
        ) {
            $output->writeln('<error>Some sylius resource configuration not found.</error>');

            return Command::FAILURE;
        }

        /** @var class-string<MenuInterface> $menuClass */
        $menuClass = $resources['sylius_happy_cms.menu']['classes']['model'];
        /** @var class-string<MenuItemInterface> $menuItemClass */
        $menuItemClass = $resources['sylius_happy_cms.menu_item']['classes']['model'];
        /** @var class-string<MenuItemTranslationInterface> $menuItemTranslationClass */
        $menuItemTranslationClass = $resources['sylius_happy_cms.menu_item']['translation']['classes']['model'];
        /** @var class-string<ChannelInterface> $channelClass */
        $channelClass = $resources['sylius.channel']['classes']['model'];

        $channelRepository = $this->manager->getRepository(ChannelInterface::class);
        if ($channelClass !== $channelRepository->getClassName()) {
            $output->writeln('<error>Channel repository not found.</error>');

            return Command::FAILURE;
        }

        // Get the first default channel
        /** @var ChannelInterface|null $channel */
        $channel = $channelRepository->findOneBy([]);
        if (!$channel instanceof ChannelInterface) {
            $output->writeln('<error>No channel found. Please create a channel first.</error>');

            return Command::FAILURE;
        }

        // Loop through channel locales to create page translation for each locale
        // GetLocales is not part of ChannelInterface, so we need to check if the method exists
        if (!method_exists($channel, 'getLocales')) {
            $output->writeln('<error>Channel locales not found. Please configure locales for the channel.</error>');

            return Command::FAILURE;
        }

        // Get locale for the channel
        $locales = $channel->getLocales();
        if ($locales->isEmpty()) {
            $output->writeln('<error>No locale found for channel. Please configure a locale for the channel.</error>');

            return Command::FAILURE;
        }
        $locale = $locales->first();
        $localeCode = $locale->getCode() ?? 'en_US';

        // Menu main home page
        /** @var MenuInterface $menu */
        $menu = new $menuClass();
        $menu->setCode('main');
        $menu->setName('Main menu');
        $menu->setStatus(true);
        $this->manager->persist($menu);
        $this->manager->flush();
        $items = [
            [
                'name' => 'Home',
                'url' => '/en_US',
            ],
            [
                'name' => 'About Us',
                'url' => '/en_US/about-us',
            ],
            [
                'name' => 'Services',
                'url' => '/en_US/services',
                'children' => [
                    [
                        'name' => 'Consulting',
                        'url' => '/en_US/services/consulting',
                    ],
                    [
                        'name' => 'Development',
                        'url' => '/en_US/services/development',
                    ],
                    [
                        'name' => 'Design',
                        'url' => '/en_US/services/design',
                    ],
                ],
            ],
            [
                'name' => 'Blog',
                'url' => '/en_US/blog',
            ],
            [
                'name' => 'Contact',
                'url' => '/en_US/contact',
            ],
        ];
        /** @var MenuItemInterface|null $rootItem */
        $rootItem = $this->manager->getRepository($menuItemClass)->findOneBy([
            'menu' => $menu,
            'parent' => null,
        ]);
        $this->addItems($items, $rootItem, $menu, $menuItemClass, $menuItemTranslationClass, $localeCode);
        $this->manager->flush();

        // Menu footer
        /** @var MenuInterface $menuFooter */
        $menuFooter = new $menuClass();
        $menuFooter->setCode('footer');
        $menuFooter->setName('Menu footer');
        $menuFooter->setStatus(true);
        $this->manager->persist($menuFooter);
        $this->manager->flush();
        $items = [
            [
                'name' => 'Privacy Policy',
                'url' => '/en_US/privacy-policy',
            ],
            [
                'name' => 'Terms of Service',
                'url' => '/en_US/terms-of-service',
            ],
            [
                'name' => 'Cookie Policy',
                'url' => '/en_US/cookie-policy',
            ],
            [
                'name' => 'Help Center',
                'url' => '/en_US/help-center',
            ],
        ];
        /** @var MenuItemInterface|null $rootItemFooter */
        $rootItemFooter = $this->manager
            ->getRepository($menuItemClass)
            ->findOneBy([
                'menu' => $menuFooter,
                'parent' => null,
            ]);
        $this->addItems($items, $rootItemFooter, $menuFooter, $menuItemClass, $menuItemTranslationClass, $localeCode);
        $this->manager->flush();

        return Command::SUCCESS;
    }

    /**
     * @param array{
     *     name: string,
     *     url?: string,
     *     className?: string,
     *     icon?: string,
     *     children?: mixed
     * }[] $items
     * @param class-string<MenuItemInterface> $menuItemClass
     * @param class-string<MenuItemTranslationInterface> $menuItemTranslationClass
     */
    private function addItems(
        array $items,
        ?MenuItemInterface $menuItem,
        MenuInterface $menu,
        string $menuItemClass,
        string $menuItemTranslationClass,
        string $localeCode,
    ): void {
        foreach ($items as $key => $item) {
            /** @var MenuItemInterface $childMenuItem */
            $childMenuItem = new $menuItemClass();
            /** @var MenuItemTranslationInterface $childMenuItemTrans */
            $childMenuItemTrans = new $menuItemTranslationClass();
            $childMenuItem->setMenu($menu);
            $childMenuItem->setPublishState(ThreeStateStatusEnum::PUBLISHED);
            $childMenuItemTrans->setName($item['name']);
            $childMenuItemTrans->setLocale($localeCode);
            $childMenuItem->setPosition($key);
            if (isset($item['url']) && method_exists($childMenuItemTrans, 'setUrl')) {
                $childMenuItemTrans->setUrl($item['url']);
            }
            if (isset($item['className']) && method_exists($childMenuItem, 'setClassAttribute')) {
                $childMenuItem->setClassAttribute($item['className']);
            }
            if (isset($item['icon']) && method_exists($childMenuItem, 'setIcon')) {
                $childMenuItem->setIcon($item['icon']);
            }
            $childMenuItem->addTranslation($childMenuItemTrans);
            $childMenuItem->setParent($menuItem);
            if (isset($item['children']) && is_array($item['children'])) {
                /** @var array{
                 *     name: string,
                 *     url?: string,
                 *     className?: string,
                 *     icon?: string,
                 *     children?: mixed
                 * }[] $children
                 */
                $children = $item['children'];
                $this->addItems($children, $childMenuItem, $menu, $menuItemClass, $menuItemTranslationClass, $localeCode);
            }
            $this->manager->persist($childMenuItem);
        }
    }
}
