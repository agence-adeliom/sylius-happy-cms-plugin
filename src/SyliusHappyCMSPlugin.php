<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\Compiler\CheckBlockAttributesPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SyliusHappyCMSPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CheckBlockAttributesPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
