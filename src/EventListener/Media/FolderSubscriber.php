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

        $parentChanged = $args->hasChangedField('parent');
        $slugChanged = $args->hasChangedField('slug');
        if (!$parentChanged && !$slugChanged) {
            return;
        }

        // The folder already holds its new parent and slug: the old ones come from the change set. A single move
        // covers a parent and a slug changed in the same flush.
        /** @var FolderInterface|null $oldParent */
        $oldParent = $parentChanged ? $args->getOldValue('parent') : $folder->getParent();
        /** @var string|null $oldSlug */
        $oldSlug = $slugChanged ? $args->getOldValue('slug') : $folder->getSlug();

        $this->manager->move(
            $this->path($oldParent, (string) $oldSlug),
            $this->path($folder->getParent(), (string) $folder->getSlug()),
        );
    }

    private function path(?FolderInterface $parent, string $slug): string
    {
        return $parent ? $parent->getPath() . \DIRECTORY_SEPARATOR . $slug : $slug;
    }
}
