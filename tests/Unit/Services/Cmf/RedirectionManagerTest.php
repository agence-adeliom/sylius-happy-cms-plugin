<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRoute;
use Adeliom\SyliusHappyCMSPlugin\Entity\Cmf\RedirectRouteInterface;
use Adeliom\SyliusHappyCMSPlugin\Repository\Cmf\RedirectRouteRepositoryInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RedirectionManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RedirectionManagerTest extends TestCase
{
    public function testItRecordsTheUnknownUrlAndRedirectsWhenAKnownOneHasATarget(): void
    {
        $known = $this->redirectRoute('/known', '/target');
        $repository = $this->repository([$known->getStaticPrefix() => $known]);

        $manager = $this->manager($repository, redirect: true, recordNotFound: true);

        // Known URL: redirected, nothing persisted.
        $response = $manager->onHttpNotFoundException($this->event('/known'));
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/target', $response->getTargetUrl());
        self::assertSame([], $repository->persisted);

        // Unknown URL: recorded, no redirect (the new row has no target yet).
        self::assertNull($manager->onHttpNotFoundException($this->event('/unknown')));
        self::assertCount(1, $repository->persisted);
        self::assertSame('/unknown', $repository->persisted[0]->getStaticPrefix());
    }

    public function testWithoutRecordingAnUnknownUrlIsNotPersisted(): void
    {
        $known = $this->redirectRoute('/known', '/target');
        $repository = $this->repository([$known->getStaticPrefix() => $known]);

        $manager = $this->manager($repository, redirect: true, recordNotFound: false);

        self::assertNull($manager->onHttpNotFoundException($this->event('/unknown')));
        self::assertSame([], $repository->persisted);

        // The redirections keep working.
        $response = $manager->onHttpNotFoundException($this->event('/known'));
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/target', $response->getTargetUrl());
    }

    public function testWithoutRedirectionKnownUrlsAreNotRedirectedButUnknownOnesAreRecorded(): void
    {
        $known = $this->redirectRoute('/known', '/target');
        $repository = $this->repository([$known->getStaticPrefix() => $known]);

        $manager = $this->manager($repository, redirect: false, recordNotFound: true);

        self::assertNull($manager->onHttpNotFoundException($this->event('/known')));
        self::assertSame([], $repository->persisted);

        self::assertNull($manager->onHttpNotFoundException($this->event('/unknown')));
        self::assertCount(1, $repository->persisted);
    }

    private function redirectRoute(string $path, string $uri): RedirectRouteInterface
    {
        $route = new RedirectRoute();
        $route->setStaticPrefix($path);
        $route->setUri($uri);

        return $route;
    }

    /**
     * @param array<string, RedirectRouteInterface> $known
     */
    private function repository(array $known): EntityRepository&RedirectRouteRepositoryInterface
    {
        return new class($known) extends EntityRepository implements RedirectRouteRepositoryInterface {
            /** @var list<RedirectRouteInterface> */
            public array $persisted = [];

            /**
             * @param array<string, RedirectRouteInterface> $known
             */
            public function __construct(private readonly array $known)
            {
            }

            public function createNew(): RedirectRouteInterface
            {
                return new RedirectRoute();
            }

            public function findByHostAndPath(string $host, string $path): ?RedirectRouteInterface
            {
                return $this->known[$path] ?? null;
            }
        };
    }

    private function manager(object $repository, bool $redirect, bool $recordNotFound): RedirectionManager
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $manager->method('persist')->willReturnCallback(static function (RedirectRouteInterface $route) use ($repository): void {
            $repository->persisted[] = $route;
        });

        return new RedirectionManager($manager, new ParameterBag(['sylius.resources' => []]), $redirect, $recordNotFound);
    }

    private function event(string $uri): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create($uri),
            HttpKernelInterface::MAIN_REQUEST,
            new NotFoundHttpException(),
        );
    }
}
