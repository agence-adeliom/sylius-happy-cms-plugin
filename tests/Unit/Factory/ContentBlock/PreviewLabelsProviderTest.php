<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\ContentBlock;

use Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockTypeInterface;
use Adeliom\SyliusHappyCMSPlugin\Factory\ContentBlock\PreviewLabelsProvider;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewContext;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewRequestValidatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

final class PreviewLabelsProviderTest extends TestCase
{
    public function testItTranslatesInTheAdminLocaleOfTheSignedPreview(): void
    {
        $provider = $this->provider('en_US', self::context('fr_FR'));

        self::assertSame('Éditer le bloc', $provider->labels()->editBlock);
        self::assertSame('Bloc non publié', $provider->labels()->unpublishedBlock);
        self::assertSame('Bloc supprimé', $provider->labels()->deletedBlock);
        self::assertSame("Aucun rendu visible dans l'aperçu", $provider->labels()->emptyBlock);
    }

    public function testItFallsBackOnTheRequestLocale(): void
    {
        $provider = $this->provider('en_US', null);

        self::assertSame('Edit Block', $provider->labels()->editBlock);
        self::assertSame('No visible output in the preview', $provider->labels()->emptyBlock);
    }

    public function testItFallsBackOnTheRequestLocaleWhenTheSignedUrlHasNoAdminLocale(): void
    {
        $provider = $this->provider('fr_FR', self::context(null));

        self::assertSame('Éditer le bloc', $provider->labels()->editBlock);
    }

    public function testItKeepsTheEnglishLabelsWithoutRequest(): void
    {
        $translator = self::translator();
        $validator = $this->createStub(SignedPreviewRequestValidatorInterface::class);
        $provider = new PreviewLabelsProvider($translator, new RequestStack(), $validator);

        self::assertSame('Edit Block', $provider->labels()->editBlock);
    }

    public function testItTranslatesTheBlockName(): void
    {
        $provider = $this->provider('en_US', self::context('fr_FR'));

        self::assertSame('Bloc héros', $provider->blockName($this->block('app.block.hero')));
        self::assertSame('Shared block', $provider->blockName($this->block('Shared block')));
    }

    public function testItResolvesTheLocaleOncePerRequest(): void
    {
        $validator = $this->createMock(SignedPreviewRequestValidatorInterface::class);
        $validator->expects(self::once())->method('validate')->willReturn(self::context('fr_FR'));

        $provider = new PreviewLabelsProvider(self::translator(), self::requestStack('en_US'), $validator);

        $provider->labels();
        $provider->labels();
        $provider->blockName($this->block('app.block.hero'));
    }

    private function provider(string $requestLocale, ?SignedPreviewContext $context): PreviewLabelsProvider
    {
        $validator = $this->createStub(SignedPreviewRequestValidatorInterface::class);
        $validator->method('validate')->willReturn($context);

        return new PreviewLabelsProvider(self::translator(), self::requestStack($requestLocale), $validator);
    }

    private static function context(?string $adminLocale): SignedPreviewContext
    {
        return new SignedPreviewContext('sylius_happy_cms.page', 42, 'en_US', 'http://admin.localhost', $adminLocale);
    }

    private static function requestStack(string $locale): RequestStack
    {
        $request = Request::create('http://localhost/ma-page');
        $request->setLocale($locale);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    private static function translator(): Translator
    {
        $translator = new Translator('en_US');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'sylius_happy_cms.page_builder.edit_block' => 'Edit Block',
            'sylius_happy_cms.page_builder.preview_overlay.unpublished_block' => 'Unpublished block',
            'sylius_happy_cms.page_builder.preview_overlay.deleted_block' => 'Deleted block',
            'sylius_happy_cms.page_builder.preview_overlay.empty_block' => 'No visible output in the preview',
            'app.block.hero' => 'Hero block',
        ], 'en_US');
        $translator->addResource('array', [
            'sylius_happy_cms.page_builder.edit_block' => 'Éditer le bloc',
            'sylius_happy_cms.page_builder.preview_overlay.unpublished_block' => 'Bloc non publié',
            'sylius_happy_cms.page_builder.preview_overlay.deleted_block' => 'Bloc supprimé',
            'sylius_happy_cms.page_builder.preview_overlay.empty_block' => "Aucun rendu visible dans l'aperçu",
            'app.block.hero' => 'Bloc héros',
        ], 'fr_FR');

        return $translator;
    }

    private function block(string $name): BlockTypeInterface
    {
        $block = $this->createStub(BlockTypeInterface::class);
        $block->method('getName')->willReturn($name);

        return $block;
    }
}
