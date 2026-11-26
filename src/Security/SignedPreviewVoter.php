<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Adeliom\SyliusHappyCMSPlugin\Security\Preview\SignedPreviewRequestValidatorInterface;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Grants the page builder right to a preview request signed for this very content, resource and locale.
 * It never denies: a logged-in admin keeps the right through ContentDocumentVoter.
 */
final class SignedPreviewVoter implements VoterInterface
{
    /**
     * @param array<string, mixed> $resources Sylius resources configuration (%sylius.resources%)
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SignedPreviewRequestValidatorInterface $validator,
        private readonly array $resources,
    ) {
    }

    /**
     * @param array<mixed> $attributes
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        if (!in_array(ContentDocumentVoter::PAGE_BUILDER, $attributes, true) || !$subject instanceof CmsRoutableInterface) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $context = $this->validator->validate($request);
        if (null === $context) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $model = $this->modelClass($context->resource);
        $id = $subject instanceof ResourceInterface ? $subject->getId() : null;
        $id = is_int($id) || is_string($id) ? (string) $id : null;

        if (null === $model ||
            !$subject instanceof $model ||
            (string) $id !== (string) $context->id ||
            $context->locale !== $request->getLocale()) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        return VoterInterface::ACCESS_GRANTED;
    }

    private function modelClass(string $resource): ?string
    {
        $config = $this->resources[$resource] ?? null;
        $model = is_array($config) && is_array($config['classes'] ?? null) ? ($config['classes']['model'] ?? null) : null;

        return is_string($model) && class_exists($model) ? $model : null;
    }
}
