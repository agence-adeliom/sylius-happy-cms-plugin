<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Security;

use Adeliom\SyliusHappyCMSPlugin\Attribute\ContentPreview;
use Adeliom\SyliusHappyCMSPlugin\Factory\CMS\CmsRoutableInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ContentDocumentVoter implements VoterInterface
{
    public const ROLE_CMS_PREVIEW = 'ROLE_CMS_PREVIEW';

    /**
     * @param array<string, mixed> $attributes
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        if (!($subject instanceof CmsRoutableInterface)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        foreach ((new \ReflectionClass($subject))->getAttributes() as $attribute) {
            if ($attribute->getName() === ContentPreview::class) {
                $instance = $attribute->newInstance();
                $attributeRoles = [];
                if (method_exists($instance, 'getRoles')) {
                    $attributeRoles = $instance->getRoles() ?? [];
                }

                if ([] === $attributeRoles) {
                    return VoterInterface::ACCESS_ABSTAIN;
                }

                if (in_array(AuthenticatedVoter::PUBLIC_ACCESS, $attributeRoles)) {
                    return VoterInterface::ACCESS_GRANTED;
                }

                foreach ($token->getRoleNames() as $tokenRole) {
                    if (\in_array($tokenRole, $attributeRoles)) {
                        return VoterInterface::ACCESS_GRANTED;
                    }
                }
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}
