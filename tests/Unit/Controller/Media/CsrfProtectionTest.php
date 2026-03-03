<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusHappyCMSPlugin\Unit\Controller\Media;

use Adeliom\SyliusHappyCMSPlugin\Controller\Media\Module\CsrfProtection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

class CsrfProtectionTest extends TestCase
{
    use CsrfProtection;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnArgument(0);
    }

    /**
     * @test
     */
    public function it_generates_a_csrf_token(): void
    {
        $request = $this->createRequestWithSession();

        $token = $this->generateCsrfToken($request);

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * @test
     */
    public function it_stores_token_in_session(): void
    {
        $request = $this->createRequestWithSession();

        $token = $this->generateCsrfToken($request);

        $session = $request->getSession();
        $sessionData = $session->get('_media_csrf_token');

        $this->assertIsArray($sessionData);
        $this->assertArrayHasKey('token', $sessionData);
        $this->assertArrayHasKey('timestamp', $sessionData);
        $this->assertEquals($token, $sessionData['token']);
        $this->assertIsInt($sessionData['timestamp']);
    }

    /**
     * @test
     */
    public function it_retrieves_existing_token(): void
    {
        $request = $this->createRequestWithSession();

        $token1 = $this->getCsrfToken($request);
        $token2 = $this->getCsrfToken($request);

        $this->assertEquals($token1, $token2, 'Token should be the same on multiple calls');
    }

    /**
     * @test
     */
    public function it_validates_correct_token_from_json_body(): void
    {
        $request = $this->createRequestWithSession();
        $token = $this->generateCsrfToken($request);

        $request = $this->createRequestWithSession($request->getSession());
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $request->initialize([], [], [], [], [], [], json_encode([
            '_csrf_token' => $token,
            'some_data' => 'value',
        ]));

        $result = $this->validateCsrfToken($request);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_validates_correct_token_from_post_params(): void
    {
        $request = $this->createRequestWithSession();
        $token = $this->generateCsrfToken($request);

        $request = $this->createRequestWithSession($request->getSession());
        $request->setMethod('POST');
        $request->request->set('_csrf_token', $token);

        $result = $this->validateCsrfToken($request);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_validates_correct_token_from_header(): void
    {
        $request = $this->createRequestWithSession();
        $token = $this->generateCsrfToken($request);

        $request = $this->createRequestWithSession($request->getSession());
        $request->setMethod('POST');
        $request->headers->set('X-CSRF-Token', $token);

        $result = $this->validateCsrfToken($request);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_token_is_missing(): void
    {
        $request = $this->createRequestWithSession();
        $this->generateCsrfToken($request);

        $request = $this->createRequestWithSession($request->getSession());
        $request->setMethod('POST');
        // No token provided

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('error.csrf_token_missing');

        $this->validateCsrfToken($request);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_token_is_invalid(): void
    {
        $request = $this->createRequestWithSession();
        $this->generateCsrfToken($request);

        $request = $this->createRequestWithSession($request->getSession());
        $request->setMethod('POST');
        $request->request->set('_csrf_token', 'invalid-token-12345');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('error.csrf_token_invalid');

        $this->validateCsrfToken($request);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_token_is_expired(): void
    {
        $request = $this->createRequestWithSession();
        $token = $this->generateCsrfToken($request);

        // Manually set token timestamp to 3 hours ago (expired)
        $session = $request->getSession();
        $session->set('_media_csrf_token', [
            'token' => $token,
            'timestamp' => time() - 10800, // 3 hours ago
        ]);

        $request = $this->createRequestWithSession($request->getSession());
        $request->setMethod('POST');
        $request->request->set('_csrf_token', $token);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('error.csrf_token_expired');

        $this->validateCsrfToken($request);
    }

    /**
     * @test
     */
    public function it_prevents_timing_attacks(): void
    {
        $request = $this->createRequestWithSession();
        $correctToken = $this->generateCsrfToken($request);

        // Create tokens that differ at different positions
        $tokens = [
            'a' . substr($correctToken, 1), // differs at first char
            substr($correctToken, 0, 32) . 'a' . substr($correctToken, 33), // differs at middle
            substr($correctToken, 0, -1) . 'a', // differs at last char
        ];

        foreach ($tokens as $wrongToken) {

            $session = $request->getSession();
            $session->set('_csrf_token', [
                'token' => $wrongToken,
                'timestamp' => time() - 10800, // 3 hours ago
            ]);

            $request = $this->createRequestWithSession($request->getSession());
            $request->setMethod('POST');
            $request->request->set('_csrf_token', $wrongToken);

            try {
                $this->validateCsrfToken($request);
                $this->fail('Should have thrown BadRequestException');
            } catch (BadRequestException $e) {
                // Expected
                $this->assertStringContainsString('invalid', $e->getMessage());
            }
        }
    }

    /**
     * @test
     */
    public function it_refreshes_token(): void
    {
        $request = $this->createRequestWithSession();
        $token1 = $this->getCsrfToken($request);

        sleep(1); // Ensure timestamp changes

        $this->refreshCsrfToken($request);
        $token2 = $this->getCsrfToken($request);

        $this->assertNotEquals($token1, $token2, 'Token should be different after refresh');
    }

    /**
     * @test
     */
    public function it_generates_different_tokens_for_different_sessions(): void
    {
        $request1 = $this->createRequestWithSession();
        $token1 = $this->generateCsrfToken($request1);

        $request2 = $this->createRequestWithSession();
        $token2 = $this->generateCsrfToken($request2);

        $this->assertNotEquals($token1, $token2, 'Tokens should be different for different sessions');
    }

    /**
     * @test
     */
    public function it_uses_custom_token_key(): void
    {
        $request = $this->createRequestWithSession();
        $token = $this->generateCsrfToken($request);

        $request = $this->createRequestWithSession($request->getSession());
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $request->initialize([], [], [], [], [], [], json_encode([
            'custom_token_key' => $token,
            'some_data' => 'value',
        ]));

        $result = $this->validateCsrfToken($request, 'custom_token_key');

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_session_is_not_available(): void
    {
        $request = new Request();
        // No session attached

        // Symfony 6+ throws SessionNotFoundException instead of RuntimeException
        $this->expectException(\Symfony\Component\HttpFoundation\Exception\SessionNotFoundException::class);
        $this->expectExceptionMessage('Session has not been set');

        $this->generateCsrfToken($request);
    }

    /**
     * @test
     */
    public function it_handles_empty_json_body_gracefully(): void
    {
        $request = $this->createRequestWithSession();
        $this->generateCsrfToken($request);

        $request = $this->createRequestWithSession($request->getSession());
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $request->initialize([], [], [], [], [], [], '');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('error.csrf_token_missing');

        $this->validateCsrfToken($request);
    }

    /**
     * @test
     */
    public function it_handles_malformed_json_gracefully(): void
    {
        $request = $this->createRequestWithSession();
        $token = $this->generateCsrfToken($request);

        $request = $this->createRequestWithSession($request->getSession());
        $request->setMethod('POST');
        $request->headers->set('Content-Type', 'application/json');
        $request->initialize([], [], [], [], [], [], '{invalid json}');
        $request->request->set('_csrf_token', $token);

        // Should fallback to POST params
        $result = $this->validateCsrfToken($request);

        $this->assertTrue($result);
    }

    private function createRequestWithSession(?Session $existingSession = null): Request
    {
        $request = new Request();
        $session = $existingSession ?? new Session(new MockArraySessionStorage());
        $request->setSession($session);

        return $request;
    }
}
