<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Config;

use Adeliom\SyliusHappyCMSPlugin\Services\Config\ConfigValueResolver;
use Sylius\Component\Channel\Model\ChannelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigExtension extends AbstractExtension
{
    public function __construct(
        private readonly ConfigValueResolver $resolver,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('happy_cms_config', $this->getValue(...)),
        ];
    }

    /**
     * Valeur d'une config pour la locale courante : surcharge du channel (courant par défaut), sinon valeur par défaut.
     */
    public function getValue(string $key, ChannelInterface|string|null $channel = null): mixed
    {
        return $this->resolver->resolve($key, $channel);
    }
}
