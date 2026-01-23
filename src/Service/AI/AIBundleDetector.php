<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Service\AI;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Service to detect if Symfony AI Bundle is installed and configured
 */
#[Autoconfigure(public: true)]
readonly class AIBundleDetector
{
    /**
     * Check if Symfony AI Bundle is installed and enabled
     */
    public function isAvailable(): bool
    {
        // Check if Symfony AI Bundle is installed by checking if a core class exists
        return class_exists(\Symfony\Component\AiBundle\AiBundle::class);
    }

    /**
     * Get installation instructions if AI bundle is not available
     */
    public function getInstallationInstructions(): string
    {
        return 'To use AI content generation, install the Symfony AI Bundle and configure an AI agent. See documentation: https://github.com/agence-adeliom/sylius-happy-cms-plugin/blob/2.x/docs/CONFIGURE_IA_AGENT.md';
    }
}
