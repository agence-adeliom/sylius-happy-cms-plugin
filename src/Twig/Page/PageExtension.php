<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Page;

use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageExtension extends AbstractExtension
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('happy_cms_tree_pages', \Closure::fromCallable(fn () => $this->getTreePages())),
        ];
    }

    /**
     * @return PageInterface[]
     */
    public function getTreePages(): array
    {
        $repository = $this->em->getRepository(PageInterface::class);
        $pages = [];
        if (null !== $repository) {
            $pages = $repository->findBy(['parent' => null], ['position' => 'ASC']);
        }

        return $pages;
    }
}
