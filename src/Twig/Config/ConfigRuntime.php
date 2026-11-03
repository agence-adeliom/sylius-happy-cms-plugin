<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Config;

use Adeliom\SyliusHappyCMSPlugin\Services\Config\ConfigValueResolver;
use Sylius\Component\Channel\Model\ChannelInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Runtime de `happy_cms_config()` : le résolveur (et le dépôt des configs) n'est construit qu'au
 * premier appel de la fonction, pas à l'initialisation de Twig.
 */
final class ConfigRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly ConfigValueResolver $resolver,
    ) {
    }

    /**
     * Valeur d'une config pour la locale courante : surcharge du channel (courant par défaut), sinon valeur par défaut.
     */
    public function getValue(string $key, ChannelInterface|string|null $channel = null): mixed
    {
        return $this->resolver->resolve($key, $channel);
    }
}
