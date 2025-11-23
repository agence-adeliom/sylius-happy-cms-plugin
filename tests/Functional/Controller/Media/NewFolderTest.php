<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Controller\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\MediaController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final class NewFolderTest extends KernelTestCase
{
    private MediaController $mediaController;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->mediaController = $container->get(MediaController::class);
    }

    public function testCreateNewFolderWithValidJsonData(): void
    {
        // Arrange: Préparer les données JSON
        $requestData = [
            'folder' => null,
            'new_folder_name' => 'test-folder',
        ];

        $jsonContent = json_encode($requestData, JSON_THROW_ON_ERROR);

        // Créer une requête avec du contenu JSON
        $request = new Request(
            [], // GET parameters
            [], // POST parameters
            [], // attributes
            [], // cookies
            [], // files
            ['CONTENT_TYPE' => 'application/json'], // server
            $jsonContent // content
        );

        // Act: Appeler la méthode
        $response = $this->mediaController->createNewFolder($request);

        // Assert: Vérifier que la réponse est correcte
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        // Vérifier le contenu de la réponse
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('new_folder_name', $responseData);
        $this->assertSame('test-folder', $responseData['new_folder_name']);
    }

    public function testCreateNewFolderWithFolderId(): void
    {
        // Arrange: Préparer les données JSON avec un folder ID
        $requestData = [
            'folder' => 1,
            'new_folder_name' => 'subfolder-test',
        ];

        $jsonContent = json_encode($requestData, JSON_THROW_ON_ERROR);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
        );

        // Act
        $response = $this->mediaController->createNewFolder($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('new_folder_name', $responseData);
        $this->assertSame('subfolder-test', $responseData['new_folder_name']);
    }

    public function testCreateNewFolderCleansSpecialCharacters(): void
    {
        // Arrange: Tester avec des caractères spéciaux
        $requestData = [
            'folder' => null,
            'new_folder_name' => 'test&/ `*folder',
        ];

        $jsonContent = json_encode($requestData, JSON_THROW_ON_ERROR);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $jsonContent
        );

        // Act
        $response = $this->mediaController->createNewFolder($request);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($responseData);

        // Le nom devrait être nettoyé (caractères spéciaux supprimés)
        $this->assertArrayHasKey('new_folder_name', $responseData);
        $this->assertStringNotContainsString('&', $responseData['new_folder_name']);
        $this->assertStringNotContainsString('*', $responseData['new_folder_name']);
        $this->assertStringNotContainsString('`', $responseData['new_folder_name']);
    }
}
