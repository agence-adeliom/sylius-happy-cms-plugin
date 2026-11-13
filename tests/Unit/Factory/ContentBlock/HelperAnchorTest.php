<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\ContentBlock;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\Services\AssetRenderer;
use Adeliom\SyliusHappyCMSPlugin\Event\Block\BlockRender;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock\Helper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Page\PageContentBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\TitleBlock;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * A content block is rendered with the anchor `content-block-<id>`, unless a `happy_cms_block.render_block` listener
 * gave it its own anchor (`attr_id`), as the JSON block renderer already allows.
 */
final class HelperAnchorTest extends TestCase
{
    public function testTheAnchorIsTheContentBlockIdByDefault(): void
    {
        $html = (string) $this->helper(new EventDispatcher())->renderContentBlock($this->contentBlock());

        self::assertSame('<h2 id="content-block-7">Hello</h2>', $html);
    }

    public function testAnAnchorSetByARenderListenerIsKept(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('happy_cms_block.render_block', static function (BlockRender $event): void {
            $event->setData([...$event->getData(), 'attr_id' => 'financement']);
        });

        $html = (string) $this->helper($dispatcher)->renderContentBlock($this->contentBlock());

        self::assertSame('<h2 id="financement">Hello</h2>', $html);
    }

    private function contentBlock(): PageContentBlock
    {
        $contentBlock = new PageContentBlock();
        (new \ReflectionProperty($contentBlock, 'id'))->setValue($contentBlock, 7);
        $contentBlock->setType(TitleBlock::class);
        $contentBlock->setLocale('fr_FR');
        $contentBlock->setPublishState(ThreeStateStatusEnum::PUBLISHED);
        $contentBlock->setPublishedData(['title' => 'Hello']);

        return $contentBlock;
    }

    private function helper(EventDispatcher $dispatcher): Helper
    {
        $twig = new Environment(new ArrayLoader([
            'blocks/title.html.twig' => '<h2 id="{{ settings.attr_id }}">{{ settings.title }}</h2>',
        ]), ['strict_variables' => true, 'autoescape' => false]);

        return new Helper(
            $twig,
            $dispatcher,
            new BlockCollection([new TitleBlock()]),
            new AssetRenderer(new EventDispatcher(), $this->createStub(TagRenderer::class), new Packages()),
        );
    }
}
