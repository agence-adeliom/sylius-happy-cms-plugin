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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use function Symfony\Component\String\u;
use Symfony\Component\Yaml\Yaml;

final class InstallDefaultFiles extends AbstractMaker
{
    public const YAML_ROUTES_FILE = 'config/routes.yaml';

    public const YAML_RESOURCE_FILE = 'config/packages/sylius_resource.yaml';

    public const YAML_HAPPY_CMS_FILE = 'config/packages/sylius_happy_cms.yaml';

    public const YAML_SERVICES_FILE = 'config/services.yaml';

    public function __construct(
        protected ParameterBagInterface $parameterBag,
    ) {
    }

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
        $happyCMSDefaultRoutes = '';
        $happyCMSDefaultResources = [];
        $io->text('====');
        $this->generatePage($happyCMSDefaultRoutes, $happyCMSDefaultResources, $io, $generator);

        $io->newLine();
        $io->text('====');
        $this->generateConfig($happyCMSDefaultRoutes, $happyCMSDefaultResources, $io, $generator);

        $io->newLine();
        $io->text('====');
        $this->generateFolder($happyCMSDefaultRoutes, $happyCMSDefaultResources, $io, $generator);

        $io->newLine();
        $io->text('====');
        $this->generateMedia($happyCMSDefaultRoutes, $happyCMSDefaultResources, $io, $generator);

        $io->newLine();
        $io->text('====');
        $this->generateMenu($happyCMSDefaultRoutes, $happyCMSDefaultResources, $io, $generator);

        $io->newLine();
        $io->text('====');
        $this->generateBlock($happyCMSDefaultRoutes, $happyCMSDefaultResources, $io, $generator);

        $io->newLine();
        $io->text('====');
        $this->addServicesResource('services', $io);

        $io->newLine();
        $io->success('Success!');
        $io->newLine();

        //$choice = $io->confirm('Do you want to get to configuration lines to add into config/parameters.yaml file ?');

