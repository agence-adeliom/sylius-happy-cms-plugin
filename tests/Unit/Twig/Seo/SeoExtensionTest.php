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

    public function testTheMetasAndBreadcrumbTemplatesCanBeReplaced(): void
    {
        $seo = new ProjectSeo();
        $seo->setDescription('Project description');
        $breadcrumb = new BreadcrumbCollection();
        $breadcrumb->addSimpleItem('Home', '/');

        $templates = [
            'project/metas.html.twig' => 'project metas: {{ data.description }}',
            'project/breadcrumb.html.twig' => 'project breadcrumb: {{ data|map(item => item.linkName)|join(",") }}',
        ];
        $options = ['templates' => ['metas' => 'project/metas.html.twig', 'breadcrumb' => 'project/breadcrumb.html.twig'], 'breadcrumb' => $breadcrumb];

        self::assertSame('project metas: Project description', $this->render('{{ seo_metas(seo) }}', ['seo' => $seo], $templates, $options));
        self::assertSame('project breadcrumb: Home', $this->render('{{ seo_breadcrumb() }}', [], $templates, $options));
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, string> $extraTemplates
     * @param array{templates?: array{metas?: string, breadcrumb?: string}, breadcrumb?: BreadcrumbCollection} $options
     */
    private function render(string $template, array $context = [], array $extraTemplates = [], array $options = []): string
    {
        $twig = new Environment(new ArrayLoader([
            'page' => $template,
            SeoExtension::DEFAULT_METAS_TEMPLATE => (string) file_get_contents(__DIR__ . '/../../../../templates/front/seo/block-metas.html.twig'),
        ] + $extraTemplates));

        $twig->addExtension(new SeoExtension(
            $twig,
            new EventDispatcher(),
            $options['breadcrumb'] ?? new BreadcrumbCollection(),
            ['separator' => '|', 'suffix' => 'Shop'],
            [],
            $options['templates'] ?? [],
        ));
        // stands for the media extension of the plugin, out of the scope of these tests
        $twig->addFilter(new TwigFilter('resolve_media', static fn (mixed $media): ?string => null));

        return $twig->render('page', $context);
    }
}
