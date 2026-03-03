<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security;

use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ContentDocumentVoter implements VoterInterface
{
    public const PAGE_BUILDER = 'page_builder';

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        if (!($subject instanceof CmsRoutableInterface)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if ($this->authorizationChecker->isGranted('ROLE_HAPPY_CMS_CONTENT_BUILDER')) {
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}
