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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
                '%s',
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
            ->addArgument(
                'hasFlexibleContent',
                InputArgument::OPTIONAL,
                'Use blocks for %s scope',
                true,
            )
            ->addArgument(
                'hasRouting',
                InputArgument::OPTIONAL,
                'Use routing for %s scope',
                true,
            )
            ->addArgument(
                'hasTaxonomy',
                InputArgument::OPTIONAL,
                'Generate a taxonomy associated resources for %s scope',
                true,
            )
        ;
        $inputConfig->setArgumentAsNonInteractive('scope');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $argument = $command->getDefinition()->getArgument('scope');
        /** @var string $scope */
        $scope = $io->ask($argument->getDescription(), 'Faq');

        $input->setArgument('scope', $scope);

        foreach (['entryNamespace', 'entryClassName', 'taxonomyClassName'] as $argName) {
            $arg = $command->getDefinition()->getArgument($argName);
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
        $hasFlexibleContent = $input->getArgument('hasFlexibleContent') ?? true;
        /** @var bool $hasRouting */
        $hasRouting = $input->getArgument('hasRouting') ?? true;
        /** @var bool $hasTaxonomy */
        $hasTaxonomy = $input->getArgument('hasTaxonomy') ?? true;
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

            $resourceConfigGenerator->generateAdmin(
                className: $entryClassNameDetail->getFullName(),
                templatePath: self::TPL_FILES['admin'],
                variables: [
                    'classNameDetail' => $entryClassNameDetail,
                    'relationClassNameDetail' => $taxonomyClassNameDetail,
                    'scope' => ucfirst($scope),
                    'hasFlexibleContent' => $hasFlexibleContent,
                    'hasRouting' => $hasRouting,
                ],
            );

            if ($hasTaxonomy && $taxonomyClassNameDetail) {
                $resourceConfigGenerator->generateAdmin(
                    className:    $taxonomyClassNameDetail->getFullName(),
                    templatePath: self::TPL_FILES['admin'],
                    variables:    [
                                      'classNameDetail' => $taxonomyClassNameDetail,
                                      'relationClassNameDetail' => $entryClassNameDetail,
                                      'scope' => ucfirst($scope),
                                      'hasFlexibleContent' => $hasFlexibleContent,
                                      'hasRouting' => $hasRouting,
                                  ],
                );
            }

            $resourceConfigGenerator->generateController(
                className: $entryClassNameDetail->getFullName(),
                templatePath: self::TPL_FILES['controller'],
                variables: [
                    'classNameDetail' => $entryClassNameDetail,
                    'scope' => ucfirst($scope),
                ],
            );

            $resourceConfigGenerator->generateMenuListener($entryClassNameDetail->getFullName());

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
}
