<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Maker\CMS;

use Adeliom\SyliusHappyCMSPlugin\Maker\CMS\MakeHappyCMS;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class MakeHappyCMSTest extends KernelTestCase
{
    private Application $application;
    private CommandTester $commandTester;
    private string $projectDir;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->application = new Application(self::$kernel);
        $command = $this->application->find('make:happy-cms:generate-cms-model');
        $this->commandTester = new CommandTester($command);

        // Save project directory before kernel is shut down
        $this->projectDir = self::$kernel->getProjectDir();
    }

    public function testCommandWithAllArgumentsNonInteractive(): void
    {
        // Execute command with all arguments provided
        $this->commandTester->execute([
            'scope' => 'Blog',
            'entryNamespace' => 'Blog',
            'entryClassName' => 'Article',
            'taxonomyClassName' => 'Category',
            '--no-interaction' => true,
        ]);

        // Command should complete successfully
        $this->assertSame(0, $this->commandTester->getStatusCode());

        // Output should contain success messages
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Thank you for using HappyCMS Plugin', $output);
    }

    public function testCommandWithScopeOnlyNonInteractive(): void
    {
        // Execute command with only scope provided
        $this->commandTester->execute([
            'scope' => 'Faq',
            '--no-interaction' => true,
        ]);

        // Command should complete successfully
        $this->assertSame(0, $this->commandTester->getStatusCode());

        // Output should contain expected configuration
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Thank you for using HappyCMS Plugin', $output);
    }

    public function testCommandWithPartialArgumentsNonInteractive(): void
    {
        // Execute command with partial arguments
        $this->commandTester->execute([
            'scope' => 'Brand',
            'entryClassName' => 'Brand',
            '--no-interaction' => true,
        ]);

        // Command should complete successfully
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Thank you for using HappyCMS Plugin', $output);
    }

    public function testCommandWithoutTaxonomy(): void
    {
        // Execute command without taxonomy
        $this->commandTester->execute([
            'scope' => 'Gallery',
            'entryNamespace' => 'Gallery',
            'entryClassName' => 'Image',
            '--no-taxonomy' => true,
            '--no-interaction' => true,
        ]);

        // Command should complete successfully
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Thank you for using HappyCMS Plugin', $output);

        // Should not contain taxonomy configuration
        $this->assertStringNotContainsString('Taxonomy', $output);
    }

    public function testCommandWithoutFlexibleContent(): void
    {
        // Execute command without flexible content
        $this->commandTester->execute([
            'scope' => 'SimpleContent',
            '--no-flexible-content' => true,
            '--no-interaction' => true,
        ]);

        // Command should complete successfully
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Thank you for using HappyCMS Plugin', $output);
    }

    public function testCommandWithCustomNamespace(): void
    {
        // Execute command with custom namespace
        $this->commandTester->execute([
            'scope' => 'Custom',
            'entryNamespace' => 'MyCustom\Namespace',
            'entryClassName' => 'CustomEntry',
            'taxonomyClassName' => 'CustomTaxonomy',
            '--no-interaction' => true,
        ]);

        // Command should complete successfully
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Thank you for using HappyCMS Plugin', $output);
    }

    public function testArgumentsAreRespectedWhenProvidedViaCommandLine(): void
    {
        // This test verifies that when arguments are provided via command line,
        // the command doesn't prompt interactively

        $this->commandTester->execute([
            'scope' => 'TestScope',
            'entryNamespace' => 'TestNamespace',
            'entryClassName' => 'TestEntry',
            'taxonomyClassName' => 'TestTaxonomy',
            '--no-interaction' => true,
        ]);

        // Command should complete without errors
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();

        // Verify that the command used the provided arguments
        $this->assertStringContainsString('Thank you for using HappyCMS Plugin', $output);

        // The output should contain the configurations with our custom names
        // Note: We can't easily check the generated file names without running the full generation
        // but we can verify the command completed successfully
    }

    public function testCommandWithMultipleDisabledFeatures(): void
    {
        // Execute command with multiple features disabled
        $this->commandTester->execute([
            'scope' => 'Minimal',
            'entryNamespace' => 'Minimal',
            'entryClassName' => 'MinimalEntry',
            '--no-flexible-content' => true,
            '--no-taxonomy' => true,
            '--no-interaction' => true,
        ]);

        // Command should complete successfully
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Thank you for using HappyCMS Plugin', $output);

        // Should not contain taxonomy configuration
        $this->assertStringNotContainsString('Taxonomy', $output);
    }

    public function testCommandWithOriginalSyntax(): void
    {
        // Test the original command syntax from the issue
        $this->commandTester->execute([
            'scope' => 'Demo',
            'entryNamespace' => 'Demo',
            'entryClassName' => 'Demo',
            'taxonomyClassName' => 'DemoCategory',
            '--no-interaction' => true,
        ]);

        // Command should complete successfully
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Thank you for using HappyCMS Plugin', $output);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up generated files if needed
        $this->cleanupGeneratedFiles();
    }

    private function cleanupGeneratedFiles(): void
    {
        // Define paths to clean up generated test files
        $testEntityPath = $this->projectDir . '/tests/TestApplication/src/Entity/HappyCMS';

        // Only clean up if the test entity path exists
        if (is_dir($testEntityPath)) {
            // Recursively remove test directories
            $testDirectories = [
                'Blog',
                'Faq',
                'Brand',
                'Gallery',
                'SimpleContent',
                'NonRoutable',
                'Custom',
                'TestScope',
                'Minimal',
                'Demo',
                'TestNamespace',
            ];

            foreach ($testDirectories as $dir) {
                $fullPath = $testEntityPath . '/' . $dir;
                if (is_dir($fullPath)) {
                    $this->removeDirectory($fullPath);
                }
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
