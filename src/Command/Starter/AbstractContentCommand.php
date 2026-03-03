<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Command\Starter;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\MediaInterface;
use Adeliom\SyliusHappyCMSPlugin\Entity\SharedBlock\SharedBlockInterface;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\AlreadyExist;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\ExtNotAllowed;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\FolderAlreadyExist;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\FolderNotExist;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\NoFile;
use Adeliom\SyliusHappyCMSPlugin\Exceptions\Media\ProviderNotFound;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCopyFile;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

abstract class AbstractContentCommand extends Command
{
    public function __construct(
        protected readonly MediaManager $mediaManager,
        protected readonly KernelInterface $kernel,
        protected readonly ParameterBagInterface $parameterBag,
        protected readonly EntityManagerInterface $manager,
        protected readonly LoggerInterface $logger,
    ) {
        parent::__construct(null);
    }

    /**
     * @param array<string, mixed> $metas
     */
    protected function createMedia(
        string $folderPath = 'pages/home',
        string $fileName = 'image.jpg',
        ?array $metas = null,
    ): ?MediaInterface {
        $manager = $this->mediaManager;
        $folderEntity = null;
        $folders = explode('/', $folderPath);
        foreach ($folders as $key => $folder) {
            try {
                $folderEntity = $manager->createFolder($folder, implode('/', array_splice($folders, 0, $key)));
            } catch (FolderAlreadyExist|FilesystemException|Exception $exception) {
                // Folder already exists
            }
        }

        try {
            if ($folderEntity instanceof FolderInterface) {
                $folderPath = $folderEntity->getPath();
                $media = $manager->createMedia(
                    __DIR__ . '/images/' . $folderPath . '/' . $fileName,
                    $folderPath,
                    $fileName,
                );

                if (is_array($metas)) {
                    $media->setMetas(array_merge($media->getMetas(), $metas));
                    $manager->save($media);
                }

                return $media;
            }
        } catch (AlreadyExist $exception) {
            $ext = pathinfo($fileName, \PATHINFO_EXTENSION);
            if ($ext === 'jpg') {
                $ext = 'jpeg';
            }
            $folder = $manager->folderByPath($folderPath);

            $repo = $manager->getHelper()->getMediaRepository();
            if ($repo) {
                return $repo->findOneBy([
                    'folder' => $folder,
                    'slug' => (new AsciiSlugger())->slug($fileName)->toString() . '.' . $ext,
                ]);
            }
        } catch (ExtNotAllowed|FolderNotExist|NoFile|ProviderNotFound|FolderAlreadyExist
        |NotFoundExceptionInterface|ContainerExceptionInterface|FileException | FilesystemException | UnableToCopyFile $exception) {
            $this->logger->error('Error while creating media', [
                'exception' => $exception,
            ]);
        }

        return null;
    }

    /**
     * @param array{key: string, name: string, status: bool, translations: array<string, mixed>} $data
     */
    protected function createSharedBlock(string $blockType, array $data): ?SharedBlockInterface
    {
        // Get Shared Block class from Sylius resources
        /** @var array<string, array{
         *  classes: array{
         *     model: class-string,
         *     controller: class-string,
         *     repository: class-string,
         *     form: class-string,
         *     factory: class-string,
         *  },
         *   translation: array{
         *    classes: array{
         *       model: class-string,
         *       controller: class-string,
         *       repository: class-string,
         *       form: class-string,
         *       factory: class-string,
         *    }
         *   }
         * }|null> $resources */
        $resources = $this->parameterBag->get('sylius.resources');

        if (!$resources) {
            return null;
        }

        if (
            empty($resources['sylius_happy_cms.shared_block'])
        ) {
            return null;
        }

        /** @var class-string<SharedBlockInterface> $sharedBlockClass */
        $sharedBlockClass = $resources['sylius_happy_cms.shared_block']['classes']['model'];
        /** @var class-string<TranslationInterface> $sharedBlockTranslationClass */
        $sharedBlockTranslationClass = $resources['sylius_happy_cms.shared_block']['translation']['classes']['model'];

        /** @var SharedBlockInterface|null $sharedBlock */
        $sharedBlock = $this->manager->getRepository($sharedBlockClass)->findOneBy([
           'type' => $blockType,
           'key' => $data['key'],
       ]);
        if (null === $sharedBlock) {
            $sharedBlock = new $sharedBlockClass();
            $sharedBlock->setType($blockType);
            $sharedBlock->setName($data['name']);
            $sharedBlock->setKey($data['key']);
            $sharedBlock->setStatus($data['status']);
        }
        foreach ($data['translations'] as $locale => $translationContent) {
            /** @var TranslationInterface $sharedBlockTranslation */
            $sharedBlockTranslation = new $sharedBlockTranslationClass();
            $sharedBlockTranslation->setLocale($locale);
            if (method_exists($sharedBlockTranslation, 'setContent')) {
                $sharedBlockTranslation->setContent((array) $translationContent);
            }
            $sharedBlock->addTranslation($sharedBlockTranslation);
        }
        $this->manager->persist($sharedBlock);
        $this->manager->flush();

        return $sharedBlock;
    }
}
