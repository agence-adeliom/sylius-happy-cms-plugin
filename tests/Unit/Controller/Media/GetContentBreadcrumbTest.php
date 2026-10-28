<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Controller\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\GetContent;
use Adeliom\SyliusHappyCMSPlugin\Entity\Media\Folder;
use PHPUnit\Framework\TestCase;

final class GetContentBreadcrumbTest extends TestCase
{
    use GetContent;

    public function testTheBreadcrumbListsTheFoldersFromTheRoot(): void
    {
        $root = $this->folder(1, 'produits');
        $child = $this->folder(7, 'fenetres', $root);

        self::assertSame(
            [['id' => 1, 'name' => 'produits'], ['id' => 7, 'name' => 'fenetres']],
            $this->getBreadcrumb($child),
        );
    }

    public function testTheBreadcrumbIsEmptyAtTheRoot(): void
    {
        self::assertSame([], $this->getBreadcrumb(null));
    }

    private function folder(int $id, string $name, ?Folder $parent = null): Folder
    {
        $folder = new Folder();
        $folder->setName($name);
        $folder->setParent($parent);
        (new \ReflectionProperty(Folder::class, 'id'))->setValue($folder, $id);

        return $folder;
    }
}
