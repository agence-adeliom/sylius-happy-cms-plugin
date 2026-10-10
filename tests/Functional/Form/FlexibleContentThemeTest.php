<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Form;

use Adeliom\SyliusHappyCMSPlugin\Block\CtaBlockType;
use Adeliom\SyliusHappyCMSPlugin\Block\WysiwygBlockType;
use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockCollection;
use Adeliom\SyliusHappyCMSPlugin\Form\Block\BlockCollectionType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;

/**
 * Renders the flexible content field with its admin form theme.
 */
final class FlexibleContentThemeTest extends KernelTestCase
{
    public function testMinimalPrototypesAskToSaveBeforeFillingTheBlock(): void
    {
        $html = $this->render([], [WysiwygBlockType::class]);

        $prototype = $this->prototype($html, WysiwygBlockType::class);
        self::assertStringContainsString('[block_type]', $prototype);
        self::assertStringNotContainsString('[__block_wysiwyg_block__][content]', $prototype);
        self::assertStringContainsString('alert-secondary', $prototype);
    }

    public function testCompletePrototypesHoldTheFieldsOfTheBlockAndNoSaveNotice(): void
    {
        $html = $this->render([], [WysiwygBlockType::class], ['complete_prototypes' => true]);

        $prototype = $this->prototype($html, WysiwygBlockType::class);
        self::assertStringContainsString('[block_type]', $prototype);
        self::assertStringContainsString('[__block_wysiwyg_block__][content]', $prototype);
        self::assertStringNotContainsString('alert-secondary', $prototype);
    }

    public function testAStoredBlockOutsideTheAllowedListIsStillRendered(): void
    {
        $html = $this->render(
            [['block_type' => CtaBlockType::class, 'block_published' => '1', 'position' => 0, 'title' => 'Kept']],
            [WysiwygBlockType::class],
        );

        self::assertStringContainsString('value="Kept"', $html);
    }

    /**
     * @param list<array<string, mixed>> $content
     * @param list<class-string> $allowed
     * @param array<string, mixed> $options
     */
    private function render(array $content, array $allowed, array $options = []): string
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var BlockCollection $blocks */
        $blocks = $container->get('sylius.happy_cms.block_collection');
        /** @var FormFactoryInterface $factory */
        $factory = $container->get('form.factory');
        /** @var Environment $twig */
        $twig = $container->get('twig');

        $form = $factory->createNamed('content', BlockCollectionType::class, $content, array_merge([
            'allow_add' => true,
            'allow_delete' => true,
            'allow_drag' => true,
            'csrf_protection' => false,
            'blocks' => $blocks->getAllowedBlocks($allowed, null),
        ], $options));

        return $twig->createTemplate(
            "{% form_theme form '@SyliusHappyCMSPlugin/field/flexible_content/form.html.twig' %}{{ form_widget(form) }}",
        )->render(['form' => $form->createView()]);
    }

    private function prototype(string $html, string $blockType): string
    {
        $pattern = sprintf('/data-block-type="%s"[^>]*data-prototype=\'([^\']*)\'/s', preg_quote(htmlspecialchars($blockType), '/'));
        self::assertMatchesRegularExpression($pattern, $html);
        preg_match($pattern, $html, $matches);

        return html_entity_decode($matches[1]);
    }
}
