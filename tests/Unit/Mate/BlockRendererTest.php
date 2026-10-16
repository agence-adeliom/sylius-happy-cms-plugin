<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Mate;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\Helper;
use Adeliom\SyliusHappyCMSPlugin\Mate\BlockRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Markup;

final class BlockRendererTest extends TestCase
{
    private const PUBLISHED = ['block_type' => 'App\Block', 'block_published' => true, 'title' => 'Hello'];

    #[Test]
    public function it_reports_the_length_of_a_rendered_block(): void
    {
        $result = $this->renderer($this->helperReturning(new Markup(' <p>Hello</p> ', 'UTF-8')))->render(self::PUBLISHED, $this->block('ok.html.twig'));

        self::assertSame(['status' => 'rendered', 'html_length' => 12], $result);
    }

    #[Test]
    public function it_reports_a_block_rendering_nothing(): void
    {
        $result = $this->renderer($this->helperReturning(new Markup('  ', 'UTF-8')))->render(self::PUBLISHED, $this->block('ok.html.twig'));

        self::assertSame('empty', $result['status']);
    }

    #[Test]
    public function it_reports_an_unknown_type_and_an_unpublished_block_skipped_by_the_helper(): void
    {
        $renderer = $this->renderer($this->helperReturning(null));

        self::assertSame(['status' => 'unknown_type'], $renderer->render(self::PUBLISHED, null));
        self::assertSame(['status' => 'unpublished'], $renderer->render(['block_published' => false] + self::PUBLISHED, $this->block('ok.html.twig')));
    }

    #[Test]
    public function it_surfaces_the_exception_a_decorating_helper_swallowed(): void
    {
        $helper = $this->helperReturning(null);

        $result = $this->renderer($helper)->render(self::PUBLISHED, $this->block('broken.html.twig'));

        self::assertSame('error', $result['status']);
        self::assertSame($helper::class, $result['swallowed_by']);
        self::assertIsString($result['message']);
        self::assertStringContainsString('missing', $result['message']);
        self::assertStringContainsString('broken.html.twig', $result['message']);
    }

    #[Test]
    public function it_reports_a_block_skipped_although_its_template_renders(): void
    {
        $result = $this->renderer($this->helperReturning(null))->render(self::PUBLISHED, $this->block('ok.html.twig'));

        self::assertSame('skipped', $result['status']);
    }

    #[Test]
    public function it_reports_the_exception_thrown_by_the_helper(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('renderBlock')->willThrowException(new \LogicException('Boom'));

        $result = $this->renderer($helper)->render(self::PUBLISHED, $this->block('ok.html.twig'));

        self::assertSame('error', $result['status']);
        self::assertSame(\LogicException::class, $result['exception']);
        self::assertSame('Boom', $result['message']);
    }

    #[Test]
    public function it_stands_a_request_in_for_the_rendering_and_removes_it_when_closed(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('/page/');

        $renderer = new BlockRenderer($this->helperReturning(null), $this->twig(), $requestStack, $request);
        self::assertSame($request, $requestStack->getCurrentRequest());

        $renderer->close();
        self::assertNull($requestStack->getCurrentRequest());
    }

    #[Test]
    public function it_keeps_the_current_request(): void
    {
        $current = Request::create('/current/');
        $requestStack = new RequestStack([$current]);

        $renderer = new BlockRenderer($this->helperReturning(null), $this->twig(), $requestStack, Request::create('/page/'));
        $renderer->close();

        self::assertSame($current, $requestStack->getCurrentRequest());
    }

    private function renderer(Helper $helper): BlockRenderer
    {
        return new BlockRenderer($helper, $this->twig(), null, Request::create('/'));
    }

    private function helperReturning(?Markup $html): Helper
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('renderBlock')->willReturn($html);

        return $helper;
    }

    private function block(string $template): BlockTypeInterface
    {
        $block = $this->createMock(BlockTypeInterface::class);
        $block->method('getFrontEndTemplatePath')->willReturn($template);

        return $block;
    }

    private function twig(): Environment
    {
        return new Environment(new ArrayLoader([
            'ok.html.twig' => '<p>{{ settings.title }}</p>',
            'broken.html.twig' => '{{ settings.missing.deep }}',
        ]), ['strict_variables' => true]);
    }
}
