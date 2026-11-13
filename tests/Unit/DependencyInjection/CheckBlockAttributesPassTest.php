<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler\CheckBlockAttributesPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\DependencyInjection\Fixtures\AttributeOnPlainFormType;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\AttributeBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\AttributeWithoutTraitBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\ChildOfAttributeBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\LegacyBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\NoMetadataBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\Block\Fixtures\TitleContentBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\AttributeSharedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\NoMetadataSharedBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\SharedFromContentBlock;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Factory\SharedBlock\Fixtures\SharedWithoutName;

final class CheckBlockAttributesPassTest extends TestCase
{
    public function testBlocksWithAttributesAreAcceptedInAttributeMode(): void
    {
        $container = $this->container(true);
        $this->registerBlock($container, AttributeBlock::class, attribute: true);
        $this->registerBlock($container, ChildOfAttributeBlock::class);
        $this->registerSharedBlock($container, AttributeSharedBlock::class, attribute: true);
        $this->registerBlock($container, LegacyBlock::class);

        (new CheckBlockAttributesPass())->process($container);

        $this->expectNotToPerformAssertions();
    }

    public function testBlocksWithMethodsOnlyAreAcceptedWhateverTheMode(): void
    {
        foreach ([true, false, null] as $enabled) {
            $container = $this->container($enabled);
            $this->registerBlock($container, LegacyBlock::class);

            (new CheckBlockAttributesPass())->process($container);
        }

        $this->expectNotToPerformAssertions();
    }

    public function testAnAttributeIsRejectedWhenTheAttributeModeIsDisabled(): void
    {
        $container = $this->container(false);
        $this->registerBlock($container, AttributeBlock::class, attribute: true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The block "%s" carries #[AsBlock] but "sylius_easy_crud.attributes.enabled" is false.', AttributeBlock::class));

        (new CheckBlockAttributesPass())->process($container);
    }

    public function testAnInheritedAttributeIsRejectedWhenTheAttributeModeIsDisabled(): void
    {
        $container = $this->container(false);
        $this->registerBlock($container, ChildOfAttributeBlock::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The block "%s" carries #[AsBlock]', ChildOfAttributeBlock::class));

        (new CheckBlockAttributesPass())->process($container);
    }

    public function testAnAttributeIsRejectedWithoutTheEasyCrudParameter(): void
    {
        $container = $this->container(null);
        $this->registerSharedBlock($container, AttributeSharedBlock::class, attribute: true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The block "%s" carries #[AsSharedBlock] but "sylius_easy_crud.attributes.enabled" is false.', AttributeSharedBlock::class));

        (new CheckBlockAttributesPass())->process($container);
    }

    public function testAnAttributeOnAClassNotExtendingTheAbstractBlockIsRejected(): void
    {
        $container = $this->container(true);
        $container->register(AttributeOnPlainFormType::class, AttributeOnPlainFormType::class)
            ->addTag(CheckBlockAttributesPass::ATTRIBUTE_TAG);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The class "%s" carries #[AsBlock] but does not extend', AttributeOnPlainFormType::class));

        (new CheckBlockAttributesPass())->process($container);
    }

    public function testAnAttributeOnABlockNotUsingTheTraitIsRejected(): void
    {
        $container = $this->container(true);
        $this->registerBlock($container, AttributeWithoutTraitBlock::class, attribute: true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The block "%s" carries #[AsBlock] but does not use "Adeliom\SyliusHappyCMSPlugin\Factory\Block\BlockAttributeMetadataTrait", which reads the attribute: add "use BlockAttributeMetadataTrait;" to the class or to its base class.', AttributeWithoutTraitBlock::class));

        (new CheckBlockAttributesPass())->process($container);
    }

    public function testABlockWithoutAttributeNorMethodIsRejected(): void
    {
        $container = $this->container(true);
        $this->registerBlock($container, NoMetadataBlock::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The block "%s" has no metadata: set #[AsBlock] on the class, or override getName(), getIcon(), getFrontEndTemplatePath().', NoMetadataBlock::class));

        (new CheckBlockAttributesPass())->process($container);
    }

    public function testASharedBlockWithoutAttributeNorMethodIsRejected(): void
    {
        $container = $this->container(true);
        $this->registerSharedBlock($container, NoMetadataSharedBlock::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The block "%s" has no metadata: set #[AsSharedBlock] on the class', NoMetadataSharedBlock::class));

        (new CheckBlockAttributesPass())->process($container);
    }

    public function testTheContentBlockIsInjectedIntoTheSharedBlockTakingItsMetadata(): void
    {
        $container = $this->container(true);
        $container->register('app.title_block', TitleContentBlock::class)->addTag('sylius.happy_cms.block');
        $this->registerSharedBlock($container, SharedFromContentBlock::class, attribute: true);

        (new CheckBlockAttributesPass())->process($container);

        self::assertEquals(
            [['setSharedBlockSource', [new Reference('app.title_block')]]],
            $container->getDefinition(SharedFromContentBlock::class)->getMethodCalls(),
        );
    }

    public function testASharedBlockTakingAnUnregisteredContentBlockIsRejected(): void
    {
        $container = $this->container(true);
        $this->registerSharedBlock($container, SharedFromContentBlock::class, attribute: true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The shared block "%s" takes its metadata and its form from "%s" (#[AsSharedBlock(block:)]), which is not a registered content block', SharedFromContentBlock::class, TitleContentBlock::class));

        (new CheckBlockAttributesPass())->process($container);
    }

    public function testASharedBlockAttributeWithoutNameNorContentBlockIsRejected(): void
    {
        $container = $this->container(true);
        $this->registerSharedBlock($container, SharedWithoutName::class, attribute: true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The shared block "%s" has no name: set it in #[AsSharedBlock], or take it from a content block', SharedWithoutName::class));

        (new CheckBlockAttributesPass())->process($container);
    }

    private function container(?bool $attributesEnabled): ContainerBuilder
    {
        $container = new ContainerBuilder();
        if (null !== $attributesEnabled) {
            $container->setParameter('sylius_easy_crud.attributes.enabled', $attributesEnabled);
        }

        return $container;
    }

    /**
     * @param class-string $class
     */
    private function registerBlock(ContainerBuilder $container, string $class, bool $attribute = false): void
    {
        $definition = $container->register($class, $class)->addTag('sylius.happy_cms.block');
        if ($attribute) {
            $definition->addTag(CheckBlockAttributesPass::ATTRIBUTE_TAG);
        }
    }

    /**
     * @param class-string $class
     */
    private function registerSharedBlock(ContainerBuilder $container, string $class, bool $attribute = false): void
    {
        $definition = $container->register($class, $class)->addTag('sylius.happy_cms.shared_block');
        if ($attribute) {
            $definition->addTag(CheckBlockAttributesPass::ATTRIBUTE_TAG);
        }
    }
}
