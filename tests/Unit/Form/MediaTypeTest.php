<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Form;

use Adeliom\SyliusHappyCMSPlugin\Form\MediaType;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaHelper;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Routing\RouterInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\Article;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\MediaReferenceTestCase;
use Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Doctrine\Fixtures\ReferencedMedia;

final class MediaTypeTest extends MediaReferenceTestCase
{
    public function testTheSubmittedIdIsTheModelValueByDefault(): void
    {
        $media = $this->saveMedia('Front door');
        $article = new Article();

        $form = $this->formFactory()->create(FormType::class, $article)->add('legacyImage', MediaType::class);
        $form->submit(['legacyImage' => (string) $media->getId()]);

        self::assertTrue($form->isValid());
        self::assertSame($media->getId(), $article->legacyImage);
    }

    public function testTheHydrateOptionGivesTheMedia(): void
    {
        $media = $this->saveMedia('Front door');
        $article = new Article();

        $form = $this->formFactory()->create(FormType::class, $article)->add('image', MediaType::class, ['hydrate' => true]);
        $form->submit(['image' => (string) $media->getId()]);

        self::assertTrue($form->isValid());
        self::assertSame($media, $article->image);
    }

    public function testResubmittingTheSameMediaKeepsTheLoadedReference(): void
    {
        $article = new Article();
        $article->image = $this->saveMedia('Front door');
        $article = $this->saveAndReload($article);
        $reference = $article->image;

        $form = $this->formFactory()->create(FormType::class, $article)->add('image', MediaType::class, ['hydrate' => true]);
        $form->submit(['image' => (string) $reference?->getId()]);

        self::assertSame($reference, $article->image, 'No change for Doctrine, no UPDATE.');
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();
        self::assertSame([], $unitOfWork->getEntityChangeSet($article));
    }

    public function testAReferenceIsShownAsItsId(): void
    {
        $article = new Article();
        $article->image = $this->saveMedia('Front door');
        $article = $this->saveAndReload($article);

        $form = $this->formFactory()->create(FormType::class, $article)->add('image', MediaType::class, ['hydrate' => true]);

        self::assertSame((string) $article->image?->getId(), $form->get('image')->getViewData());
    }

    public function testAReferenceToADeletedMediaIsShownEmpty(): void
    {
        $this->entityManager->getConnection()->insert('article', ['image' => 999]);
        $article = $this->entityManager->find(Article::class, (int) $this->entityManager->getConnection()->lastInsertId());

        $form = $this->formFactory()->create(FormType::class, $article)->add('image', MediaType::class, ['hydrate' => true]);

        self::assertSame('', $form->get('image')->getViewData());
    }

    public function testAnUnknownIdIsRefused(): void
    {
        $article = new Article();

        $form = $this->formFactory()->create(FormType::class, $article)->add('image', MediaType::class, ['hydrate' => true]);
        $form->submit(['image' => '999']);

        self::assertFalse($form->isValid());
        self::assertNull($article->image);
    }

    private function formFactory(): FormFactoryInterface
    {
        $container = new Container();
        $container->setParameter('sylius_happy_cms.media.media_entity', ReferencedMedia::class);
        $helper = new MediaHelper(new ContainerBag($container), $this->entityManager, $this->createStub(RouterInterface::class));

        $manager = $this->createStub(MediaManager::class);
        $manager->method('getHelper')->willReturn($helper);
        $manager->method('getMedia')->willReturnCallback($helper->getMedia(...));

        $type = new MediaType();
        $type->setManager($manager);

        return Forms::createFormFactoryBuilder()->addType($type)->getFormFactory();
    }
}
