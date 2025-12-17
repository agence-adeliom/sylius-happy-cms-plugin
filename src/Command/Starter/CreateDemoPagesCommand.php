<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Command\Starter;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageTranslationInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\Seo;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface;
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
    name: 'happycms:starter:create-demo-pages',
    description: 'Create homepage',
)]
class CreateDemoPagesCommand extends AbstractContentCommand
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
        // Get the classes configured via sylius.resources
        /** @var array<string, array{
         *  classes: array{
         *     model: class-string,
         *     controller: class-string,
         *     repository: class-string,
         *     form: class-string,
         *     factory: class-string,
         *  }
         * }|null> $resources */
        $resources = $this->parameterBag->get('sylius.resources');

        if (!$resources) {
            $output->writeln('<error>Sylius resources configuration not found.</error>');

            return Command::FAILURE;
        }

        if (
            empty($resources['sylius_happy_cms.page']) ||
            empty($resources['sylius.channel'])
        ) {
            $output->writeln('<error>Some sylius resource configuration not found.</error>');

            return Command::FAILURE;
        }

        /** @var class-string<PageInterface> $pageClass */
        $pageClass = $resources['sylius_happy_cms.page']['classes']['model'];
        ///** @var class-string<PageTranslationInterface> $pageTranslationClass */
        //$pageTranslationClass = $resources['sylius_happy_cms.page']['translation']['classes']['model'];
        ///** @var class-string<SharedBlockInterface> $sharedBlockClass */
        //$sharedBlockClass = $resources['sylius_happy_cms.shared_block']['classes']['model'];
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

        $locales = $channel->getLocales();
        if ($locales->isEmpty()) {
            $output->writeln('<error>No locale found for channel. Please configure a locale for the channel.</error>');

            return Command::FAILURE;
        }
        $locale = $locales->first();
        $localeCode = $locale->getCode() ?? 'fr_FR';

        /** @var PageInterface|null $page */
        $page = $this->manager->getRepository($pageClass)->findOneBy(['channel' => $channel, 'template' => PageInterface::HOMEPAGE]);
        if (!$page instanceof PageInterface) {
            $page = new $pageClass();
        }
        $page->setTemplate(PageInterface::HOMEPAGE);
        $page->setChannel($channel);
        $page->setPublishState(ThreeStateStatusEnum::PUBLISHED);
        $page->setFallbackLocale($localeCode);

        // Content blocks (old way)
        $image1 = $this->createMedia(
            'pages/home',
            'image-cms.png',
            [
                'title' => 'Happy CMS Image 1',
                'alt' => 'Happy CMS Image 1',
            ],
        );
        $image2 = $this->createMedia(
            'pages/home',
            'image-2.jpg',
            [
                'title' => 'Happy CMS Image 2',
                'alt' => 'Happy CMS Image 2',
            ],
        );
        $image3 = $this->createMedia(
            'pages/home',
            'image-3.jpg',
            [
                'title' => 'Happy CMS Image 3',
                'alt' => 'Happy CMS Image 3',
            ],
        );

        $this->manager->persist($page);

        foreach ($locales as $locale) {
            $localeCode = $locale->getCode() ?? 'en_US';
            // Important to not get fallback translation and create new instances
            $page->setFallbackLocale($localeCode);
            $pageTranslation = $page->getTranslation($localeCode);
            $pageTranslation->setName('Homepage');
            $pageTranslation->setSlug(uniqid());

            // SEO
            $seo = new Seo();
            $seo->setTitle('Welcome to Sylius Happy CMS');
            $seo->setDescription('A default Happy CMS Homepage.');
            $seo->setSitemap(true);
            $seo->setKey('homepage');
            $pageTranslation->setSeo($seo);

            $flexContent = [
                'hp-flex-1' => [
                    'image' => $image1?->getId(),
                    'title' => 'TextImageCtaBlockType, consectetur adipiscing elit.',
                    'wysiwyg' => '<p>Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>',
                    'position' => '1',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\TextImageCtaBlockType',
                    'block_published' => '1',
                ],
                'hp-flex-2' => [
                    'title' => 'GalleryBlockType',
                    'wysiwyg' => '<p>Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>',
                    'images' => [$image1?->getId(), $image2?->getId(), $image3?->getId()],
                    'position' => '2',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\GalleryBlockType',
                    'block_published' => '1',
                ],
                'hp-flex-3' => [
                    'title' => 'CtaBlockType',
                    'wysiwyg' => '<p>Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>',
                    'cta_one' => [
                        'label' => 'CTA 1',
                        'link' => '/'
                    ],
                    'position' => '3',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\CtaBlockType',
                    'block_published' => '1',
                ],
                'hp-flex-4' => [
                    'title' => 'KeyFeaturesBlockType',
                    'wysiwyg' => '<p>Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>',
                    'features' => [
                        [
                            'key' => '1',
                            'text' => '1',
                            'icon' => 'star'
                        ]
                    ],
                    'position' => '4',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\KeyFeaturesBlockType',
                    'block_published' => '1',
                ],
                'hp-flex-5' => [
                    'title' => 'WysiwygBlockType',
                    'wysiwyg' => '<p>Sed do eiusmod tempor incididunt ut labore et dolore magna <b>aliqua</b>.</p>',
                    'position' => '5',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\WysiwygBlockType',
                    'block_published' => '1',
                ],
                'hp-flex-6' => [
                    'title' => 'AccordionBlockType',
                    'wysiwyg' => '<p>Sed do eiusmod tempor incididunt ut labore et dolore magna <b>aliqua</b>.</p>',
                    'cta' => [
                        'label' => 'CTA 1',
                        'link' => '/'
                    ],
                    'items' => [
                        [
                            'title' => 'Accordion 1',
                            'content' => '<p>Text 1.</p>',
                        ],
                        [
                            'title' => 'Accordion 2',
                            'content' => '<p>Text 2.</p>',
                        ]
                    ],
                    'position' => '6',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\AccordionBlockType',
                    'block_published' => '1',
                ],
            ];

            // setContent method exists in PageTranslationInterface, because it will replace by the new ContentEditableInterface
            if (method_exists($pageTranslation, 'setContent')) {
                $pageTranslation->setContent($flexContent);
            }

            $page->addTranslation($pageTranslation);
        }

        $this->manager->persist($page);
        $this->manager->flush();

        return Command::SUCCESS;
    }
}
