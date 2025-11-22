<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\ContextProvider;

use Adeliom\SyliusHappyCMSPlugin\Factory\SharedBlock\SharedBlockCollection;
use Sylius\TwigHooks\Hookable\AbstractHookable;
use Sylius\TwigHooks\Provider\ContextProviderInterface;

class CreateSharedBlockContextProvider implements ContextProviderInterface
{
    public function __construct(
        private readonly SharedBlockCollection $sharedBlockCollection,
    ) {
    }

    /**
     * @param array<string, mixed> $hookContext
     *
     * @return array<string, mixed>
     */
    public function provide(AbstractHookable $hookable, array $hookContext): array
    {
        return [
            'blocks' => $this->sharedBlockCollection->getBlocks(),
        ];
    }

    public function supports(AbstractHookable $hookable): bool
    {
        return 'sylius.cms.shared_block.choose' === $hookable->getName();
    }
}
