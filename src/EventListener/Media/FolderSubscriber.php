<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\EventListener\Media;

use Adeliom\SyliusHappyCMSPlugin\Entity\Media\FolderInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Media\MediaManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use League\Flysystem\FilesystemException;

class FolderSubscriber
{
    public function __construct(private MediaManager $manager)
    {
    }

    /**
     * @throws FilesystemException
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        /** @var FolderInterface $folder */
        $folder = $args->getObject();
        if (!$folder instanceof FolderInterface) {
            return;
        }

        if ($args->hasChangedField('parent')) {
            /** @var FolderInterface|null $old */
            $old = $args->getOldValue('parent');
            /** @var FolderInterface|null $new */
            $new = $args->getNewValue('parent');
            $oldPath = ($old ? $old->getPath() : '') . \DIRECTORY_SEPARATOR . $folder->getSlug();
            $newPath = ($new ? $new->getPath() : '') . \DIRECTORY_SEPARATOR . $folder->getSlug();
            $this->manager->move($oldPath, $newPath);
        }

        if ($args->hasChangedField('slug')) {
            $oldPath = basename($folder->getPath()) . \DIRECTORY_SEPARATOR . $args->getOldValue('slug');
            $newPath = basename($folder->getPath()) . \DIRECTORY_SEPARATOR . $args->getNewValue('slug');
            $this->manager->move($oldPath, $newPath);
        }
    }
}