        // Add
        //if ($choice) {
        //$io->writeln($happyCMSDefaultPackageParameters);
        //}
    }

    private function generatePage(string &$happyCMSDefaultRoutes, array &$happyCMSDefaultResources, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'page';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'addRepo' => true, 'addTrans' => true],
            ['prefix' => 'Entity', 'suffix' => 'Translation'],
            ['prefix' => 'Repository', 'suffix' => 'Repository'],
            ['prefix' => 'Admin', 'suffix' => 'Admin'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $this->generateRoute($scope, $io);

        $this->generateSyliusResource($scope, $io);

        $this->generateHappyCMSConfig($scope, $io);
    }

    private function generateConfig(string &$happyCMSDefaultRoutes, array &$happyCMSDefaultResources, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'config';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'addRepo' => true, 'addTrans' => true],
            ['prefix' => 'Entity', 'suffix' => 'Translation'],
            ['prefix' => 'Repository', 'suffix' => 'Repository'],
            ['prefix' => 'Admin', 'suffix' => 'Admin'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $this->generateRoute($scope, $io);

        $this->generateSyliusResource($scope, $io);

        $this->generateHappyCMSConfig($scope, $io);
    }

    private function generateFolder(string &$happyCMSDefaultRoutes, array &$happyCMSDefaultResources, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'media';
        $entityName = 'folder';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'entityName' => $entityName],
            ['prefix' => 'Repository', 'suffix' => 'Repository', 'entityName' => $entityName],
        ];
        $this->generateScope($scope, $files, $io, $generator);
    }

    private function generateMedia(string &$happyCMSDefaultRoutes, array &$happyCMSDefaultResources, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'media';
        $files = [
            ['prefix' => 'Entity', 'suffix' => ''],
            ['prefix' => 'Repository', 'suffix' => 'Repository'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $this->generateSyliusResource($scope, $io);

        $this->generateHappyCMSConfig($scope, $io);
    }

    private function generateMenu(string &$happyCMSDefaultRoutes, array &$happyCMSDefaultResources, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'menu';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'entityName' => 'menu', 'addRepo' => true],
            ['prefix' => 'Repository', 'suffix' => 'Repository', 'entityName' => 'menu'],
            ['prefix' => 'Admin', 'suffix' => 'Admin', 'entityName' => 'menu'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $this->generateRoute($scope, $io);

        $this->generateSyliusResource($scope, $io);

        $this->generateHappyCMSConfig($scope, $io);

        $entityName = 'menuItem';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'entityName' => $entityName, 'addRepo' => true, 'addTrans' => true],
            ['prefix' => 'Entity', 'suffix' => 'Translation', 'entityName' => $entityName],
            ['prefix' => 'Repository', 'suffix' => 'Repository', 'entityName' => $entityName],
            ['prefix' => 'Admin', 'suffix' => 'Admin', 'entityName' => $entityName],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $this->generateRoute($scope . '_item', $io);
    }

    private function generateBlock(string &$happyCMSDefaultRoutes, array &$happyCMSDefaultResources, ConsoleStyle $io, Generator $generator): void
    {
        $scope = 'sharedBlock';
        $files = [
            ['prefix' => 'Entity', 'suffix' => '', 'entityName' => 'sharedBlock', 'addRepo' => true, 'addTrans' => true],
            ['prefix' => 'Entity', 'suffix' => 'Translation', 'entityName' => 'sharedBlock'],
            ['prefix' => 'Repository', 'suffix' => 'Repository', 'entityName' => 'sharedBlock'],
            ['prefix' => 'Admin', 'suffix' => 'Admin', 'entityName' => 'sharedBlock'],
        ];
        $this->generateScope($scope, $files, $io, $generator);

        $this->generateRoute('shared_block', $io);

        $this->generateSyliusResource('shared_block', $io);

        $this->generateHappyCMSConfig('shared_block', $io);
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

    public function generateRoute(string $scope, ConsoleStyle $io): string
    {
        try {
            $yaml = [
                'sylius_happy_cms_' . $scope . '_admin' => [
                    'resource' => 'alias: sylius_happy_cms.' . $scope . "\n"
                        . "section: admin\n"
                        . "templates: \"@SyliusEasyCrudPlugin\\\\crud\"\n"
                        . "redirect: update\n"
                        . 'grid: sylius_happy_cms_' . $scope . "_admin\n"
                        . "form:\n"
                        . '    type: App\\Admin\\HappyCMS\\' . ucfirst($scope) . '\\' . ucfirst(u($scope)->camel()
                                                                                                                                      ->toString()) . "Admin\n"
                        . "    options:\n"
                        . "        context: \$context\n"
                        . "vars:\n"
                        . "    all:\n"
                        . "        icon: 'file'\n"
                        . '        subheader: sylius_happy_cms.' . $scope . ".admin.ui.subheader\n"
                        . '        breadcrumb: sylius_happy_cms.' . $scope . ".admin.ui.index\n"
                        . "        templates:\n"
                        . "            form: \"@SyliusEasyCrudPlugin\\\\crud\\\\form\\\\_form.html.twig\"\n"
                        . "    index:\n"
                        . '        header: sylius_happy_cms.' . $scope . ".admin.ui.index\n"
                        . "    create:\n"
                        . '        header: sylius_happy_cms.' . $scope . ".admin.ui.create\n"
                        . "    update:\n"
                        . '        header: sylius_happy_cms.' . $scope . ".admin.ui.update\n"
                        . "        redirect:\n"
                        . "            route: update\n"
                        . "            parameters:\n"
                        . "                context: \$context\n"
                        . "                id: \$id\n"
                        . "        route:\n"
                        . "            parameters:\n"
                        . "                context: \$context\n"
                        . "                id: \$id\n",
                    'type' => 'sylius.resource',
                    'prefix' => 'admin',
                ],
            ];

            if (file_exists(self::YAML_ROUTES_FILE)) {
                $route = 'sylius_happy_cms_' . $scope . '_admin';
                $existingContent = file_get_contents(self::YAML_ROUTES_FILE);
                if (str_contains($existingContent, $route)) {
                    $io->comment(sprintf(
                        '%s: %s',
                        '<fg=yellow>warning</>',
                        self::YAML_ROUTES_FILE . ' already modified (' . $scope . ')',
                    ));

                    return self::YAML_ROUTES_FILE;
                }
            }

            file_put_contents(
                self::YAML_ROUTES_FILE,
                "\n" . Yaml::dump($yaml, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK),
                \FILE_APPEND,
            );

            $io->comment(sprintf(
                '%s: %s',
                '<fg=green>updated</>',
                self::YAML_ROUTES_FILE,
            ));

            return self::YAML_ROUTES_FILE;
        } catch (\Exception $e) {
            $io->error($e->getCode() . ' : ' . $e->getMessage());

            return $e->getCode() . ' : ' . $e->getMessage();
        }
    }

    public function generateSyliusResource(string $scope, ConsoleStyle $io): string
    {
        try {
            $content = file_get_contents(__DIR__ . '/_tpl/resources_' . $scope . '.yaml');

            if (file_exists(self::YAML_RESOURCE_FILE)) {
                $existingContent = file_get_contents(self::YAML_RESOURCE_FILE);
                if (str_contains($existingContent, 'sylius_happy_cms.' . $scope)) {
                    $io->comment(sprintf(
                        '%s: %s',
                        '<fg=yellow>warning</>',
                        self::YAML_RESOURCE_FILE . ' already modified (' . $scope . ')',
                    ));

                    return self::YAML_RESOURCE_FILE;
                }
            }

            file_put_contents(
                self::YAML_RESOURCE_FILE,
                "\n" . str_replace("\n", "\n    ", $content),
                \FILE_APPEND,
            );

            $io->comment(sprintf(
                '%s: %s',
                '<fg=green>updated</>',
                self::YAML_RESOURCE_FILE,
            ));

            return self::YAML_RESOURCE_FILE;
        } catch (\Exception $e) {
            $io->error($e->getCode() . ' : ' . $e->getMessage());

            return $e->getCode() . ' : ' . $e->getMessage();
        }
    }

    public function generateHappyCMSConfig(string $scope, ConsoleStyle $io): string
    {
        try {
            $content = file_get_contents(__DIR__ . '/_tpl/happy_cms_' . $scope . '.yaml');

            if (!file_exists(self::YAML_HAPPY_CMS_FILE)) {
                $content = 'sylius_happy_cms:' . $content;
            } else {
                $existingContent = file_get_contents(self::YAML_HAPPY_CMS_FILE);
                if (!str_contains($existingContent, 'sylius_happy_cms:')) {
                    $content = 'sylius_happy_cms:' . $content;
                }
                if (str_contains($existingContent, $scope . ':')) {
                    $io->comment(sprintf(
                        '%s: %s',
                        '<fg=yellow>warning</>',
                        self::YAML_HAPPY_CMS_FILE . ' already modified (' . $scope . ')',
                    ));

                    return self::YAML_HAPPY_CMS_FILE;
                }
            }

            file_put_contents(
                self::YAML_HAPPY_CMS_FILE,
                "\n" . str_replace("\n", "\n  ", $content),
                \FILE_APPEND,
            );

            $io->comment(sprintf(
                '%s: %s',
                '<fg=green>updated</>',
                self::YAML_HAPPY_CMS_FILE,
            ));

            return self::YAML_HAPPY_CMS_FILE;
        } catch (\Exception $e) {
            $io->error($e->getCode() . ' : ' . $e->getMessage());

            return $e->getCode() . ' : ' . $e->getMessage();
        }
    }

    public function addServicesResource(string $scope, ConsoleStyle $io): string
    {
        try {
            $content = file_get_contents(__DIR__ . '/_tpl/services.yaml');

            if (file_exists(self::YAML_SERVICES_FILE)) {
                $existingContent = file_get_contents(self::YAML_SERVICES_FILE);
                if (preg_match('#' . trim(substr($content, 0, 50)) . '#uis', $existingContent)) {
                    $io->comment(sprintf(
                        '%s: %s',
                        '<fg=yellow>warning</>',
                        self::YAML_SERVICES_FILE . ' already modified (' . $scope . ')',
                    ));

                    return self::YAML_SERVICES_FILE;
                }
            }

            file_put_contents(
                self::YAML_SERVICES_FILE,
                "\n" . str_replace("\n", "\n    ", $content),
                \FILE_APPEND,
            );

            $io->comment(sprintf(
                '%s: %s',
                '<fg=green>updated</>',
                self::YAML_SERVICES_FILE,
            ));

            return self::YAML_SERVICES_FILE;
        } catch (\Exception $e) {
            $io->error($e->getCode() . ' : ' . $e->getMessage());

            return $e->getCode() . ' : ' . $e->getMessage();
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        // TODO: Implement configureDependencies() method.
    }
}
