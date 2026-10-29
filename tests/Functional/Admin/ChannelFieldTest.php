<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\Config\ConfigAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\Menu\MenuAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Admin\HappyCMS\SharedBlock\SharedBlockAdmin;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Config\Config;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\Menu\Menu;
use Tests\Adeliom\SyliusHappyCMSPlugin\Entity\HappyCMS\SharedBlock\SharedBlock;

final class ChannelFieldTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /**
     * @return iterable<string, array{class-string, class-string, object}>
     */
    public static function admins(): iterable
    {
        yield 'menu' => [MenuAdmin::class, Menu::class, new Menu()];
        yield 'shared block' => [SharedBlockAdmin::class, SharedBlock::class, new SharedBlock()];
        yield 'config' => [ConfigAdmin::class, Config::class, new Config()];
    }

    /**
     * @param class-string $admin
     * @param class-string $entityClass
     */
    #[DataProvider('admins')]
    public function testChannelsFieldExistsOnEachAdminForm(string $admin, string $entityClass, object $entity): void
    {
        $form = $this->createForm($admin, $entityClass, $entity);

        self::assertTrue($form->has('channels'));
        $config = $form->get('channels')->getConfig();
        self::assertInstanceOf(ChannelChoiceType::class, $config->getType()->getInnerType());
        self::assertTrue($config->getOption('multiple'));
        self::assertFalse($config->getOption('required'));
    }

    public function testHelpTextIsTranslatedInFrenchAndEnglish(): void
    {
        $translator = self::getContainer()->get('translator');
        self::assertInstanceOf(TranslatorInterface::class, $translator);

        self::assertSame(
            'Laissez vide pour que ce contenu soit disponible sur tous les channels',
            $translator->trans('sylius_happy_cms.admin.channels.help', [], 'messages', 'fr'),
        );
        self::assertSame(
            'Leave empty to make this content available on all channels',
            $translator->trans('sylius_happy_cms.admin.channels.help', [], 'messages', 'en'),
        );
    }

    /**
     * @param class-string $admin
     * @param class-string $entityClass
     */
    #[DataProvider('admins')]
    public function testSubmittingNoChannelKeepsRelationEmpty(string $admin, string $entityClass, object $entity): void
    {
        $form = $this->createForm($admin, $entityClass, $entity);

        $form->get('channels')->submit([]);

        self::assertTrue($form->get('channels')->isSynchronized());
        self::assertCount(0, $entity->getChannels());
        self::assertTrue($entity->isAvailableForAllChannels());
    }

    /**
     * @param class-string $admin
     * @param class-string $entityClass
     */
    private function createForm(string $admin, string $entityClass, object $entity): FormInterface
    {
        $formFactory = self::getContainer()->get('form.factory');
        self::assertInstanceOf(FormFactoryInterface::class, $formFactory);

        return $formFactory->create($admin, $entity, ['data_class' => $entityClass]);
    }
}
