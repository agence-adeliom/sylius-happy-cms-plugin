<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

final class InstallDefaultFiles extends AbstractMaker
{
    public const TPL_FILES = [
        'entity' => __DIR__ . '/../../Resources/skeleton/default/entity.tpl.php',
        'translation' => __DIR__ . '/../../Resources/skeleton/cms/translation.tpl.php',
        'repository' => __DIR__ . '/../../Resources/skeleton/cms/repository.tpl.php',
        'admin' => __DIR__ . '/../../Resources/skeleton/cms/admin.tpl.php',
    ];

    public static function getCommandName(): string
    {
        return 'make:happy-cms:install';
    }

    public static function getCommandDescription(): string
    {
        return 'Install default Entities, Translations, Repositories and Admin classes';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
        ;
    }

    /**
     * @throws \Exception
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $happyCMSDefaultPackageParameters = [
            'parameters:',
        ];
        $this->generatePage($happyCMSDefaultPackageParameters, $io, $generator);
        $this->generateConfig($happyCMSDefaultPackageParameters, $io, $generator);
        $this->generateFolder($happyCMSDefaultPackageParameters, $io, $generator);
        $this->generateMedia($happyCMSDefaultPackageParameters, $io, $generator);
        $this->generateMenu($happyCMSDefaultPackageParameters, $io, $generator);
        $this->generateBlock($happyCMSDefaultPackageParameters, $io, $generator);

        $io->newLine();
        $io->success('Success!');
        $io->newLine();
        $choice = $io->confirm('Do you want to get to configuration lines to add into config/parameters.yaml file ?');

        // Add
        if ($choice) {
            $io->writeln($happyCMSDefaultPackageParameters);
        }
    }

    /**
     * @param string[] $happyCMSDefaultPackageParameters
     */
    private function generatePage(array &$happyCMSDefaultPackageParameters, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'page';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'addRepo' => true, 'addTrans' => true],
            ['prefix' => 'Entity', 'suffix' => 'Translation'],
            ['prefix' => 'Repository', 'suffix' => 'Repository'],
            ['prefix' => 'Admin', 'suffix' => 'Admin'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $happyCMSDefaultPackageParameters = array_merge($happyCMSDefaultPackageParameters, [
            '   sylius_happy_cms.page.model: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope),
            '   sylius_happy_cms.page.model_translation: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Translation',
            '   sylius_happy_cms.page.repository: App\Repository\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Repository',
            '   sylius_happy_cms.page.page_admin: App\Admin\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Admin',
        ]);
    }

    /**
     * @param string[] $happyCMSDefaultPackageParameters
     */
    private function generateConfig(array &$happyCMSDefaultPackageParameters, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'config';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'addRepo' => true, 'addTrans' => true],
            ['prefix' => 'Entity', 'suffix' => 'Translation'],
            ['prefix' => 'Repository', 'suffix' => 'Repository'],
            ['prefix' => 'Admin', 'suffix' => 'Admin'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $happyCMSDefaultPackageParameters = array_merge($happyCMSDefaultPackageParameters, [
            '',
            '   sylius_happy_cms.config.model: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope),
            '   sylius_happy_cms.config.model_translation: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Translation',
            '   sylius_happy_cms.config.repository: App\Repository\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Repository',
            '   sylius_happy_cms.config.config_admin: App\Admin\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Admin',
        ]);
    }

