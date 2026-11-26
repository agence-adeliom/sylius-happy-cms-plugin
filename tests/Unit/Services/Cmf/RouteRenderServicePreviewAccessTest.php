<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Services\Cmf;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Services\Cmf\RouteRenderService;
use Adeliom\SyliusHappyCMSPlugin\Services\Seo\BreadcrumbCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Controller\ParametersParser;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/**
 * A refused preview is a plain 403, not a server error: an expired signed URL must not fill the production logs.
 */
final class RouteRenderServicePreviewAccessTest extends TestCase
{
    public function testARefusedPreviewIsA403(): void
    {
        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturn(false);

        $container = new Container();
        $container->set('security.authorization_checker', $checker);

        $service = new RouteRenderService(
            $this->createStub(RouterInterface::class),
            new RequestConfigurationFactory(new ParametersParser($container, new ExpressionLanguage()), RequestConfiguration::class),
            $this->createStub(Environment::class),
            $this->createStub(EventDispatcherInterface::class),
            new BreadcrumbCollection(),
            $this->createStub(KernelInterface::class),
            $this->createStub(EntityManagerInterface::class),
            new ParameterBag(),
        );
        $service->setContainer($container);

        try {
            $service->renderAction($this->createStub(CmsRoutableInterface::class), Request::create('/ma-page?happy_cms_preview=1'));
            self::fail('The preview should be refused.');
        } catch (AccessDeniedHttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
            self::assertSame('Access Denied to preview content document', $exception->getMessage());
        }
    }
}
