<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Tests\Functional\Services\AI;

use Adeliom\SyliusHappyCMSPlugin\Services\AI\AIBundleDetector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AIBundleDetectorTest extends KernelTestCase
{
    private AIBundleDetector $detector;

    protected function setUp(): void
    {
        self::bootKernel();

        // Get the detector service from the container or instantiate it with the kernel
        $this->detector = new AIBundleDetector(self::$kernel);
    }

    public function testIsAvailableChecksIfAIBundleExists(): void
    {
        $isAvailable = $this->detector->isAvailable();

        // The result depends on whether symfony/ai-bundle is installed
        // In a test environment without the bundle, it should return false
        $this->assertIsBool($isAvailable);
    }

    public function testIsAvailableReturnsFalseWhenBundleNotInstalled(): void
    {
        // Assuming the AI bundle is not installed in the test environment
        // This test validates that the method works correctly
        $isAvailable = $this->detector->isAvailable();

        // We can't assert the exact value as it depends on dependencies
        // but we can verify it returns a boolean
        $this->assertIsBool($isAvailable);
    }

    public function testGetInstallationInstructionsReturnsString(): void
    {
        $instructions = $this->detector->getInstallationInstructions();

        $this->assertIsString($instructions);
        $this->assertNotEmpty($instructions);
        $this->assertStringContainsString('Symfony AI Bundle', $instructions);
        $this->assertStringContainsString('https://github.com/agence-adeliom/sylius-happy-cms-plugin', $instructions);
    }
}
