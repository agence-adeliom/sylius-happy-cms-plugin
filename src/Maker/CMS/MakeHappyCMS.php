<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Maker\CMS;

use Adeliom\SyliusEasyCrudPlugin\Services\CrudMakerService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class MakeHappyCMS extends AbstractMaker
{
    public const TPL_FILES = [
        'entity' => __DIR__ . '/../../Resources/skeleton/cms/entity.tpl.php',
        'translation' => __DIR__ . '/../../Resources/skeleton/cms/translation.tpl.php',
        'content_block' => __DIR__ . '/../../Resources/skeleton/cms/content_block.tpl.php',
        'repository' => __DIR__ . '/../../Resources/skeleton/cms/repository.tpl.php',
        'admin' => __DIR__ . '/../../Resources/skeleton/cms/admin.tpl.php',
        'controller' => __DIR__ . '/../../Resources/skeleton/cms/controller.tpl.php',
    ];

    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected ParameterBagInterface $parameterBag,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:happy-cms:generate-cms-model';
    }

    public static function getCommandDescription(): string
    {
        return 'Generate Entities with Translations, Repositories and Admin classes for your CMS such as FAQ, Blog, Brand pages etc..';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('scope', InputArgument::REQUIRED, 'Scope of classes to create (ex: Faq, Blog)')
            ->addArgument(
                'entryNamespace',
                InputArgument::OPTIONAL,
                'Namespace for %s scope',
                'Faq',
            )
            ->addArgument(
                'entryClassName',
                InputArgument::OPTIONAL,
                'Entity filename name for %s',
                'Entry',
            )
            ->addArgument(
                'taxonomyClassName',
                InputArgument::OPTIONAL,
                'Taxonomy entity filename name for %s scope',
                'Taxonomy',
            )
            ->addOption(
                'no-flexible-content',
                null,
                InputOption::VALUE_NONE,
                'Disable flexible content blocks for this model',
            )
            ->addOption(
                'no-taxonomy',
                null,
                InputOption::VALUE_NONE,
                'Do not generate taxonomy associated resources',
            )
        ;
        $inputConfig->setArgumentAsNonInteractive('scope');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        // Only ask for scope if not provided
        if (!$input->getArgument('scope')) {
            $argument = $command->getDefinition()->getArgument('scope');
            /** @var string $scope */
            $scope = $io->ask($argument->getDescription(), 'Faq');
            $input->setArgument('scope', $scope);
        }

        /** @var string $scope */
        $scope = $input->getArgument('scope');

        $arguments = ['entryNamespace', 'entryClassName'];

        // Ask for boolean options if in interactive mode
        // Options are already set to false by default if not provided via CLI
        if (!$input->getOption('no-flexible-content')) {
            if ($input->getOption('no-interaction')) {
                $input->setOption('no-flexible-content', true);

                return;
            }
            $hasFlexibleContent = $io->confirm(
                sprintf('Use blocks for %s scope', $scope),
                true,
            );
            if (!$hasFlexibleContent) {
                $input->setOption('no-flexible-content', true);
            }
        }

        if (!$input->getOption('no-taxonomy')) {
            if ($input->getOption('no-interaction')) {
                $input->setOption('no-taxonomy', true);

                return;
            }
            $hasTaxonomy = $io->confirm(
                sprintf('Generate a taxonomy associated resources for %s scope', $scope),
                true,
            );
            if (!$hasTaxonomy) {
                $input->setOption('no-taxonomy', true);
            }
        }
        if (!$input->getOption('no-taxonomy')) {
            $arguments[] = 'taxonomyClassName';
        }

        // Only ask for string arguments that haven't been provided
        foreach ($arguments as $argName) {
            $arg = $command->getDefinition()->getArgument($argName);
            $currentValue = $input->getArgument($argName);

            // Skip if argument already has a non-default value
            if ($currentValue !== null && $currentValue !== $arg->getDefault()) {
                continue;
            }

            $question = sprintf($arg->getDescription(), $scope);
            if (is_string($arg->getDefault())) {
                $default = sprintf($arg->getDefault(), $scope);
                $input->setArgument(
                    $arg->getName(),
                    $io->ask($question, $default),
                );
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        /** @var string $entryNamespace */
        $entryNamespace = $input->getArgument('entryNamespace');
        /** @var string $scope */
        $scope = $input->getArgument('scope');
        /** @var bool $hasFlexibleContent */
        $hasFlexibleContent = !($input->getOption('no-flexible-content') === true);
        // Generating cms model always requires routing
        $hasRouting = true;
        /** @var bool $hasTaxonomy */
        $hasTaxonomy = !($input->getOption('no-taxonomy') === true);
        /** @var string|class-string $entryClassName */
        $entryClassName = $input->getArgument('entryClassName');
        /** @var string|class-string $taxonomyClassName */
        $taxonomyClassName = $input->getArgument('taxonomyClassName');

        $namespace = Str::asCamelCase($entryNamespace);
        $entryClassName = Str::asClassName($entryClassName);
        $entryClassNameDetail = $generator->createClassNameDetails(
            $entryClassName,
            'Entity\\HappyCMS\\' . $namespace . '\\',
        );
        $entryClassNameTranslationDetail = $generator->createClassNameDetails(
            $entryClassName,
            'Entity\\HappyCMS\\' . $namespace . '\\',
            'Translation',
        );
        $entryClassNameContentBlockDetail = $generator->createClassNameDetails(
            $entryClassName,
            'Entity\\HappyCMS\\' . $namespace . '\\',
            'ContentBlock',
        );

        $taxonomyClassNameDetail = false;
        $taxonomyClassNameTranslationDetail = false;
        $taxonomyClassNameContentBlockDetail = false;

        if ($hasTaxonomy) {
            $taxonomyClassName = Str::asClassName($taxonomyClassName);
            $taxonomyClassNameDetail = $generator->createClassNameDetails(
                $taxonomyClassName,
                'Entity\\HappyCMS\\' . $namespace . '\\',
            );
            $taxonomyClassNameTranslationDetail = $generator->createClassNameDetails(
                $taxonomyClassName,
                'Entity\\HappyCMS\\' . $namespace . '\\',
                'Translation',
            );
            $taxonomyClassNameContentBlockDetail = $generator->createClassNameDetails(
                $taxonomyClassName,
                'Entity\\HappyCMS\\' . $namespace . '\\',
                'ContentBlock',
            );
        }

        [$entity, $entityTranslation, $repository] = CrudMakerService::getEntity(
            $entryClassNameDetail->getFullName(),
            $generator,
            $this->managerRegistry,
        );

        try {
            $resourceConfigGenerator = new CrudMakerService(
                $generator,
                $namespace,
                $entity,
                $repository,
                $entityTranslation,
            );

            /** @var class-string $fullName */
            $fullName = $entryClassNameDetail->getFullName();
            $resourceConfigGenerator->generateEntity(
                $fullName,
                self::TPL_FILES['entity'],
                [
                    'classNameDetail' => $entryClassNameDetail,
                    'scope' => ucfirst($scope),
                    'addRepo' => true,
                    'addTrans' => true,
                    'hasRouting' => $hasRouting,
                    'hasFlexibleContent' => $hasFlexibleContent,
                    'isOwningSide' => true,
                    'relationClassNameDetail' => $taxonomyClassNameDetail,
                ],
            );

            if ($hasTaxonomy && $taxonomyClassNameDetail) {
                /** @var class-string $fullName */
                $fullName = $taxonomyClassNameDetail->getFullName();
                $resourceConfigGenerator->generateEntity(
                    $fullName,
                    self::TPL_FILES['entity'],
                    [
                        'classNameDetail' => $taxonomyClassNameDetail,
                        'scope' => ucfirst($scope),
                        'addRepo' => true,
                        'addTrans' => true,
                        'hasRouting' => false,
                        'hasFlexibleContent' => $hasFlexibleContent,
                        'isOwningSide' => false,
                        'relationClassNameDetail' => $entryClassNameDetail,
                    ],
                );
            }

            /** @var class-string $fullName */
            $fullName = $entryClassNameTranslationDetail->getFullName();
            $resourceConfigGenerator->generateEntity(
                $fullName,
                self::TPL_FILES['translation'],
                [
                    'classNameDetail' => $entryClassNameTranslationDetail,
                    'scope' => ucfirst($scope),
                    'extraFields' => [
                    ],
                ],
            );

            if ($hasTaxonomy && $taxonomyClassNameDetail && $taxonomyClassNameTranslationDetail) {
                /** @var class-string $fullName */
                $fullName = $taxonomyClassNameTranslationDetail->getFullName();
                $resourceConfigGenerator->generateEntity(
                    $fullName,
                    self::TPL_FILES['translation'],
                    [
                        'classNameDetail' => $taxonomyClassNameTranslationDetail,
                        'scope' => ucfirst($scope),
                        'extraFields' => [],
                    ],
                );
            }

            // Generate ContentBlock entities
            /** @var class-string $fullName */
            $fullName = $entryClassNameContentBlockDetail->getFullName();
            $resourceConfigGenerator->generateEntity(
                $fullName,
                self::TPL_FILES['content_block'],
                [
                    'classNameDetail' => $entryClassNameContentBlockDetail,
                    'parentClassNameDetail' => $entryClassNameDetail,
                    'scope' => ucfirst($scope),
                ],
            );

            if ($hasTaxonomy && $taxonomyClassNameDetail && $taxonomyClassNameContentBlockDetail) {
                /** @var class-string $fullName */
                $fullName = $taxonomyClassNameContentBlockDetail->getFullName();
                $resourceConfigGenerator->generateEntity(
                    $fullName,
                    self::TPL_FILES['content_block'],
                    [
                        'classNameDetail' => $taxonomyClassNameContentBlockDetail,
                        'parentClassNameDetail' => $taxonomyClassNameDetail,
                        'scope' => ucfirst($scope),
                    ],
                );
            }

            /** @var class-string $fullName */
            $fullName = $entryClassNameDetail->getFullName();
            $resourceConfigGenerator->generateRepository(
                $fullName,
                self::TPL_FILES['repository'],
                [
                    'classNameDetail' => $entryClassNameDetail,
                    'relationClassNameDetail' => $taxonomyClassNameDetail,
                    'scope' => ucfirst($scope),
                ],
            );

            if ($hasTaxonomy && $taxonomyClassNameDetail) {
                /** @var class-string $fullName */
                $fullName = $taxonomyClassNameDetail->getFullName();
                $resourceConfigGenerator->generateRepository(
                    $fullName,
                    self::TPL_FILES['repository'],
                    [
                        'classNameDetail' => $taxonomyClassNameDetail,
                        'relationClassNameDetail' => $entryClassNameDetail,
                        'scope' => ucfirst($scope),
                    ],
                );
            }

            $adminVariables = [
                'classNameDetail' => $entryClassNameDetail,
                'relationClassNameDetail' => $taxonomyClassNameDetail,
                'scope' => ucfirst($scope),
                'hasFlexibleContent' => $hasFlexibleContent,
                'hasRouting' => $hasRouting,
            ];

            $entryAdminDetails = $resourceConfigGenerator->generateAdmin(
                className: $entryClassNameDetail->getFullName(),
                templatePath: self::TPL_FILES['admin'],
                variables: $adminVariables,
            );

            if ($hasTaxonomy && $taxonomyClassNameDetail) {
                $taxonomyVariables = [
                    'classNameDetail' => $taxonomyClassNameDetail,
                    'relationClassNameDetail' => $entryClassNameDetail,
                    'scope' => ucfirst($scope),
                    'hasFlexibleContent' => $hasFlexibleContent,
                    'hasRouting' => $hasRouting,
                ];

                $taxonomyAdminDetails = $resourceConfigGenerator->generateAdmin(
                    className:    $taxonomyClassNameDetail->getFullName(),
                    templatePath: self::TPL_FILES['admin'],
                    variables:    $taxonomyVariables,
                );
            }

            $controllerDetails = $resourceConfigGenerator->generateController(
                className: $entryClassNameDetail->getFullName(),
                templatePath: self::TPL_FILES['controller'],
                variables: [
                    'classNameDetail' => $entryClassNameDetail,
                    'scope' => ucfirst($scope),
                ],
            );

            $resourceConfigGenerator->generateMenuListener($entryClassNameDetail->getFullName());

            if (!$this->attributesModeEnabled()) {
                $io->confirm(
                    'Press any key to continue and see the configuration to copy into the \'routes.yaml\' file',
                    true,
                );
                $config = $resourceConfigGenerator->generateRoute(true, $entryClassNameDetail->getFullName());
                $io->text($config);
                if ($hasTaxonomy && $taxonomyClassNameDetail) {
                    $configTaxonomy = $resourceConfigGenerator->generateRoute(true, $taxonomyClassNameDetail->getFullName());
                    $io->newLine();
                    $io->text($configTaxonomy);
                }
                $io->note('Please copy the above configuration into the \'config/routes.yaml\' file');
                $io->note("Don't forget to add the new route _index into the Sylius administration menu");

                $io->confirm(
                    'Press any key to continue and see the configuration to copy into the \'packages/sylius_resources.yaml\' file',
                    true,
                );
                $config = $resourceConfigGenerator->generateResource(
                    true,
                    $entryClassNameDetail->getFullName(),
                    $entryClassNameTranslationDetail->getFullName(),
                );
                $io->text($config);

                if ($hasTaxonomy && $taxonomyClassNameDetail && $taxonomyClassNameTranslationDetail) {
                    $configTaxonomy = $resourceConfigGenerator->generateResource(
                        true,
                        $taxonomyClassNameDetail->getFullName(),
                        $taxonomyClassNameTranslationDetail->getFullName(),
                    );
                    $io->newLine();
                    $io->text(str_replace(['sylius_resource:', 'resources:'], ['', ''], $configTaxonomy));
                }

                $io->note('Please copy the above configuration into the \'config/packages/sylius_resources.yaml\' file');
            } else {
                // Attribute mode: declare via #[AsResource] (entity) + #[AsAdmin] (admin), reusing
                // easy-crud's own generator so the result matches the legacy YAML blocks it replaces
                // (same alias/grid/controller). The entry resource carries its custom controller;
                // the taxonomy resource uses the default easy-crud controller.
                $this->declareViaAttributes(
                    $resourceConfigGenerator,
                    $entryClassNameDetail,
                    $entryAdminDetails,
                    $controllerDetails->getFullName(),
                    $io,
                );

                if ($hasTaxonomy && $taxonomyClassNameDetail && isset($taxonomyAdminDetails)) {
                    $this->declareViaAttributes(
                        $resourceConfigGenerator,
                        $taxonomyClassNameDetail,
                        $taxonomyAdminDetails,
                        null,
                        $io,
                    );
                }

                $io->note('Resources declared via #[AsResource] + #[AsAdmin] attributes (auto-discovered).');
            }
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->comment('Thank you for using HappyCMS Plugin');
        //$this->writeSuccessMessage($io);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // No dependencies needed
    }

    private function attributesModeEnabled(): bool
    {
        return $this->parameterBag->has('sylius_easy_crud.attributes.enabled') &&
            true === $this->parameterBag->get('sylius_easy_crud.attributes.enabled');
    }

    /**
     * Declare a generated CMS resource via attributes — #[AsResource] on the entity and
     * #[AsAdmin] on the Admin — by delegating to easy-crud's CrudMakerService. Reusing
     * easy-crud's own generator guarantees the alias/grid/controller match the legacy YAML
     * blocks this replaces (so toggling sylius_easy_crud.attributes.enabled keeps routes stable).
     */
    private function declareViaAttributes(
        CrudMakerService $generator,
        ClassNameDetails $entity,
        ClassNameDetails $admin,
        ?string $controller,
        ConsoleStyle $io,
    ): void {
        $entityPath = $generator->getGeneratedClassPath($entity->getFullName());
        if (null !== $entityPath) {
            $generator->addAsResourceAttributeToEntity($entityPath, $entity->getShortName());
            $io->comment(sprintf('%s: %s (#[AsResource])', '<fg=yellow>updated</>', $entityPath));
        }

        $adminPath = $generator->getGeneratedClassPath($admin->getFullName());
        if (null === $adminPath) {
            return;
        }

        $customController = (null !== $controller && class_exists($controller)) ? $controller : null;

        $generator->addEasyCrudAttributeToClass(
            $adminPath,
            $admin->getShortName(),
            $customController,
            Str::asClassName($entity->getShortName()),
            'admin_' . mb_strtolower(Str::asSnakeCase($entity->getShortName())),
        );
        $io->comment(sprintf('%s: %s (#[AsAdmin])', '<fg=yellow>updated</>', $adminPath));
    }
}
