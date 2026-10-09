<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Twig\Seo;

use Adeliom\SyliusHappyCMSPlugin\Entity\Seo\Seo;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\BreadcrumbCollection;
use Adeliom\SyliusHappyCMSPlugin\Twig\Seo\SeoExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Twig\Seo\Fixtures\ProjectSeo;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;

final class SeoExtensionTest extends TestCase
{
    public function testSeoMetasAcceptsAnySeoInterfaceImplementation(): void
    {
        $seo = new ProjectSeo();
        $seo->setDescription('Project description');
        $seo->setCanonical('https://example.com/page');

        $html = $this->render('{{ seo_metas(seo) }}', ['seo' => $seo]);

        self::assertStringContainsString('<meta name="description" content="Project description">', $html);
        self::assertStringContainsString('<link rel="canonical" href="https://example.com/page" />', $html);
    }

    public function testSeoMetasStillAcceptsThePluginSeo(): void
    {
        $seo = new Seo();
        $seo->setDescription('Plugin description');
        $seo->setKeywords(null);
        $seo->setCanonical(null);
        $seo->setCover(null);
        $seo->setKey(null);

        self::assertStringContainsString(
            '<meta name="description" content="Plugin description">',
            $this->render('{{ seo_metas(seo) }}', ['seo' => $seo]),
        );
    }

    public function testSeoMetasRendersNothingWithoutSeo(): void
    {
        self::assertSame('', trim($this->render('{{ seo_metas(null) }}')));
    }

    public function testSeoTitleReadsTheTitleOfAnySeoInterfaceImplementation(): void
    {
        $seo = new ProjectSeo();
        $seo->setTitle('Project title');

        self::assertSame('Project title | Shop', $this->render('{{ seo_title(seo) }}', ['seo' => $seo]));
    }

    public function testSeoTitleAcceptsAStringOrNull(): void
    {
        self::assertSame('Home | Shop', $this->render('{{ seo_title("Home") }}'));
        self::assertSame(' | Shop', $this->render('{{ seo_title(null) }}'), 'null used to throw a TypeError');
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(string $template, array $context = []): string
    {
        $twig = new Environment(new ArrayLoader([
            'page' => $template,
            '@SyliusHappyCMSPlugin/front/seo/block-metas.html.twig' => (string) file_get_contents(__DIR__ . '/../../../../templates/front/seo/block-metas.html.twig'),
        ]));

        $twig->addExtension(new SeoExtension(
            $twig,
            new EventDispatcher(),
            new BreadcrumbCollection(),
            ['separator' => '|', 'suffix' => 'Shop'],
            [],
        ));
        // stands for the media extension of the plugin, out of the scope of these tests
        $twig->addFilter(new TwigFilter('resolve_media', static fn (mixed $media): ?string => null));

        return $twig->render('page', $context);
    }
}
