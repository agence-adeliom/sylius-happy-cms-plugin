<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Twig\Config;

use Adeliom\SyliusHappyCMSPlugin\Services\Config\ConfigValueResolver;
use Sylius\Component\Channel\Model\ChannelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigExtension extends AbstractExtension
{
    /**
     * @param ConfigValueResolver|null $resolver deprecated since 2.3.4: the function is served by ConfigRuntime
     */
    public function __construct(
        private readonly ?ConfigValueResolver $resolver = null,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('happy_cms_config', null !== $this->resolver ? $this->getValue(...) : [ConfigRuntime::class, 'getValue']),
        ];
    }

    /**
     * Valeur d'une config pour la locale courante : surcharge du channel (courant par défaut), sinon valeur par défaut.
     *
     * @deprecated since 2.3.4, use ConfigRuntime::getValue()
     */
    public function getValue(string $key, ChannelInterface|string|null $channel = null): mixed
    {
        if (null === $this->resolver) {
            throw new \LogicException(sprintf('%s() needs a %s; use %s::getValue() instead.', __METHOD__, ConfigValueResolver::class, ConfigRuntime::class));
        }

        return $this->resolver->resolve($key, $channel);
    }
}