    /**
     * @param string[] $happyCMSDefaultPackageParameters
     */
    private function generateFolder(array &$happyCMSDefaultPackageParameters, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'media';
        $entityName = 'folder';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'entityName' => $entityName],
            ['prefix' => 'Repository', 'suffix' => 'Repository', 'entityName' => $entityName],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $happyCMSDefaultPackageParameters = array_merge($happyCMSDefaultPackageParameters, [
            '',
            '   sylius_happy_cms.folder.model: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($entityName),
            '   sylius_happy_cms.folder.repository: App\Repository\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($entityName) . 'Repository',
        ]);
    }

    /**
     * @param string[] $happyCMSDefaultPackageParameters
     */
    private function generateMedia(array &$happyCMSDefaultPackageParameters, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'media';
        $files = [
            ['prefix' => 'Entity', 'suffix' => ''],
            ['prefix' => 'Repository', 'suffix' => 'Repository'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $happyCMSDefaultPackageParameters = array_merge($happyCMSDefaultPackageParameters, [
            '   sylius_happy_cms.media.model: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope),
            '   sylius_happy_cms.media.repository: App\Repository\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Repository',
        ]);
    }

    /**
     * @param string[] $happyCMSDefaultPackageParameters
     */
    private function generateMenu(array &$happyCMSDefaultPackageParameters, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'menu';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'entityName' => 'menu', 'addRepo' => true],
            ['prefix' => 'Repository', 'suffix' => 'Repository', 'entityName' => 'menu'],
            ['prefix' => 'Admin', 'suffix' => 'Admin', 'entityName' => 'menu'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $happyCMSDefaultPackageParameters = array_merge($happyCMSDefaultPackageParameters, [
            '',
            '   sylius_happy_cms.menu.model: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope),
            '   sylius_happy_cms.menu.repository: App\Repository\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Repository',
            '   sylius_happy_cms.menu.menu_admin: App\Admin\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Admin',
        ]);

        $entityName = 'menuItem';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'entityName' => $entityName, 'addRepo' => true, 'addTrans' => true],
            ['prefix' => 'Entity', 'suffix' => 'Translation', 'entityName' => $entityName],
            ['prefix' => 'Repository', 'suffix' => 'Repository', 'entityName' => $entityName],
            ['prefix' => 'Admin', 'suffix' => 'Admin', 'entityName' => $entityName],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $happyCMSDefaultPackageParameters = array_merge($happyCMSDefaultPackageParameters, [
            '',
            '   sylius_happy_cms.menu_item.model: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($entityName),
            '   sylius_happy_cms.menu_item.model_translation: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($entityName) . 'Translation',
            '   sylius_happy_cms.menu_item.repository: App\Repository\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($entityName) . 'Repository',
            '   sylius_happy_cms.menu_item.menu_admin: App\Admin\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($entityName) . 'Admin',
        ]);
    }

    /**
     * @param string[] $happyCMSDefaultPackageParameters
     */
    private function generateBlock(array &$happyCMSDefaultPackageParameters, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'sharedBlock';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'entityName' => 'sharedBlock', 'addRepo' => true, 'addTrans' => true],
            ['prefix' => 'Entity', 'suffix' => 'Translation', 'entityName' => 'sharedBlock'],
            ['prefix' => 'Repository', 'suffix' => 'Repository', 'entityName' => 'sharedBlock'],
            ['prefix' => 'Admin', 'suffix' => 'Admin', 'entityName' => 'sharedBlock'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $happyCMSDefaultPackageParameters = array_merge($happyCMSDefaultPackageParameters, [
            '',
            '   sylius_happy_cms.shared_block.model: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope),
            '   sylius_happy_cms.shared_block.model_translation: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Translation',
            '   sylius_happy_cms.shared_block.repository: App\Repository\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Repository',
            '   sylius_happy_cms.shared_block.menu_admin: App\Admin\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Admin',
        ]);
    }

    /**
     * @param array<int, array<string, string>> $files
     */
    private function generateScope(string $scope, array $files, ConsoleStyle $io, Generator $generator): void
    {
        foreach ($files as $data) {
            $namespacePrefix = $data['prefix'] . '\HappyCMS\\' . ucfirst($scope);
            $classNameDetail = $generator->createClassNameDetails(
                ucfirst($data['entityName'] ?? $scope),
                $namespacePrefix,
                $data['suffix'],
            );

            try {
                if (class_exists($classNameDetail->getFullName())) {
                    $io->comment(sprintf(
                        '%s: %s',
                        '<fg=yellow>warning</>',
                        $classNameDetail->getFullName() . ' already exists',
                    ));
                } else {
                    $generator->generateClass(
                        $classNameDetail->getFullName(),
                        __DIR__ . '/../Resources/skeleton/default/' . strtolower($data['prefix']) . '.tpl.php',
                        [
                            'classNameDetail' => $classNameDetail,
                            'scope' => ucfirst($scope),
                            'addRepo' => $data['addRepo'] ?? false,
                            'addTrans' => $data['addTrans'] ?? false,
                        ],
                    );
                    $generator->writeChanges();
                }
            } catch (\Exception $exception) {
                $io->error($exception->getMessage());
            }
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // No dependencies needed
    }

    /**
     * @param string[] $happyCMSDefaultPackageParameters
     */
    private function getHappyCMSDefaultPackageParameters(array &$happyCMSDefaultPackageParameters, string $scope): void
    {
        $happyCMSDefaultPackageParameters = array_merge($happyCMSDefaultPackageParameters, [
            '   ' . $scope . ':',
            '       page_class: App\Entity\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope),
            '       page_repository: App\Repository\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Repository',
            '       page_admin: App\Admin\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst($scope) . 'Admin',
        ]);
    }
}
