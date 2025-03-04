<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Controller\Page;

use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Adeliom\SyliusHappyCMSPlugin\Entity\Page\PageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webmozart\Assert\Assert;

class PageResourceController extends SyliusCrudResourceController
{
    public function moveUpAction(int $id): Response
    {
        $pageToBeMoved = $this->findPageOr404($id);
        $repository = $this->getDoctrine()->getRepository(PageInterface::class);

        if (null === $pageToBeMoved->getPosition()) {
            $parent = $pageToBeMoved->getParent();
            foreach ($parent->getChildren() as $key => $child) {
                $child->setPosition($key);
                $this->getDoctrine()->getManager()->persist($child);
            }
            $this->getDoctrine()->getManager()->flush();
        }

        if ($pageToBeMoved->getPosition() > 0 && null !== $repository) {
            $otherPageToBeMoved = $repository->findPreviousPage($pageToBeMoved);
            if ($otherPageToBeMoved) {
                $oldPosition = $pageToBeMoved->getPosition();

                $pageToBeMoved->setPosition($pageToBeMoved->getPosition() - 1);
                $otherPageToBeMoved->setPosition($oldPosition);

                $this->getDoctrine()->getManager()->flush();
            }
        }

        return new JsonResponse('', Response::HTTP_NO_CONTENT);
    }

    public function moveDownAction(int $id): Response
    {
        $pageToBeMoved = $this->findPageOr404($id);
        $repository = $this->getDoctrine()->getRepository(PageInterface::class);

        if (null !== $repository) {
            $otherPageToBeMoved = $repository->findNextPage($pageToBeMoved);
            if ($otherPageToBeMoved) {
                $oldPosition = $pageToBeMoved->getPosition();

                $pageToBeMoved->setPosition($pageToBeMoved->getPosition() + 1);
                $otherPageToBeMoved->setPosition($oldPosition);

                $this->getDoctrine()->getManager()->flush();
            }
        }

        return new JsonResponse('', Response::HTTP_NO_CONTENT);
    }

    private function findPageOr404(int $id): PageInterface
    {
        $repository = $this->getDoctrine()->getRepository(PageInterface::class);

        $page = null;
        if (null !== $repository) {
            /** @var PageInterface|null $page */
            $page = $repository->find($id);

            if (null === $page) {
                throw new NotFoundHttpException(sprintf('Page with id %d does not exist.', $id));
            }
        }
        Assert::isInstanceOf($page, PageInterface::class);

        return $page;
    }
}
