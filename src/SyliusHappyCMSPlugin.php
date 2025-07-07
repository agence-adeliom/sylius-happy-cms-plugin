<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin;

use Adeliom\SyliusHappyCMSPlugin\DependencyInjection\SyliusHappyCMSExtension;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SyliusHappyCMSPlugin extends AbstractBundle
{
    use SyliusPluginTrait;

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SyliusHappyCMSExtension();
    }
}
