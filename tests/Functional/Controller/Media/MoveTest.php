<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Functional\Controller\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\MediaController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class MoveTest extends KernelTestCase
{
    private MediaController $mediaController;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->mediaController = $container->get(MediaController::class);
    }

    /**
     * Helper method to create a request with CSRF token
     */
    private function createRequestWithCsrfToken(array $requestData): Request
    {
        // Create a session
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        // Create a temporary request to generate CSRF token
        $tempRequest = new Request();
        $tempRequest->setSession($session);

        // Generate CSRF token
        $csrfToken = $this->mediaController->getCsrfToken($tempRequest);

        // Add CSRF token to request data
        $requestData['_csrf_token'] = $csrfToken;

        $jsonContent = json_encode($requestData, JSON_THROW_ON_ERROR);

        // Create the actual request with session and JSON content
        $request = new Request(
            [], // GET parameters
            [], // POST parameters
            [], // attributes
            [], // cookies
            [], // files
            ['CONTENT_TYPE' => 'application/json'], // server
            $jsonContent // content
        );
        $request->setSession($session);

        return $request;
    }

    /**
     * Test que la méthode moveItem() traite correctement les données JSON
     * en vérifiant que le décodage à la ligne 31 renvoie bien un tableau exploitable.
     */
    public function testMoveItemWithValidJsonData(): void
    {
        // Arrange: Préparer les données JSON avec la structure attendue
        $requestData = [
            'destination' => 1,
            'moved_files' => [
                [
                    'id' => 10,
                    'name' => 'fichier-test.txt',
                    'type' => 'file',
                    'storage_path' => '/ancienne/position/fichier-test.txt',
                ],
                [
                    'id' => 11,
                    'name' => 'dossier-test',
                    'type' => 'folder',
                    'storage_path' => '/ancienne/position/dossier-test',
                ],
            ],
        ];

        // Créer une requête avec un CSRF token valide
        $request = $this->createRequestWithCsrfToken($requestData);

        // Act: Appeler la méthode moveItem
        $response = $this->mediaController->moveItem($request);

        // Assert: Vérifier que la réponse est correcte
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        // Vérifier le contenu de la réponse
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($responseData, 'La réponse doit être un tableau');

        // La réponse devrait contenir des résultats pour chaque fichier déplacé
        // (même si les entités n'existent pas, il y aura des résultats)
        $this->assertNotEmpty($responseData);
    }

    /**
     * Test que moveItem() gère correctement une destination vide (déplacement vers la racine).
     */
    public function testMoveItemWithNullDestination(): void
    {
        // Arrange: Tester avec destination null (déplacement vers la racine)
        $requestData = [
            'destination' => null,
            'moved_files' => [
                [
                    'id' => 5,
                    'name' => 'fichier.pdf',
                    'type' => 'file',
                    'storage_path' => '/dossier/fichier.pdf',
                ],
            ],
        ];

        // Créer une requête avec un CSRF token valide
        $request = $this->createRequestWithCsrfToken($requestData);

        // Act: Appeler la méthode moveItem
        $response = $this->mediaController->moveItem($request);

        // Assert: Vérifier la réponse
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);
    }

    /**
     * Test que moveItem() gère correctement un tableau vide de fichiers à déplacer.
     */
    public function testMoveItemWithEmptyMovedFiles(): void
    {
        // Arrange: Tester avec un tableau vide de fichiers à déplacer
        $requestData = [
            'destination' => 3,
            'moved_files' => [],
        ];

        // Créer une requête avec un CSRF token valide
        $request = $this->createRequestWithCsrfToken($requestData);

        // Act: Appeler la méthode moveItem
        $response = $this->mediaController->moveItem($request);

        // Assert: Vérifier la réponse
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData, 'La réponse devrait être un tableau vide si aucun fichier n\'est déplacé');
    }

    /**
     * Test que json_decode lève bien une exception pour du JSON invalide.
     */
    public function testJsonDecodeThrowsExceptionForInvalidJson(): void
    {
        // Arrange: Créer une requête avec du JSON invalide
        $invalidJsonContent = '{"destination": 1, "moved_files": ['; // JSON incomplet

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $invalidJsonContent
        );

        // Assert: Vérifier que json_decode lève une exception avec JSON_THROW_ON_ERROR
        $this->expectException(\JsonException::class);

        // Act: Tenter de décoder du JSON invalide
        json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * Test que moveItem() traite correctement des données avec la structure PHPDoc complète.
     */
    public function testMoveItemWithCompletePhpDocStructure(): void
    {
        // Arrange: Créer des données qui correspondent exactement à la structure PHPDoc
        // de la ligne 21-29 dans Move.php
        $requestData = [
            'destination' => 42,
            'moved_files' => [
                [
                    'id' => 100,
                    'name' => 'document.docx',
                    'type' => 'file',
                    'storage_path' => '/documents/document.docx',
                ],
                [
                    'id' => 200,
                    'name' => 'images',
                    'type' => 'folder',
                    'storage_path' => '/images',
                ],
            ],
        ];

        // Créer une requête avec un CSRF token valide
        $request = $this->createRequestWithCsrfToken($requestData);

        // Act: Appeler la méthode moveItem avec des données complètes
        $response = $this->mediaController->moveItem($request);

        // Assert: Vérifier que la méthode traite correctement les données
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($responseData);

        // Vérifier qu'il y a un résultat pour chaque fichier déplacé
        $this->assertCount(2, $responseData, 'Il devrait y avoir 2 résultats pour les 2 fichiers déplacés');

        // Vérifier que chaque résultat contient les clés attendues
        foreach ($responseData as $result) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
        }
    }
}
