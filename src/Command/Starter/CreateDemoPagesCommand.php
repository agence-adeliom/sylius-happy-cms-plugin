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
        $imageMediaManager = $this->createMedia(
            'pages/home',
            'media-manager.png',
            [
                'title' => 'Happy Media Manager',
                'alt' => 'Happy Media Manager',
            ],
        );
        $imageContentManager = $this->createMedia(
            'pages/home',
            'content-manager.png',
            [
                'title' => 'Happy Content Manager',
                'alt' => 'Happy Content Manager',
            ],
        );
        $imageMenuManager = $this->createMedia(
            'pages/home',
            'menu-manager.png',
            [
                'title' => 'Happy Menu Manager',
                'alt' => 'Happy Menu Manager',
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
                // Hero Section
                'hp-flex-1' => [
                    'image' => $image1?->getId(),
                    'title' => 'Happy CMS: A new Content Management Solution for Sylius',
                    'headline' => 'Powerful & Flexible',
                    'wysiwyg' => '<p>Seamlessly integrated with Sylius e-commerce, Happy CMS brings enterprise-grade content management to your online store. Build beautiful pages with our intuitive visual editor and manage your content like never before.</p>',
                    'cta_one' => [
                        'label' => 'Discover the Page Builder',
                        'link' => '/',
                    ],
                    'position' => '1',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\TextImageCtaBlockType',
                    'block_published' => '1',
                ],

                // Key Features
                'hp-flex-2' => [
                    'title' => 'Why Choose Happy CMS?',
                    'headline' => 'Everything you need',
                    'wysiwyg' => '<p>Designed specifically for Sylius, Happy CMS combines powerful features with an exceptional user experience. Create, manage, and publish content effortlessly.</p>',
                    'features' => [
                        [
                            'key' => 'i18n',
                            'text' => 'Multi-language Support',
                            'icon' => 'bi bi-translate',
                        ],
                        [
                            'key' => 'Content',
                            'text' => 'Intuitive visual editor',
                            'icon' => 'bi bi-layout-text-window-reverse',
                        ],
                        [
                            'key' => 'Media',
                            'text' => 'Advanced Media Management',
                            'icon' => 'bi bi-images',
                        ],
                        [
                            'key' => 'SEO',
                            'text' => 'Pre-build SEO features',
                            'icon' => 'bi bi-graph-up',
                        ],
                    ],
                    'position' => '2',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\KeyFeaturesBlockType',
                    'block_published' => '1',
                ],

                // Page Builder Presentation
                'hp-flex-3' => [
                    'title' => 'A Simple Visual Page Builder',
                    'headline' => 'Build Pages Visually',
                    'wysiwyg' => '<p>Our state-of-the-art page builder transforms content creation. With real-time preview, blocks configuration, and an intuitive interface, you can design stunning pages without writing a single line of code.</p><ul><li><strong>Live Preview:</strong> See your changes instantly as you build</li><li><strong>Pre-built Blocks:</strong> Choose from a rich library of content blocks</li><li><strong>Responsive Design:</strong> Preview on desktop, tablet, and mobile</li><li><strong>Version Control:</strong> Save drafts and publish when ready</li></ul>',
                    'cta_one' => [
                        'label' => 'Explore Features',
                        'link' => '/',
                    ],
                    'cta_two' => [
                        'label' => 'View Documentation',
                        'link' => '/',
                    ],
                    'position' => '3',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\TextCtaBlockType',
                    'block_published' => '1',
                ],

                // Gallery Showcase
                'hp-flex-4' => [
                    'title' => 'See Happy CMS in Action',
                    'wysiwyg' => '<p>Discover the power and flexibility of Happy CMS through these interface screenshots. From content editing to media management, every feature is designed with you in mind.</p>',
                    'images' => [$imageContentManager?->getId(), $imageMenuManager?->getId(), $imageMediaManager?->getId()],
                    'position' => '4',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\GalleryBlockType',
                    'block_published' => '1',
                ],

                // Sylius Integration
                'hp-flex-7' => [
                    'title' => 'Perfect Sylius Integration',
                    'content' => '<p>Happy CMS is <strong>built specifically for Sylius</strong>, ensuring perfect compatibility and native integration. Leverage Sylius\'s powerful e-commerce features while managing your content with ease.</p><p>Whether you\'re creating product landing pages, blog posts, or marketing campaigns, Happy CMS seamlessly integrates with your Sylius store\'s channels, locales, and taxonomies. Take advantage of familiar Sylius conventions while enjoying a superior content management experience.</p><p>From simple pages to complex layouts, Happy CMS gives you the flexibility to create exactly what your business needs.</p>',
                    'position' => '5',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\WysiwygBlockType',
                    'block_published' => '1',
                ],

                // Content Management Section
                'hp-flex-5' => [
                    'image' => $imageContentManager?->getId(),
                    'img_right' => false,
                    'title' => 'Advanced Page Builder',
                    'headline' => 'Organize Your Content',
                    'wysiwyg' => '<p>Our state-of-the-art page builder transforms content creation. With real-time preview, blocks configurator, and an intuitive interface, you can design stunning pages without writing a single line of code.</p><ul><li><strong>Live Preview:</strong> See your changes instantly as you build</li><li><strong>Pre-built Blocks:</strong> Choose from a rich library of content blocks</li><li><strong>Responsive Design:</strong> Preview on desktop, tablet, and mobile</li><li><strong>Version Control:</strong> Save drafts and publish when ready</li></ul>',
                    'cta_one' => [
                        'label' => 'Learn More',
                        'link' => 'https://github.com/agence-adeliom/sylius-happy-cms-plugin',
                    ],
                    'position' => '6',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\TextImageCtaBlockType',
                    'block_published' => '1',
                ],

                // Media Management Section
                'hp-flex-8' => [
                    'image' => $imageMediaManager?->getId(),
                    'img_right' => true,
                    'title' => 'Advanced Media Management',
                    'headline' => 'Organize Your Assets',
                    'wysiwyg' => '<p>Our built-in media manager makes organizing images, documents, and media files effortless. With features like smart folders, batch uploads, and SEO metadata management, you have complete control over your digital assets.</p>',
                    'cta_one' => [
                        'label' => 'Learn More',
                        'link' => 'https://github.com/agence-adeliom/sylius-happy-cms-plugin',
                    ],
                    'position' => '7',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\TextImageCtaBlockType',
                    'block_published' => '1',
                ],

                // FAQ Section
                'hp-flex-6' => [
                    'title' => 'Frequently Asked Questions',
                    'wysiwyg' => '<p>Learn more about Happy CMS and how it can transform your Sylius store into a powerful content platform.</p>',
                    'cta' => [
                        'label' => 'View Full Documentation',
                        'link' => '/',
                    ],
                    'items' => [
                        [
                            'title' => 'Is Happy CMS compatible with all Sylius versions?',
                            'content' => '<p>Happy CMS is designed to work seamlessly with Sylius 2+. We maintain compatibility with the latest Sylius releases and provide upgrade guides for smooth transitions.</p>',
                        ],
                        [
                            'title' => 'Can I customize the available content blocks?',
                            'content' => '<p>Absolutely! Happy CMS is highly extensible. You can create custom blocks to match your specific needs, integrate third-party services, and extend the page builder with your own components.</p>',
                        ],
                        [
                            'title' => 'Does Happy CMS support multi-language content?',
                            'content' => '<p>Yes! Multi-language support is built into the core of Happy CMS. Manage translations effortlessly, copy content between languages, and even use AI-powered translation features.</p>',
                        ],
                        [
                            'title' => 'How does the visual page builder work?',
                            'content' => '<p>Our visual page builder provides a real-time preview of your pages alongside an intuitive editing interface. Add, edit, and arrange content blocks with simplicity while seeing your changes instantly.</p>',
                        ],
                    ],
                    'position' => '8',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\AccordionBlockType',
                    'block_published' => '1',
                ],

                // Final CTA
                'hp-flex-9' => [
                    'title' => 'Ready to Transform Your Content Management?',
                    'wysiwyg' => '<p>Join hundreds of Sylius stores already using Happy CMS to create exceptional content experiences. Get started today and see the difference a purpose-built CMS can make.</p>',
                    'cta_one' => [
                        'label' => 'Get Started Now',
                        'link' => 'https://github.com/agence-adeliom/sylius-happy-cms-plugin',
                    ],
                    'position' => '9',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\CtaBlockType',
                    'block_published' => '1',
                ],

                // SEO Text
                'hp-flex-10' => [
                    'wysiwyg' => '<p><strong>Happy CMS for Sylius</strong> is the complete content management solution designed specifically for Sylius e-commerce platforms. With its revolutionary visual page builder, advanced media management, multi-language support, and SEO optimization tools, Happy CMS empowers merchants to create stunning content experiences that drive conversions. Built by <strong>Agence Adeliom</strong>, Happy CMS seamlessly integrates with Sylius\'s architecture while providing an intuitive interface for content creators. Whether you\'re building product landing pages, managing blog content, or creating marketing campaigns, Happy CMS delivers the flexibility and power you need to succeed in e-commerce.</p>',
                    'position' => '10',
                    'block_type' => 'Adeliom\\SyliusHappyCMSPlugin\\Block\\SeoBlockType',
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
