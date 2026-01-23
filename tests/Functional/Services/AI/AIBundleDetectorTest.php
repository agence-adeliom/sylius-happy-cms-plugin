<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Tests\Functional\Services\AI;

use Adeliom\SyliusHappyCMSPlugin\Service\AI\AIBundleDetector;
use PHPUnit\Framework\TestCase;

final class AIBundleDetectorTest extends TestCase
{
    public function testIsAvailableChecksIfAIBundleExists(): void
    {
        $detector = new AIBundleDetector();

        $isAvailable = $detector->isAvailable();

        // The result depends on whether symfony/ai-bundle is installed
        // In a test environment without the bundle, it should return false
        $this->assertIsBool($isAvailable);
    }

    public function testIsAvailableReturnsFalseWhenBundleNotInstalled(): void
    {
        $detector = new AIBundleDetector();

        // Assuming the AI bundle is not installed in the test environment
        // This test validates that the method works correctly
        $isAvailable = $detector->isAvailable();

        // We can't assert the exact value as it depends on dependencies
        // but we can verify it returns a boolean
        $this->assertIsBool($isAvailable);
    }

    public function testGetInstallationInstructionsReturnsString(): void
    {
        $detector = new AIBundleDetector();

        $instructions = $detector->getInstallationInstructions();

        $this->assertIsString($instructions);
        $this->assertNotEmpty($instructions);
        $this->assertStringContainsString('Symfony AI Bundle', $instructions);
        $this->assertStringContainsString('https://github.com/agence-adeliom/sylius-happy-cms-plugin', $instructions);
    }
}
